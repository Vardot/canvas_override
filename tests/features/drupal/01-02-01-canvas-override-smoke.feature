@smoke @canvas-override
Feature: Smoke - the Canvas Override test site loads
  As an anonymous visitor
  I want the seeded Canvas Override content to load
  So that the Canvas Override module can be exercised

  Scenario: The seeded Canvas Override campaign loads for anonymous visitors
    Given I am on the Canvas Override test content
    Then the "drupal main content" element should be visible
    And there should be no JavaScript errors

  Scenario: The home page loads without errors
    Given I am on the homepage
    Then the "drupal main content" element should be visible
    And there should be no JavaScript errors
