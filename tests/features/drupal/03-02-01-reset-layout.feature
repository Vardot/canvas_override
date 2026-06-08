@canvas-override @reset
Feature: Canvas Override - reset per-content layout
  As a content editor
  I want the Reset Canvas layout tab to clear a per-content layout
  So that the content falls back to the shared default template

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: Resetting a layout redirects back with a success message
    Given I am on the Canvas Override test content
    When I click the "node canvas reset tab" element
    Then the "drupal status messages" element should contain text "Canvas layout reset to the shared default template"

  Scenario: The reset action returns the visitor to the content page
    Given I am on the Canvas Override test content
    When I click the "node canvas reset tab" element
    Then the "drupal page heading" element should be visible
    And there should be no JavaScript errors
