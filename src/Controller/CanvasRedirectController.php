<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles Canvas editing redirects for canvas_override-enabled nodes.
 */
final class CanvasRedirectController extends ControllerBase {

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

    $url = Url::fromUri("base:canvas/editor/node/{$node->id()}")->setAbsolute()->toString();
    return new TrustedRedirectResponse($url);
  }

  /**
   * Redirects to the ContentTemplate editor for this node's content type.
   *
   * Edits the shared default layout used by all nodes of this content type
   * that do not have a per-node canvas override.
   */
  public function redirectToTemplate(NodeInterface $node): TrustedRedirectResponse {
    $this->checkEnabled($node);

    $bundle = $node->bundle();
    $url = Url::fromUri("base:canvas/template/node/{$bundle}/full/{$node->id()}")->setAbsolute()->toString();
    return new TrustedRedirectResponse($url);
  }

}
