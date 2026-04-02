<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Hook;

use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Hook\Order\OrderAfter;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for the canvas_override module.
 */
class CanvasOverrideHooks {

  use StringTranslationTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RouteMatchInterface $routeMatch,
    private readonly MessengerInterface $messenger,
    #[Autowire(service: 'entity_display.repository')]
    private readonly mixed $entityDisplayRepository,
  ) {}

  /**
   * Implements hook_entity_type_alter().
   *
   * Adds a validation constraint to nodes that restores required field values
   * before validation. This runs early in the validation process to fix empty
   * required fields before NotNull constraints are checked.
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    if (isset($entity_types['node'])) {
      $entity_types['node']->addConstraint('CanvasOverrideRestoreRequiredFields');
    }
  }

  /**
   * Implements hook_entity_view_alter().
   *
   * When a node has a per-node Canvas layout stored in field_canvas_layout,
   * replaces whatever the ContentTemplate (or standard Drupal) rendered with
   * just the per-node canvas layout.
   */
  #[Hook('entity_view_alter')]
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {
    if (!$entity instanceof NodeInterface) {
      return;
    }

    if (($build['#view_mode'] ?? NULL) !== 'full') {
      return;
    }

    $node_type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle());
    if (!$node_type?->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      return;
    }

    if (!$entity->hasField(CANVAS_OVERRIDE_FIELD_NAME) || $entity->get(CANVAS_OVERRIDE_FIELD_NAME)->isEmpty()) {
      return;
    }

    foreach (Element::children($build) as $key) {
      unset($build[$key]);
    }
    unset($build['#theme']);

    $build[CANVAS_OVERRIDE_FIELD_NAME] = $entity->get(CANVAS_OVERRIDE_FIELD_NAME)->view([
      'label' => 'hidden',
      'type' => 'canvas_naive_render_sdc_tree',
      'settings' => [],
    ]);
  }

  /**
   * Implements hook_form_node_type_form_alter().
   *
   * Adds a "Canvas layout editing" checkbox to the content type form.
   */
  #[Hook('form_node_type_form_alter')]
  public function formNodeTypeFormAlter(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $form_state->getFormObject()->getEntity();
    $is_enabled = (bool) $node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE);

    $form['canvas_override'] = [
      '#type' => 'details',
      '#title' => $this->t('Canvas layout'),
      '#group' => 'additional_settings',
      '#access' => \Drupal::currentUser()->hasPermission('administer canvas override'),
    ];

    $form['canvas_override']['canvas_override_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable per-node Canvas layout editing on the <em>full content</em> view mode'),
      '#description' => $this->t('When enabled, each node of this type gets its own Canvas layout. A <strong>Canvas</strong> tab appears on every node, allowing editors to visually compose a unique page layout with Canvas components.'),
      '#default_value' => $is_enabled,
    ];

    $form['canvas_override']['canvas_override_was_enabled'] = [
      '#type' => 'value',
      '#value' => $is_enabled,
    ];

    $form['actions']['submit']['#submit'][] = [static::class, 'nodeTypeFormSubmit'];
  }

  /**
   * Submit handler: persists the Canvas setting and ensures the field exists.
   */
  public static function nodeTypeFormSubmit(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $form_state->getFormObject()->getEntity();
    $enabled = (bool) $form_state->getValue('canvas_override_enabled');
    $was_enabled = (bool) $form_state->getValue('canvas_override_was_enabled');

    $node_type->setThirdPartySetting('canvas_override', 'enabled', $enabled);
    $node_type->save();

    if ($enabled && !$was_enabled) {
      static::ensureCanvasField($node_type->id());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for node_type.
   *
   * Ensures the component_tree field exists whenever a node type is saved with
   * canvas_override enabled. Covers programmatic enabling (config import,
   * recipes) where the form submit handler is not triggered.
   */
  #[Hook('node_type_presave')]
  public function nodeTypePresave(NodeTypeInterface $node_type): void {
    if ($node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      static::ensureCanvasField($node_type->id());
    }
  }

  /**
   * Implements hook_entity_operation().
   *
   * Adds a "Canvas" operation to nodes whose type has Canvas enabled.
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity): array {
    if ($entity->getEntityTypeId() !== 'node') {
      return [];
    }

    /** @var \Drupal\node\NodeInterface $entity */
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle());
    if (!$node_type instanceof NodeTypeInterface) {
      return [];
    }

    if (!$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      return [];
    }

    if (!$entity->hasField(CANVAS_OVERRIDE_FIELD_NAME)) {
      return [];
    }

    $current_user = \Drupal::currentUser();
    $bundle = $entity->bundle();
    if (!$current_user->hasPermission('administer canvas override')
      && !$current_user->hasPermission('use canvas override')
      && !$current_user->hasPermission("use canvas override for $bundle")) {
      return [];
    }

    return [
      'canvas' => [
        'title' => $this->t('Canvas'),
        'url' => Url::fromRoute('canvas_override.node.canvas', ['node' => $entity->id()]),
        'weight' => 50,
      ],
    ];
  }

  /**
   * Implements hook_entity_form_display_alter().
   *
   * Removes all field components from the form display for canvas_override
   * enabled nodes. Only field_canvas_layout is saved; all other fields use
   * the standard node Edit form.
   */
  #[Hook('entity_form_display_alter')]
  public function entityFormDisplayAlter(EntityFormDisplayInterface $form_display, array $context): void {
    if (!\str_starts_with((string) $this->routeMatch->getRouteName(), 'canvas.api.')) {
      return;
    }

    if ($form_display->getTargetEntityTypeId() !== 'node') {
      return;
    }

    $bundle = $form_display->getTargetBundle();
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($bundle);
    if (!$node_type instanceof NodeTypeInterface) {
      return;
    }
    if (!$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      return;
    }

    foreach (array_keys($form_display->getComponents()) as $field_name) {
      if ($field_name === 'title') {
        continue;
      }
      $form_display->removeComponent($field_name);
    }
  }

  /**
   * Implements hook_form_alter().
   *
   * Hides all entity field form elements for canvas_override enabled nodes.
   * Only field_canvas_layout is saved; all other fields use the standard
   * node Edit form.
   */
  #[Hook('form_alter', order: Order::Last)]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!\str_starts_with((string) $this->routeMatch->getRouteName(), 'canvas.api.')) {
      return;
    }

    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof ContentEntityFormInterface) {
      return;
    }

    $entity = $form_object->getEntity();
    if (!$entity instanceof NodeInterface) {
      return;
    }

    $node_type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle());
    if (!$node_type instanceof NodeTypeInterface) {
      return;
    }
    if (!$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      return;
    }

    // Unset all entity field elements except title. Only field_canvas_layout
    // and title are saved; all other fields use the standard node Edit form.
    // We unset() rather than setting #access = FALSE because Canvas core's
    // extractFormValues() still processes #access = FALSE widgets, clearing
    // field values and causing entity validation to fail with null values.
    foreach (Element::children($form) as $key) {
      if ($key === 'title' || !$entity->hasField($key)) {
        continue;
      }
      unset($form[$key]);
    }
  }

  /**
   * Implements hook_node_field_values_init().
   *
   * When a node is being initialized (e.g., from auto-save data), this hook
   * ensures required fields have their original values from the database.
   * This prevents validation failures for required fields that are not being
   * edited in Canvas Override but may have been cleared from the auto-save.
   */
  #[Hook('node_field_values_init')]
  public function nodeFieldValuesInit(NodeInterface $node): void {
    if (!\str_starts_with((string) $this->routeMatch->getRouteName(), 'canvas.api.')) {
      return;
    }

    // Skip if no ID (truly new node).
    if (!$node->id()) {
      return;
    }

    $node_type = $this->entityTypeManager->getStorage('node_type')->load($node->bundle());
    if (!$node_type instanceof NodeTypeInterface) {
      return;
    }
    if (!$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      return;
    }

    // Load the original node from the database.
    $original = $this->entityTypeManager->getStorage('node')->loadUnchanged($node->id());
    if (!$original instanceof NodeInterface) {
      return;
    }

    // Fields that are being edited in Canvas Override - don't restore these.
    $preserved_fields = ['title', CANVAS_OVERRIDE_FIELD_NAME];

    foreach ($node->getFieldDefinitions() as $field_name => $definition) {
      if (in_array($field_name, $preserved_fields, TRUE)) {
        continue;
      }
      // Restore field values from original if current is empty.
      if ($original->hasField($field_name) && $node->hasField($field_name)) {
        $current_value = $node->get($field_name)->getValue();
        $original_value = $original->get($field_name)->getValue();
        // Restore if current is empty but original has a value.
        if (empty($current_value) && !empty($original_value)) {
          $node->set($field_name, $original_value);
        }
      }
    }
  }

  /**
   * Implements hook_entity_bundle_field_info_alter().
   *
   * Removes the NotNull constraint from required fields during Canvas API
   * requests for canvas_override-enabled node types. This prevents validation
   * failures for required fields that are not being edited in Canvas Override.
   */
  #[Hook('entity_bundle_field_info_alter')]
  public function entityBundleFieldInfoAlter(array &$fields, \Drupal\Core\Entity\EntityTypeInterface $entity_type, string $bundle): void {
    if ($entity_type->id() !== 'node') {
      return;
    }

    if (!\str_starts_with((string) $this->routeMatch->getRouteName(), 'canvas.api.')) {
      return;
    }

    $node_type = $this->entityTypeManager->getStorage('node_type')->load($bundle);
    if (!$node_type instanceof NodeTypeInterface) {
      return;
    }
    if (!$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      return;
    }

    // Fields that should retain their constraints (being edited in Canvas).
    $preserved_fields = ['title', CANVAS_OVERRIDE_FIELD_NAME];

    foreach ($fields as $field_name => $field) {
      if (in_array($field_name, $preserved_fields, TRUE)) {
        continue;
      }
      // Remove NotNull constraint from fields not being edited in Canvas Override.
      if ($field instanceof FieldConfig) {
        $constraints = $field->getConstraints();
        if (isset($constraints['NotNull'])) {
          unset($constraints['NotNull']);
          $field->setConstraints($constraints);
        }
      }
    }
  }

  /**
   * Implements hook_node_presave().
   *
   * Restores required field values that may have been cleared during Canvas
   * form processing. When Canvas Override removes fields from the form display
   * for node override editing, required fields like ai_automator_status can
   * get set to null. This hook restores them from the original entity.
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $node): void {
    if (!\str_starts_with((string) $this->routeMatch->getRouteName(), 'canvas.api.')) {
      return;
    }

    $node_type = $this->entityTypeManager->getStorage('node_type')->load($node->bundle());
    if (!$node_type instanceof NodeTypeInterface) {
      return;
    }
    if (!$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      return;
    }

    // If the node is new, nothing to restore.
    if ($node->isNew()) {
      return;
    }

    // Load the original node from the database.
    $original = $this->entityTypeManager->getStorage('node')->loadUnchanged($node->id());
    if (!$original instanceof NodeInterface) {
      return;
    }

    // Restore all field values except title and field_canvas_layout.
    // These are the only fields that should be modified in Canvas Override.
    $preserved_fields = ['title', CANVAS_OVERRIDE_FIELD_NAME];
    foreach ($node->getFieldDefinitions() as $field_name => $definition) {
      if (in_array($field_name, $preserved_fields, TRUE)) {
        continue;
      }
      // Only restore if the field exists on the original and the current
      // value is empty but the original has a value.
      if ($original->hasField($field_name) && $node->hasField($field_name)) {
        $current_value = $node->get($field_name)->getValue();
        $original_value = $original->get($field_name)->getValue();
        // Restore if current is empty but original is not, or if the field
        // is required and current is empty.
        if (empty($current_value) && !empty($original_value)) {
          $node->set($field_name, $original_value);
        }
      }
    }
  }

  /**
   * Implements hook_js_settings_alter().
   *
   * Injects a flag telling the Canvas React app to hide the Page data panel
   * when editing a canvas_override-enabled node. Nodes use the standard Drupal
   * edit form for their field data; the Page data panel is not needed here.
   */
  #[Hook('js_settings_alter')]
  public function jsSettingsAlter(array &$settings, AttachedAssetsInterface $assets): void {
    $entity_type = $this->routeMatch->getParameter('entity_type');
    if ($entity_type !== 'node') {
      return;
    }

    $entity = $this->routeMatch->getParameter('entity');
    if (!$entity instanceof NodeInterface) {
      return;
    }

    $node_type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle());
    if (!$node_type instanceof NodeTypeInterface) {
      return;
    }
    if (!$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      return;
    }

    $settings['canvas']['hidePageDataPanel'] = TRUE;
  }

  /**
   * Implements hook_menu_local_tasks_alter().
   *
   * Hides the "Edit template" tab when canvas_override is enabled for a content
   * type. Nodes with canvas_override use per-node Canvas layouts and should
   * only show "Canvas Override" and "Reset to default layout" options.
   */
  #[Hook('menu_local_tasks_alter', order: new OrderAfter(modules: ['drupal_cms_helper']))]
  public function menuLocalTasksAlter(array &$data, string $route_name): void {
    if ($route_name !== 'entity.node.canonical' && $route_name !== 'entity.node.edit_form') {
      return;
    }

    $node = $this->routeMatch->getParameter('node');
    if (!$node instanceof NodeInterface) {
      return;
    }

    $node_type = $this->entityTypeManager->getStorage('node_type')->load($node->bundle());
    if (!$node_type instanceof NodeTypeInterface) {
      return;
    }

    if (!$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      return;
    }

    // Hide the "Edit template" tab for canvas_override enabled content types.
    // The tab is added by drupal_cms_helper with ID 'entity.node.template'.
    if (isset($data['tabs'][0]['entity.node.template'])) {
      unset($data['tabs'][0]['entity.node.template']);
    }
  }

  /**
   * Adds a component_tree field to the content type and configures the display.
   */
  public static function ensureCanvasField(string $bundle): void {
    $field_name = CANVAS_OVERRIDE_FIELD_NAME;

    $storage = FieldStorageConfig::loadByName('node', $field_name);
    if (!$storage) {
      $storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'component_tree',
        'settings' => [],
        'locked' => TRUE,
      ]);
      $storage->save();
    }
    elseif (!$storage->isLocked()) {
      $storage->setLocked(TRUE)->save();
    }

    if (!FieldConfig::loadByName('node', $bundle, $field_name)) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'bundle' => $bundle,
        'label' => 'Canvas Layout',
      ])->save();
    }

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repo */
    $display_repo = \Drupal::service('entity_display.repository');

    foreach (['full', 'default'] as $view_mode) {
      $display = $display_repo->getViewDisplay('node', $bundle, $view_mode);
      if (!$display->getComponent($field_name)) {
        $display->setComponent($field_name, [
          'label' => 'hidden',
          'type' => 'canvas_naive_render_sdc_tree',
          'weight' => -2,
        ])->save();
      }
    }

    \Drupal::messenger()->addStatus(\t('A Canvas layout field has been added to this content type. Each node will have its own Canvas layout editable from the <strong>Canvas</strong> tab.'));
  }

}
