<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resets a node's per-node Canvas layout, reverting it to the shared default.
 */
final class CanvasResetController extends ControllerBase {

  /**
   * Clears the per-node canvas layout and redirects to the node.
   */
  public function reset(NodeInterface $node): RedirectResponse {
    $bundle = $node->bundle();

    $node_type = $this->entityTypeManager()->getStorage('node_type')->load($bundle);
    if (!$node_type || !$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      throw new NotFoundHttpException();
    }

    if ($node->hasField(\CANVAS_OVERRIDE_FIELD_NAME)) {
      $node->set(\CANVAS_OVERRIDE_FIELD_NAME, NULL);
      $node->save();
      $this->messenger()->addStatus($this->t('Canvas layout reset to the shared default template.'));
    }

    $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString();
    return new RedirectResponse($url);
  }

}
