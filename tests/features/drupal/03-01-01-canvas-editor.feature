@canvas-override @canvas @editor
Feature: Canvas Override - per-content Canvas editor
  As a marketing team member
  I want the Canvas Override tab to open the per-campaign Canvas editor
  So that I can add extra blocks, sections or a webform to a selected campaign
  on top of the shared full-content layout

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: The Canvas Override tab redirects to the per-content editor
    Given I am on the Canvas Override test content
    When I click the "node canvas override tab" element
    Then the url should match "/canvas/editor/node/\d+"

  Scenario: The Canvas editor mounts for a Canvas Override-enabled node
    Given I am on the Canvas Override test content
    When I click the "node canvas override tab" element
    Then the "canvas editor frame" element should be attached within 30 seconds
    And the "canvas side menu" element should be attached within 30 seconds
    And the "canvas topbar" element should be attached within 30 seconds
    And the "canvas contextual panel" element should be attached within 30 seconds

  @wip
  Scenario: The Page data panel is hidden in Canvas Override mode
    # canvas_override sets drupalSettings.canvas.hidePageDataPanel = TRUE so
    # field data is edited through the standard node form, not the editor.
    # The hide is performed by the Canvas React app and therefore only takes
    # effect when the running drupal/canvas build carries the matching
    # #3567225 front-end change. Tagged @wip (excluded by "not @wip") until the
    # patch front-end hunks land in the pinned Canvas release.
    Given I am on the Canvas Override test content
    When I click the "node canvas override tab" element
    Then the "canvas primary panel" element should be attached within 30 seconds
    And the "canvas page data panel" element should have a count of 0
