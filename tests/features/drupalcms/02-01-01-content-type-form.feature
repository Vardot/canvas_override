@canvas-override @drupal-cms @admin @form
Feature: Canvas Override - content type form on Drupal CMS
  As a Drupal CMS administrator setting up the marketing team
  I want the "Canvas layout" setting on the Marketing campaign content type form
  So that the team gets a Canvas full-content layout they can override per campaign

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: The Canvas layout fieldset appears on the content type form
    When I am on "/admin/structure/types/manage/marketing_campaign"
    Then the "canvas override fieldset" element should be attached
    And the "canvas override enable checkbox" element should be attached

  Scenario: The enable checkbox is checked for the Marketing campaign type
    When I am on "/admin/structure/types/manage/marketing_campaign"
    Then the "canvas override enable checkbox checked" element should have a count of 1

  Scenario: The Gin admin theme renders the content type form
    When I am on "/admin/structure/types/manage/marketing_campaign"
    Then the "gin admin form" element should have a count of 1
    And there should be no JavaScript errors

  Scenario: Enabling Canvas Override on a content type creates the layout field
    # The "Department" type ships from the department_test_base recipe with
    # Canvas Override NOT activated, so this exercises turning it on via Gin.
    Given I enable Canvas Override on the "department" content type
    Then the "drupal status messages" element should be visible
    When I am on "/admin/structure/types/manage/department/fields"
    Then the "drupal main content" element should contain text "Canvas Layout"
