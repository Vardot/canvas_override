@canvas-override @drupal-cms @access
Feature: Canvas Override access on Drupal CMS
  As a site owner
  I want each Canvas Override permission level to reach exactly the right tools
  So that only the right people can change a content item's layout

  # A representative check on Drupal CMS; the full per-role matrix runs on
  # Drupal Core.

  Scenario: The Webmaster can open the Canvas editor and reset the layout
    Given I am a logged in user with the "Webmaster" user
    And I am on the Canvas Override test content
    Then the Canvas Override tab is available
    And the Reset Canvas layout tab is available

  Scenario: A user allowed on this content type can open the Canvas editor
    Given I am a logged in user with the "Canvas per-bundle" user
    And I am on the Canvas Override test content
    Then the Canvas Override tab is available

  Scenario: A reset-only user can reset the layout but cannot open the Canvas editor
    Given I am a logged in user with the "Canvas reset" user
    And I am on the Canvas Override test content
    Then the Reset Canvas layout tab is available
    And the Canvas Override tab is not available

  Scenario: A regular authenticated user gets no Canvas tools
    Given I am a logged in user with the "Authenticated user" user
    And I am on the Canvas Override test content
    Then the Canvas Override tab is not available
    And the Reset Canvas layout tab is not available

  Scenario: A regular authenticated user is blocked from the Canvas editor link
    Given I am a logged in user with the "Authenticated user" user
    When I go to the Canvas Override test content "canvas" route
    Then I should see "Access denied"
