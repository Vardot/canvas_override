@setup @canvas-override
Feature: Provision the Canvas Override test site
  As the site owner setting up the marketing team
  I want Canvas Override enabled on the Marketing campaign type and a user per role
  So that the editor, reset and access-control scenarios have what they expect

  # The CI before_script installs Drupal and applies three recipes:
  #   - tests/fixtures/marketing_campaign_test_base  -> the ready "Marketing campaign"
  #     type with Canvas Override already activated and its default layout;
  #   - tests/fixtures/department_test_base   -> a plain "Design department" type with
  #     Canvas Override NOT activated, for the enable-through-the-form test;
  #   - tests/fixtures/canvas_override_roles_test_base -> one role per Canvas
  #     Override permission tier, for the access matrix.
  # This setup scenario creates the seeded campaign node through the browser
  # and provisions one testing user per role (incl. the permission tiers).

  Scenario: The Webmaster seeds the campaign and provisions users
    Given I am a logged in user with the "Webmaster" user
    And I create the Canvas Override test campaign
    And I add testing users
    When I am on the Canvas Override test content
    Then the "drupal main content" element should be visible
