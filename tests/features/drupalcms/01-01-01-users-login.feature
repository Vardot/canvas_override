@setup @canvas-override @drupal-cms
Feature: Provision the Canvas Override Drupal CMS test site
  As the site owner of a Drupal CMS install
  I want the seeded campaign and one test user per role
  So that the editor, reset and access-control scenarios have what they expect

  # The CI before_script installs Drupal CMS, applies the
  # tests/fixtures/marketing_campaign recipe (enables canvas + canvas_override,
  # creates the Marketing campaign content type with Canvas Override and its
  # default layout) and renames uid 1 to "webmaster". This setup scenario
  # creates the seeded campaign node through the browser and provisions users.

  Scenario: The Webmaster seeds the campaign and provisions users
    Given I am a logged in user with the "Webmaster" user
    And I create the Canvas Override test campaign
    And I add testing users
    When I am on the Canvas Override test content
    Then the "drupal main content" element should be visible
