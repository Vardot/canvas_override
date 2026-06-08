@canvas-override @accessibility
Feature: Canvas Override - accessibility
  As a site owner
  I want the Canvas Override surfaces to be accessible
  So that every editor can manage per-content layouts

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: The content type form has no serious accessibility violations
    When I am on "/admin/structure/types/manage/marketing_campaign"
    Then the page should have exactly one h1
    And every image should have an alt attribute
    And the page should have no serious accessibility violations

  Scenario: The content with Canvas Override tabs is accessible
    Given I am on the Canvas Override test content
    Then the page should have a main landmark
    And every link should have an accessible name
    And the page should have no serious accessibility violations
