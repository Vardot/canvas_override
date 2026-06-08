@canvas-override @drupal-cms @admin @permissions
Feature: Canvas Override - permissions registration on Drupal CMS
  As a Drupal CMS administrator
  I want the Canvas Override permissions listed on the Gin permissions page
  So that I can grant per-content-type layout rights to roles

  Background:
    Given I am a logged in user with the "Webmaster" user
    And I am on "/admin/people/permissions"

  Scenario: The global Canvas Override permissions are registered
    Then the "permission administer canvas override row" element should have a count of at least 1
    And the "permission use canvas override row" element should have a count of at least 1

  Scenario: A per-bundle Canvas Override permission is generated for enabled types
    Then the "permission per bundle use canvas override row" element should have a count of at least 1
