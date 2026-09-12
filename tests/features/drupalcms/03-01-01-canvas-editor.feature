@canvas-override @drupal-cms @canvas @editor
Feature: Canvas Override - per-content Canvas editor on Drupal CMS
  As a Drupal CMS content editor
  I want the Canvas Override tab to open the per-content Canvas editor
  So that I can compose a unique layout for this content

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: The Canvas Override tab redirects to the per-content editor
    Given I am on the Canvas Override test content
    When I click the "node canvas override tab" element
    Then the url should match "/canvas/editor/node/\d+"

  @wip
  Scenario: The Canvas editor mounts for a Canvas Override-enabled node
    # On Drupal CMS the Canvas editor data layer (RTK Query) errors until the
    # running drupal/canvas build carries the full #3567225 change, so the SPA
    # never reaches network-idle. The redirect scenario above already proves
    # the tab opens the per-content editor route; tagged @wip (excluded by
    # "not @wip") until the patched Canvas release ships.
    Given I am on the Canvas Override test content
    When I click the "node canvas override tab" element
    Then the "canvas editor frame" element should be attached within 30 seconds
    And the "canvas contextual panel" element should be attached within 30 seconds

  @wip
  Scenario: The Page data panel is hidden in Canvas Override mode
    # See the Drupal Standard editor feature - the hide is performed by the
    # Canvas React app and needs the #3567225 front-end change in the running
    # drupal/canvas build. Tagged @wip (excluded by "not @wip") until then.
    Given I am on the Canvas Override test content
    When I click the "node canvas override tab" element
    Then the "canvas page data panel" element should have a count of 0

  @canvas-override @editor @props
  Scenario: Editing a component's props saves without a server error
    # Smoke cover for the editor's save path: changing component settings must
    # not produce a failed Canvas layout API call.
    #
    # This is deliberately NOT the regression guard for the host-entity fix
    # (issue #3621557). That bug only fires for a component whose props carry
    # an entity-field prop source, and which component that is depends on the
    # site's ContentTemplate for the bundle; driving the canvas overlays from
    # the outside does not reliably land on one, so this scenario was verified
    # to still pass with the fix removed. The precise guard is the unit test
    # CanvasOverridePreviewEntitySubscriberTest, which asserts the node is
    # offered to Canvas as its own host entity.
    # @see https://www.drupal.org/project/canvas_override/issues/3621557
    Given I watch the Canvas layout API calls
    And I am on the Canvas Override test content
    When I click the "node canvas override tab" element
    And the "canvas contextual panel" element should be attached within 30 seconds
    And I edit the first component's settings if the layout has any
    Then no Canvas layout API call should have failed
