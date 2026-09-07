<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Form;

use Drupal\canvas\AutoSave\AutoSaveManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * No-JavaScript fallback confirmation for resetting a Canvas layout.
 *
 * With JavaScript the "Reset Canvas layout" local task confirms and resets
 * through HTMX (hx-confirm + a POST to CanvasResetController, see
 * CanvasOverrideHooks::menuLocalTasksAlter()). Without JavaScript the link
 * opens this standard confirmation page instead, so the action always runs
 * through an explicit confirmation and a POST with a CSRF token.
 */
final class CanvasResetConfirmForm extends ConfirmFormBase {

  /**
   * The node whose Canvas layout will be reset.
   */
  protected ?NodeInterface $node = NULL;

  /**
   * The human-readable content type label of the node.
   */
  protected string $bundleLabel = '';

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'canvas_override_reset_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): string {
    return (string) $this->t('Reset the Canvas layout?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Removes the custom layout for %title and restores the default %type layout. This cannot be undone.', [
      '%title' => $this->node?->label() ?? '',
      '%type' => $this->bundleLabel,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): string {
    return (string) $this->t('Reset to default layout');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): string {
    return (string) $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('entity.node.canonical', ['node' => $this->node?->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL): array {
    $node_type = $node ? $this->entityTypeManager->getStorage('node_type')->load($node->bundle()) : NULL;
    if (!$node instanceof NodeInterface || !$node_type instanceof NodeTypeInterface || !$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      throw new NotFoundHttpException();
    }
    $this->node = $node;
    $this->bundleLabel = (string) $node_type->label();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($this->node && $this->node->hasField(\CANVAS_OVERRIDE_FIELD_NAME)) {
      $this->node->set(\CANVAS_OVERRIDE_FIELD_NAME, NULL);
      // Record the reset as its own revision so the previous layout stays in
      // the node's revision history instead of being overwritten in place.
      $this->node->setNewRevision(TRUE);
      $this->node->setRevisionLogMessage('Canvas layout reset to the shared default template.');
      $this->node->setRevisionUserId((int) $this->currentUser()->id());
      $this->node->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $this->node->save();
      // Drop any pending Canvas auto-save: it holds a full snapshot of the
      // node including the old layout, so publishing it later would silently
      // restore what was just reset.
      \Drupal::service(AutoSaveManager::class)->delete($this->node);
      $this->messenger()->addStatus($this->t('Canvas layout reset to the shared default template.'));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
