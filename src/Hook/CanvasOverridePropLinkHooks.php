<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Hook;

use Drupal\canvas\Entity\Component;
use Drupal\canvas\Plugin\Canvas\ComponentSource\JsonSchemaPropsComponentSourceBase;
use Drupal\canvas\PropSource\LinkablePropSourceInterface;
use Drupal\canvas\PropSource\PropSource;
use Drupal\canvas\ShapeMatcher\PropSourceSuggester;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Offers "link to field" on per-content layouts, as on content templates.
 *
 * Canvas only builds the prop linker when the edited entity is a
 * ContentTemplate: both the suggestion lookup and the linker markup in
 * JsonSchemaPropsComponentSourceBase::buildComponentInstanceForm() sit behind
 * `if ($entity instanceof ContentTemplate)`. Editing a node's own component
 * tree therefore shows plain static widgets, and a prop that is already bound
 * to a field (every prop Canvas Override seeds from the content type's
 * template) renders as a disabled textbox with no way to see or change the
 * binding.
 *
 * A per-content layout has exactly the same host entity semantics as the
 * template it was seeded from — the node itself — so the same affordance
 * applies. This hook adds it after the fact, on the built form, using Canvas's
 * own suggester and its `linked_prop` element, for canvas_override-enabled
 * nodes only. Content templates are untouched: Canvas has already handled
 * them, and this hook skips any element it has processed.
 *
 * @see \Drupal\canvas\Plugin\Canvas\ComponentSource\JsonSchemaPropsComponentSourceBase::buildComponentInstanceForm()
 * @see \Drupal\canvas\Element\LinkedProp
 * @see https://www.drupal.org/project/canvas_override/issues/3621557
 */
final class CanvasOverridePropLinkHooks {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RouteMatchInterface $routeMatch,
    private readonly PropSourceSuggester $propSourceSuggester,
  ) {}

  /**
   * Implements hook_form_FORM_ID_alter() for component_instance_form.
   */
  #[Hook('form_component_instance_form_alter')]
  public function formComponentInstanceFormAlter(array &$form, FormStateInterface $form_state): void {
    $node = $this->getEnabledNode();
    if ($node === NULL) {
      return;
    }

    $uuid = $form['form_canvas_selected']['#value'] ?? NULL;
    if (!\is_string($uuid) || $uuid === '' || !isset($form['canvas_component_props'][$uuid])) {
      return;
    }

    // Resolve the component the same way the form itself does: from the posted
    // client-side tree. Reading the stored component tree would miss a
    // component the editor has just added but not saved yet.
    // @see \Drupal\canvas\Form\ComponentInstanceForm::buildForm()
    $component = $this->getComponentFromForm($form);
    if (!$component instanceof Component) {
      return;
    }
    $source = $component->getComponentSource();
    if (!$source instanceof JsonSchemaPropsComponentSourceBase) {
      return;
    }

    // The node is its own host entity, so suggestions come from its bundle.
    $entity_data_definition = EntityDataDefinition::create('node', $node->bundle());
    $suggestions = PropSourceSuggester::structureSuggestionsForHierarchicalResponse(
      $this->propSourceSuggester->suggest(
        $source->getSourceSpecificComponentId(),
        $source->getMetadata(),
        $entity_data_definition,
      )
    );
    if ($suggestions === []) {
      return;
    }

    // Current prop sources come from the posted client model, for the same
    // reason: it also covers components that are not saved yet.
    $client_model = \json_decode((string) ($form['form_canvas_props']['#value'] ?? ''), TRUE);
    $inputs = \is_array($client_model) && \is_array($client_model['source'] ?? NULL)
      ? $client_model['source']
      : [];
    $schema_properties = $source->getMetadata()->schema['properties'] ?? [];
    $linked_any = FALSE;

    foreach ($form['canvas_component_props'][$uuid] as $prop_name => &$element) {
      if (!\is_string($prop_name) || \str_starts_with($prop_name, '#') || !\is_array($element)) {
        continue;
      }
      if (!isset($element['widget']) || !\is_array($element['widget'])) {
        continue;
      }
      // Canvas already added the linker (content template): leave it alone.
      if (isset($element['widget']['#prop_link_data']) || ($element['widget']['#type'] ?? NULL) === 'linked_prop') {
        continue;
      }
      if (!isset($suggestions[$prop_name]) || $suggestions[$prop_name] === []) {
        continue;
      }

      $label = $schema_properties[$prop_name]['title'] ?? $prop_name;
      $description = $schema_properties[$prop_name]['description'] ?? NULL;
      $linked_source = $this->getLinkablePropSource($inputs[$prop_name] ?? NULL);

      if ($linked_source !== NULL) {
        // Already bound to a field: show the linked-field badge plus the
        // linker, exactly as Canvas does on a content template, instead of the
        // disabled static widget the node path would otherwise render.
        $element['#disabled'] = FALSE;
        $element['widget'] = [
          '#type' => 'linked_prop',
          '#sdc_prop_name' => $prop_name,
          '#sdc_prop_label' => $label,
          '#prop_source' => $linked_source,
          '#entity_data_definition' => $entity_data_definition,
          '#field_link_suggestions' => $suggestions[$prop_name],
          '#description' => $description,
          '#is_required' => (bool) ($element['widget']['#required'] ?? FALSE),
        ];
        continue;
      }

      // Not bound yet: render the linker next to the field label.
      $element['widget']['#prop_link_data'] = [
        'linked' => FALSE,
        'prop_name' => $element['widget']['#field_name'] ?? $prop_name,
        'description' => $description,
        'suggestions' => $suggestions[$prop_name],
      ];
      $linked_any = TRUE;
    }

    // Canvas moves #prop_link_data onto the label attributes in an #after_build
    // that it only registers for content templates; without it the client never
    // sees the data and renders no linker.
    if ($linked_any) {
      $form['#after_build'][] = [
        JsonSchemaPropsComponentSourceBase::class,
        'moveSuggestionsToLabel',
      ];
    }
  }

  /**
   * Resolves the Component from the form's posted client-side tree.
   */
  private function getComponentFromForm(array $form): ?Component {
    $tree = \json_decode((string) ($form['form_canvas_tree']['#value'] ?? ''), TRUE);
    $type = \is_array($tree) ? ($tree['type'] ?? NULL) : NULL;
    if (!\is_string($type) || $type === '') {
      return NULL;
    }
    $component_id = \explode('@', $type)[0];
    $component = Component::load($component_id);
    return $component instanceof Component ? $component : NULL;
  }

  /**
   * Returns the linkable prop source for a stored input, if it is linked.
   */
  private function getLinkablePropSource(mixed $input): ?LinkablePropSourceInterface {
    if (!\is_array($input) || !isset($input['sourceType'])) {
      return NULL;
    }
    try {
      $source = PropSource::parse($input);
    }
    catch (\Throwable) {
      return NULL;
    }
    return $source instanceof LinkablePropSourceInterface ? $source : NULL;
  }

  /**
   * Returns the routed node when it has Canvas Override enabled.
   */
  private function getEnabledNode(): ?NodeInterface {
    foreach (['entity', 'node'] as $name) {
      $candidate = $this->routeMatch->getParameter($name);
      if ($candidate instanceof NodeInterface) {
        $node_type = $this->entityTypeManager->getStorage('node_type')->load($candidate->bundle());
        if ($node_type instanceof NodeTypeInterface
          && $node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
          return $candidate;
        }
        return NULL;
      }
    }
    return NULL;
  }

}
