<?php

declare(strict_types=1);

namespace Drupal\canvas_override\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\RouteObjectInterface;

/**
 * Makes a node act as its own host entity for Canvas API requests.
 *
 * Canvas resolves the host entity used to save component inputs from the
 * route's `preview_entity` parameter, which only exists on the ContentTemplate
 * routes. When a node's own component tree is edited there is no preview
 * entity, so ApiLayoutController passes NULL and
 * JsonSchemaPropsComponentSourceBase::clientModelToInput() throws
 * "A host entity is required to set entity field prop sources." — an HTTP 500
 * on PATCH /canvas/api/v0/layout/node/{node}.
 *
 * That breaks editing for every component Canvas Override seeds from the
 * content type's ContentTemplate, because those carry entity-field prop
 * sources bound to the node's own fields: changing any prop on such a
 * component (even an unrelated one) fails and the change is lost.
 *
 * For a per-content layout the host entity is simply the node itself, so this
 * subscriber sets it as the `preview_entity` request attribute for Canvas API
 * requests on canvas_override-enabled nodes. Canvas then resolves the bindings
 * against the node and saves normally.
 *
 * Safe for the paths involved: ApiLayoutController ignores the preview entity
 * when rendering a fieldable entity's own tree, and only asserts a NULL preview
 * entity on the config-entity (pattern) branch, which a node never reaches.
 *
 * @see \Drupal\canvas\Controller\ApiLayoutController::patch()
 * @see \Drupal\canvas\Plugin\Canvas\ComponentSource\JsonSchemaPropsComponentSourceBase::clientModelToInput()
 * @see https://www.drupal.org/project/canvas_override/issues/3621557
 */
final class CanvasOverridePreviewEntitySubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Sets the node as its own host entity on Canvas API requests.
   */
  public function onRequest(RequestEvent $event): void {
    $request = $event->getRequest();

    $route_name = (string) $request->attributes->get(RouteObjectInterface::ROUTE_NAME);
    if (!\str_starts_with($route_name, 'canvas.api.')) {
      return;
    }

    // Never override a preview entity Canvas resolved itself (ContentTemplate
    // routes carry a real one).
    if ($request->attributes->get('preview_entity') !== NULL) {
      return;
    }

    $entity = $request->attributes->get('entity');
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

    $request->attributes->set('preview_entity', $entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // After routing (and its parameter upcasting) has populated `entity`, and
    // before the controller arguments are resolved.
    return [KernelEvents::REQUEST => [['onRequest', 20]]];
  }

}
