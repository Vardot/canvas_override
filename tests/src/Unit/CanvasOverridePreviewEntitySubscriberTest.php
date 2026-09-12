<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\Unit;

use Drupal\canvas_override\EventSubscriber\CanvasOverridePreviewEntitySubscriber;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Guards that a node is offered to Canvas as its own host entity.
 *
 * Regression cover for
 * https://www.drupal.org/project/canvas_override/issues/3621557: Canvas takes
 * the host entity used to save component inputs from the route's
 * `preview_entity`, which only exists on its ContentTemplate routes. Without
 * one, saving a component whose props carry entity-field prop sources — every
 * component Canvas Override seeds from the content type's ContentTemplate —
 * fails with "A host entity is required to set entity field prop sources", an
 * HTTP 500 on PATCH /canvas/api/v0/layout/node/{node}, and the editor silently
 * discards the change.
 *
 * These assertions fail if the attribute stops being set for Canvas API
 * requests on enabled nodes, or if it ever starts overriding a preview entity
 * Canvas resolved itself.
 *
 * @group canvas_override
 *
 * @coversDefaultClass \Drupal\canvas_override\EventSubscriber\CanvasOverridePreviewEntitySubscriber
 */
class CanvasOverridePreviewEntitySubscriberTest extends UnitTestCase {

  /**
   * Builds a subscriber whose node type reports the given enabled state.
   *
   * @param bool|null $enabled
   *   The canvas_override third-party setting, or NULL for no node type.
   */
  private function subscriber(?bool $enabled): CanvasOverridePreviewEntitySubscriber {
    $storage = $this->createMock(EntityStorageInterface::class);
    if ($enabled === NULL) {
      $storage->method('load')->willReturn(NULL);
    }
    else {
      $node_type = $this->createMock(NodeTypeInterface::class);
      $node_type->method('getThirdPartySetting')
        ->with('canvas_override', 'enabled', FALSE)
        ->willReturn($enabled);
      $storage->method('load')->willReturn($node_type);
    }

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node_type')->willReturn($storage);

    return new CanvasOverridePreviewEntitySubscriber($entity_type_manager);
  }

  /**
   * Builds a request event for a route, entity and existing preview entity.
   */
  private function event(string $route, mixed $entity, mixed $preview_entity = NULL): RequestEvent {
    $request = new Request();
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, $route);
    if ($entity !== NULL) {
      $request->attributes->set('entity', $entity);
    }
    if ($preview_entity !== NULL) {
      $request->attributes->set('preview_entity', $preview_entity);
    }

    return new RequestEvent(
      $this->createMock(HttpKernelInterface::class),
      $request,
      HttpKernelInterface::MAIN_REQUEST,
    );
  }

  /**
   * Returns a node mock of the given bundle.
   */
  private function node(string $bundle = 'article'): NodeInterface {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn($bundle);
    return $node;
  }

  /**
   * The node becomes the host entity on Canvas API routes when enabled.
   *
   * @covers ::onRequest
   */
  public function testSetsNodeAsPreviewEntity(): void {
    $node = $this->node();
    $event = $this->event('canvas.api.layout.patch', $node);

    $this->subscriber(TRUE)->onRequest($event);

    $this->assertSame($node, $event->getRequest()->attributes->get('preview_entity'));
  }

  /**
   * Nothing happens for a bundle that does not use Canvas Override.
   *
   * @covers ::onRequest
   */
  public function testIgnoresDisabledBundle(): void {
    $event = $this->event('canvas.api.layout.patch', $this->node());

    $this->subscriber(FALSE)->onRequest($event);

    $this->assertNull($event->getRequest()->attributes->get('preview_entity'));
  }

  /**
   * Only Canvas API routes are touched.
   *
   * @covers ::onRequest
   */
  public function testIgnoresNonCanvasApiRoute(): void {
    $event = $this->event('entity.node.canonical', $this->node());

    $this->subscriber(TRUE)->onRequest($event);

    $this->assertNull($event->getRequest()->attributes->get('preview_entity'));
  }

  /**
   * A preview entity Canvas resolved itself is never replaced.
   *
   * Content template editing carries a real preview entity; overriding it would
   * resolve every binding against the wrong entity.
   *
   * @covers ::onRequest
   */
  public function testDoesNotOverrideExistingPreviewEntity(): void {
    $existing = $this->node('page');
    $event = $this->event('canvas.api.layout.patch', $this->node(), $existing);

    $this->subscriber(TRUE)->onRequest($event);

    $this->assertSame($existing, $event->getRequest()->attributes->get('preview_entity'));
  }

  /**
   * Requests that route to something other than a node are left alone.
   *
   * @covers ::onRequest
   */
  public function testIgnoresNonNodeEntity(): void {
    $event = $this->event('canvas.api.layout.patch', new \stdClass());

    $this->subscriber(TRUE)->onRequest($event);

    $this->assertNull($event->getRequest()->attributes->get('preview_entity'));
  }

  /**
   * The subscriber runs after routing has upcast the entity.
   *
   * @covers ::getSubscribedEvents
   */
  public function testSubscribesToRequestAfterRouting(): void {
    $events = CanvasOverridePreviewEntitySubscriber::getSubscribedEvents();

    $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
    [$method, $priority] = $events[KernelEvents::REQUEST][0];
    $this->assertSame('onRequest', $method);
    // Below the router (32) so `entity` is populated, above controller
    // argument resolution so the attribute is picked up.
    $this->assertLessThan(32, $priority);
  }

}
