<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Hook;

use Drupal\canvas_override\CanvasOverrideServiceProvider;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
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
use Symfony\Component\HttpFoundation\RequestStack;

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
    private readonly RequestStack $requestStack,
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

    // Load HTMX on the content page (for users who can see the Reset local task)
    // so that task can confirm and reset through HTMX. Attached from the node
    // build rather than the local task because some admin themes re-render the
    // tabs and drop the task's #attached library.
    $account = \Drupal::currentUser();
    $bundle = $entity->bundle();
    if ($account->hasPermission('administer canvas override')
      || $account->hasPermission('use canvas override')
      || $account->hasPermission("use canvas override for $bundle")
      || $account->hasPermission('reset canvas layout')
      || $account->hasPermission("reset canvas layout for $bundle")) {
      $build['#attached']['library'][] = 'core/htmx';
      $build['#cache']['contexts'][] = 'user.permissions';
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
    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $form_object->getEntity();
    $is_enabled = (bool) $node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE);

    $form['canvas_override'] = [
      '#type' => 'details',
      '#title' => $this->t('Canvas layout'),
      '#group' => 'additional_settings',
      '#access' => \Drupal::currentUser()->hasPermission('administer canvas override'),
    ];

    // Per-content layouts need a Canvas whose ComponentTreeLoader can be
    // extended. Without it the service swap is skipped, so enabling this would
    // save a setting that does nothing. Say so here, where someone is actually
    // trying to switch it on, rather than on every site's status report.
    // @see \Drupal\canvas_override\CanvasOverrideServiceProvider
    // @see https://www.drupal.org/i/3620603
    $available = CanvasOverrideServiceProvider::isComponentTreeLoaderExtendable();

    if (!$available) {
      $form['canvas_override']['canvas_override_unavailable'] = [
        '#type' => 'container',
        '#weight' => -10,
        'message' => [
          '#theme' => 'status_messages',
          '#message_list' => [
            'warning' => [
              $this->t('Per-content Canvas layout editing is unavailable. This Canvas release ships <code>@class</code> as a final class, so Canvas Override cannot extend it and the setting below would have no effect. Apply the patch from <a href="@issue" target="_blank" rel="noopener">issue #3567225</a> — Vardot projects get it through <code>vardot/varbase-patches</code> — then rebuild caches.', [
                '@class' => 'Drupal\\canvas\\Storage\\ComponentTreeLoader',
                '@issue' => 'https://www.drupal.org/i/3567225',
              ]),
            ],
          ],
        ],
      ];
    }

    $form['canvas_override']['canvas_override_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable per-content Canvas layout editing on the <em>full content</em> view mode'),
      '#description' => $this->t('When enabled, each content item of this type gets its own Canvas layout. A <strong>Canvas Override</strong> tab appears on every content item, allowing editors to visually compose a unique page layout with Canvas components.'),
      '#default_value' => $is_enabled,
      '#disabled' => !$available,
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
    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $form_object->getEntity();
    $enabled = (bool) $form_state->getValue('canvas_override_enabled');
    $was_enabled = (bool) $form_state->getValue('canvas_override_was_enabled');

    $node_type->setThirdPartySetting('canvas_override', 'enabled', $enabled);
    $node_type->save();

    if ($enabled && !$was_enabled) {
      static::ensureCanvasField($node_type->id());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() and _update() for node_type.
   *
   * Ensures the component_tree field exists whenever a node type is saved with
   * canvas_override enabled. Covers programmatic enabling and recipes, where
   * the form submit handler never runs.
   */
  #[Hook('node_type_insert')]
  #[Hook('node_type_update')]
  public function nodeTypeSave(NodeTypeInterface $node_type): void {
    if (\Drupal::isConfigSyncing()) {
      return;
    }
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
  public function entityOperation(EntityInterface $entity, CacheableMetadata $cacheability): array {
    if ($entity->getEntityTypeId() !== 'node') {
      return [];
    }

    // Whether this operation appears depends on the current user's
    // permissions and on the node type's Canvas Override setting.
    $cacheability->addCacheContexts(['user.permissions']);

    /** @var \Drupal\node\NodeInterface $entity */
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle());
    if (!$node_type instanceof NodeTypeInterface) {
      return [];
    }

    $cacheability->addCacheableDependency($node_type);

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

    $entity_type = $node->getEntityType();
    // Don't restore fields that are edited in Canvas Override.
    // Exclude entity key fields (nid, vid, uuid, langcode, …) and
    // revision metadata keys as well.
    $preserved_fields = array_merge(
      ['title', CANVAS_OVERRIDE_FIELD_NAME],
      array_values(array_filter($entity_type->getKeys())),
      array_values(array_filter($entity_type->getRevisionMetadataKeys())),
    );

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
  public function entityBundleFieldInfoAlter(array &$fields, EntityTypeInterface $entity_type, ?string $bundle): void {
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

    $entity_type = $node->getEntityType();
    // Don't restore fields that are edited in Canvas Override.
    // Exclude entity key fields (nid, vid, uuid, langcode, …) and
    // revision metadata keys as well.
    $preserved_fields = array_merge(
      ['title', CANVAS_OVERRIDE_FIELD_NAME],
      array_values(array_filter($entity_type->getKeys())),
      array_values(array_filter($entity_type->getRevisionMetadataKeys())),
    );

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
   * Injects a flag telling the Canvas editor to hide the Page data panel when
   * editing a Canvas Override enabled node. Nodes use the standard Drupal edit
   * form for their field data; the Page data panel is not needed here.
   *
   * The entity is resolved from the request path rather than from route
   * parameters. Canvas serves its editor shell through a route match that
   * carries no entity_type/entity parameters, so reading them here always
   * yields NULL and the flag would never be set.
   *
   * @see \Drupal\canvas_override\Hook\CanvasOverrideHooks::libraryInfoAlter()
   */
  #[Hook('js_settings_alter')]
  public function jsSettingsAlter(array &$settings, AttachedAssetsInterface $assets): void {
    $node = $this->canvasEditorNode();
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

    $settings['canvas']['hidePageDataPanel'] = TRUE;
  }

  /**
   * Implements hook_library_info_alter().
   *
   * Appends this module's editor behaviour to the Canvas editor library, so it
   * loads with the editor without patching Drupal Canvas. Canvas ships its
   * editor as a prebuilt bundle, so behaviour that has to change inside the
   * editor is added alongside it rather than compiled into it.
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension !== 'canvas' || !isset($libraries['canvas-ui'])) {
      return;
    }

    $libraries['canvas-ui']['dependencies'][] = 'canvas_override/editor';
  }

  /**
   * Returns the node being edited in the Canvas editor, if any.
   *
   * Resolves both the route parameters (when Drupal upcasts them) and the
   * `/canvas/editor/node/{nid}` request path (when it does not).
   */
  private function canvasEditorNode(): ?NodeInterface {
    $entity = $this->routeMatch->getParameter('entity');
    if ($entity instanceof NodeInterface
      && $this->routeMatch->getParameter('entity_type') === 'node') {
      return $entity;
    }

    $path = $this->requestStack->getCurrentRequest()?->getPathInfo() ?? '';
    if (!\preg_match('#^/canvas/editor/node/(\d+)#', $path, $matches)) {
      return NULL;
    }

    $node = $this->entityTypeManager->getStorage('node')->load($matches[1]);
    return $node instanceof NodeInterface ? $node : NULL;
  }

  /**
   * Implements hook_menu_local_tasks_alter().
   *
   * Controls visibility of all three Canvas Override local task tabs based on
   * the current user's permissions. Only applies to content types that have
   * Canvas Override enabled.
   *
   * - Canvas Override tab: requires 'administer canvas override',
   *   'use canvas override', or 'use canvas override for {bundle}'.
   * - Reset Canvas layout tab: requires 'administer canvas override',
   *   'reset canvas layout', 'reset canvas layout for {bundle}',
   *   'use canvas override', or 'use canvas override for {bundle}'.
   * - Edit template tab: requires 'edit canvas default template' or
   *   'administer canvas override'.
   */
  #[Hook('menu_local_tasks_alter', order: new OrderAfter(modules: ['drupal_cms_helper']))]
  public function menuLocalTasksAlter(array &$data, string $route_name, RefinableCacheableDependencyInterface &$cacheability): void {
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

    $account = \Drupal::currentUser();
    $bundle = $node->bundle();
    $is_admin = $account->hasPermission('administer canvas override');
    $can_use = $account->hasPermission('use canvas override')
      || $account->hasPermission("use canvas override for $bundle");

    // Canvas Override tab: hide if the user cannot use canvas override.
    if (isset($data['tabs'][0]['canvas_override.node.canvas'])) {
      if (!$is_admin && !$can_use) {
        unset($data['tabs'][0]['canvas_override.node.canvas']);
      }
    }

    // Reset Canvas layout tab: hide if the user cannot reset layouts. Otherwise
    // wire it to confirm and reset through HTMX: clicking it asks for
    // confirmation (hx-confirm, the browser's native prompt), then posts to the
    // reset endpoint, which clears the layout and sends an HX-Redirect back to
    // the content. No dialog markup or CSS is involved. The link's href still
    // points at the standalone confirmation form, so without JavaScript it falls
    // back to a normal confirm page.
    if (isset($data['tabs'][0]['canvas_override.node.canvas.reset'])) {
      $can_reset = $account->hasPermission('reset canvas layout')
        || $account->hasPermission("reset canvas layout for $bundle");
      if (!$is_admin && !$can_use && !$can_reset) {
        unset($data['tabs'][0]['canvas_override.node.canvas.reset']);
      }
      elseif (isset($data['tabs'][0]['canvas_override.node.canvas.reset']['#link'])) {
        // Generate with bubbleable metadata so RouteProcessorCsrf emits a
        // placeholder plus a lazy builder instead of baking a real token in.
        // Without metadata it embeds the token AND skips the 'session' cache
        // context, so the cached local-tasks array hands one user's token to
        // everybody else and the POST is rejected with 403.
        // @see \Drupal\Core\Access\RouteProcessorCsrf::processOutbound()
        $generated = Url::fromRoute('canvas_override.node.canvas.reset.do', ['node' => $node->id()])->toString(TRUE);
        $cacheability->addCacheableDependency($generated);
        $post_url = $generated->getGeneratedUrl();
        $message = (string) $this->t('Reset the Canvas layout for "@title"? This removes the custom layout and restores the default @type layout, and cannot be undone.', [
          '@title' => $node->label(),
          '@type' => $node_type->label(),
        ]);
        $link = &$data['tabs'][0]['canvas_override.node.canvas.reset']['#link'];
        $link['localized_options']['attributes']['hx-post'] = $post_url;
        $link['localized_options']['attributes']['hx-confirm'] = $message;
        $link['localized_options']['attributes']['hx-swap'] = 'none';
        // The core/htmx library is attached from the node build (entityViewAlter)
        // so it survives admin themes that re-render the local tasks.
      }
    }

    // Edit template tab: hide if the user cannot edit the shared template.
    if (isset($data['tabs'][0]['entity.node.template'])) {
      $can_edit_template = $account->hasPermission('edit canvas default template')
        || $account->hasPermission("edit canvas default template for $bundle");
      if (!$is_admin && !$can_edit_template) {
        unset($data['tabs'][0]['entity.node.template']);
      }
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

    \Drupal::messenger()->addStatus(\t('A Canvas layout field has been added to this content type. Each content item will have its own Canvas layout editable from the <strong>Canvas</strong> tab.'));
  }

}
