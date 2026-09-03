<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Controller;

use Drupal\canvas_override\CanvasOverridePageResolver;
use Drupal\canvas_override\CanvasOverrideServiceProvider;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles Canvas editing redirects for canvas_override-enabled nodes.
 */
final class CanvasRedirectController extends ControllerBase {

  public function __construct(
    private readonly CanvasOverridePageResolver $pageResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get(CanvasOverridePageResolver::class));
  }

  /**
   * Checks that Canvas is enabled for this node's content type.
   */
  private function checkEnabled(NodeInterface $node): void {
    $node_type = $this->entityTypeManager()->getStorage('node_type')->load($node->bundle());
    if (!$node_type || !$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Redirects to the per-entity Canvas editor for this specific node.
   *
   * Edits only this node's canvas layout (stored in field_canvas_layout),
   * without affecting the shared ContentTemplate default for the content type.
   */
  public function redirectToEditor(NodeInterface $node): TrustedRedirectResponse {
    $this->checkEnabled($node);

    // On a stock Canvas the editor cannot open on a node at all:
    // ComponentTreeLoader::getCanvasFieldName() throws for anything that is
    // not a canvas_page, and that check sits inside a final class. So edit the
    // node's own backing canvas_page instead -- Canvas supports that natively,
    // and the tree is copied back onto the node when the page is saved.
    // A patched site keeps the direct per-node editor it already has.
    // @see \Drupal\canvas_override\CanvasOverridePageResolver
    // @see https://www.drupal.org/i/3620603
    if (!CanvasOverrideServiceProvider::isComponentTreeLoaderExtendable()) {
      $page = $this->pageResolver->getOrCreatePage($node);
      $url = Url::fromUri("base:canvas/editor/canvas_page/{$page->id()}")->setAbsolute()->toString();
      return new TrustedRedirectResponse($url);
    }

    $url = Url::fromUri("base:canvas/editor/node/{$node->id()}")->setAbsolute()->toString();
    return new TrustedRedirectResponse($url);
  }

}
