@canvas-override @drupal-cms @admin
Feature: Canvas Override - content tabs on Drupal CMS
  As a Drupal CMS content editor with Canvas Override rights
  I want the Canvas Override and Reset tabs on enabled content
  So that I can reach the per-content layout tools

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: The Canvas Override and Reset tabs render on enabled content
    Given I am on the Canvas Override test content
    Then the "node primary tabs" element should be visible
    And the "node canvas override tab" element should have a count of 1
    And the "node canvas reset tab" element should have a count of 1

  Scenario: The content list exposes a Canvas operation link
    When I am on "/admin/content"
    Then the "content canvas operation link" element should have a count of at least 1
