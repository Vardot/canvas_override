@canvas-override @access @routes
Feature: Canvas Override blocks the editor and reset links directly
  As a site owner
  I want unauthorised people blocked even if they guess the editor or reset link
  So that hiding a tab is backed by real protection, not just a hidden button

  # Opening the Canvas editor link or the Reset link directly must show an
  # "Access denied" page to anyone without the right permission.

  # --- The Canvas editor link ---

  Scenario: The Webmaster can open the Canvas editor link
    Given I am a logged in user with the "Webmaster" user
    When I go to the Canvas Override test content "canvas" route
    Then I should not see "Access denied"

  Scenario: A user allowed on this content type can open the Canvas editor link
    Given I am a logged in user with the "Canvas per-bundle" user
    When I go to the Canvas Override test content "canvas" route
    Then I should not see "Access denied"

  Scenario: A reset-only user is blocked from the Canvas editor link
    Given I am a logged in user with the "Canvas reset" user
    When I go to the Canvas Override test content "canvas" route
    Then I should see "Access denied"

  Scenario: A content editor is blocked from the Canvas editor link
    Given I am a logged in user with the "Content editor" user
    When I go to the Canvas Override test content "canvas" route
    Then I should see "Access denied"

  Scenario: A regular authenticated user is blocked from the Canvas editor link
    Given I am a logged in user with the "Authenticated user" user
    When I go to the Canvas Override test content "canvas" route
    Then I should see "Access denied"

  # --- The Reset layout link ---

  Scenario: A reset-only user can open the Reset link
    Given I am a logged in user with the "Canvas reset" user
    When I go to the Canvas Override test content "canvas/reset" route
    Then I should not see "Access denied"

  Scenario: A template-only user is blocked from the Reset link
    Given I am a logged in user with the "Canvas template" user
    When I go to the Canvas Override test content "canvas/reset" route
    Then I should see "Access denied"

  Scenario: A content editor is blocked from the Reset link
    Given I am a logged in user with the "Content editor" user
    When I go to the Canvas Override test content "canvas/reset" route
    Then I should see "Access denied"

  Scenario: A regular authenticated user is blocked from the Reset link
    Given I am a logged in user with the "Authenticated user" user
    When I go to the Canvas Override test content "canvas/reset" route
    Then I should see "Access denied"
