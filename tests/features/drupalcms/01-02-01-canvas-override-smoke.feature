@smoke @canvas-override @drupal-cms
Feature: Smoke - Canvas Override on the Drupal CMS test site
  As an anonymous visitor on a Drupal CMS site
  I want the seeded Canvas Override content to load
  So that the Canvas Override module is exercised on the Drupal CMS distribution

  Scenario: The seeded Canvas Override content loads for anonymous visitors
    Given I am on the Canvas Override test content
    Then the "drupal main content" element should be visible
    And there should be no JavaScript errors

  Scenario: The Drupal CMS home page loads without errors
    Given I am on the homepage
    Then the "drupal main content" element should be visible
    And there should be no JavaScript errors
