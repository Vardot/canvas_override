@canvas-override @reset
Feature: Canvas Override - reset a layout with a confirmation
  As an editor
  I want a confirmation before a per-content Canvas layout is reset
  So that a layout is never cleared by accident

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: Confirming the reset clears the layout with a success message
    Given I am on the Canvas Override test content
    When I reset the Canvas layout and accept the confirmation
    Then the "drupal status messages" element should contain text "Canvas layout reset to the shared default template"

  Scenario: After confirming, the editor returns to the content page
    Given I am on the Canvas Override test content
    When I reset the Canvas layout and accept the confirmation
    Then the "drupal page heading" element should be visible
    And there should be no JavaScript errors

  Scenario: Dismissing the confirmation leaves the layout untouched
    Given I am on the Canvas Override test content
    When I reset the Canvas layout and dismiss the confirmation
    Then the "drupal status messages" element should have a count of 0
