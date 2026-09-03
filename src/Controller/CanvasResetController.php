<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * HTMX endpoint that resets a node's per-content Canvas layout.
 *
 * The "Reset Canvas layout" local task posts here through HTMX after the user
 * accepts the hx-confirm prompt (see CanvasOverrideHooks::menuLocalTasksAlter()).
 * The reset runs, a status message is set, and an HX-Redirect header sends the
 * browser back to the content - so there is no dialog chrome to theme. Without
 * JavaScript the local task instead opens the standalone confirmation form
 * (CanvasResetConfirmForm).
 */
final class CanvasResetController extends ControllerBase {

  /**
   * Clears the per-content Canvas layout and redirects back to the content.
   */
  public function reset(NodeInterface $node): Response {
    $node_type = $this->entityTypeManager()->getStorage('node_type')->load($node->bundle());
    if (!$node_type instanceof NodeTypeInterface || !$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      throw new NotFoundHttpException();
    }

    if ($node->hasField(\CANVAS_OVERRIDE_FIELD_NAME)) {
      $node->set(\CANVAS_OVERRIDE_FIELD_NAME, NULL);
      $node->save();
      $this->messenger()->addStatus($this->t('Canvas layout reset to the shared default template.'));
    }

    // Tell HTMX to navigate the browser to the content, where the status
    // message renders on the next page load.
    $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString();
    $response = new Response('', Response::HTTP_NO_CONTENT);
    $response->headers->set('HX-Redirect', $url);
    return $response;
  }

}
