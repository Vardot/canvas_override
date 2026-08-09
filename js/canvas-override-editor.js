/**
 * @file
 * Hides the Canvas editor's Page data panel on Canvas Override nodes.
 *
 * A Canvas Override node keeps its field data on the standard Drupal edit
 * form, so the editor's Page data tab is not meaningful there. Drupal Canvas
 * ships its editor as a prebuilt bundle, so this runs alongside that bundle
 * instead of being compiled into it.
 */

((Drupal, drupalSettings, once) => {
  'use strict';

  const TRIGGER = '[data-testid="canvas-contextual-panel--page-data"]';
  const HIDDEN_CLASS = 'canvas-override-hidden';

  /**
   * Whether the Page data panel should be hidden for this request.
   *
   * @return {boolean}
   *   TRUE when the editor is showing a Canvas Override enabled node.
   */
  function shouldHide() {
    return (
      drupalSettings.canvas !== undefined &&
      drupalSettings.canvas.hidePageDataPanel === true
    );
  }

  /**
   * Hides the Page data tab and moves selection off it when it is active.
   */
  function hidePageData() {
    document.querySelectorAll(TRIGGER).forEach((trigger) => {
      trigger.classList.add(HIDDEN_CLASS);
      trigger.setAttribute('hidden', 'hidden');

      // The editor mounts with Page data selected. Move to the next available
      // tab so the editor does not open on a panel that is not there.
      if (trigger.getAttribute('data-state') !== 'active') {
        return;
      }
      const list = trigger.closest('[role="tablist"]');
      const next = list
        ? [...list.querySelectorAll('[role="tab"]')].find(
            (tab) => tab !== trigger && !tab.hasAttribute('hidden'),
          )
        : null;
      if (next) {
        next.click();
      }
    });
  }

  Drupal.behaviors.canvasOverrideEditor = {
    attach(context) {
      if (!shouldHide()) {
        return;
      }

      // The editor renders after this behaviour runs and re-renders as the
      // user works, so watch for the tab reappearing rather than running once.
      once('canvas-override-editor', 'body', context).forEach((body) => {
        hidePageData();
        new MutationObserver(hidePageData).observe(body, {
          childList: true,
          subtree: true,
        });
      });
    },
  };
})(Drupal, drupalSettings, once);
