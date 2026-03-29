<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Hook;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
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
    if (!$current_user->hasPermission('administer content templates')) {
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
   * Hides content-related fields from the per-entity Canvas editor's Page data
   * panel when editing a canvas_override-enabled node.
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

    $fields_to_hide = [
      'body',
      'field_content',
      'field_image',
      'field_featured_image',
      'field_media_image',
      CANVAS_OVERRIDE_FIELD_NAME,
    ];

    foreach ($fields_to_hide as $field_name) {
      $form_display->removeComponent($field_name);
    }
  }

  /**
   * Adds a component_tree field to the content type and configures the display.
   */
  public static function ensureCanvasField(string $bundle): void {
    $field_name = CANVAS_OVERRIDE_FIELD_NAME;

    if (!FieldStorageConfig::loadByName('node', $field_name)) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'component_tree',
        'settings' => [],
      ])->save();
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
