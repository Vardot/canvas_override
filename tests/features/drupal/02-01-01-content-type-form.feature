@canvas-override @admin @form
Feature: Canvas Override - content type form integration
  As a site administrator
  I want a "Canvas layout" setting on the content type form
  So that I can enable per-content Canvas layout editing for a content type

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: The Canvas layout fieldset appears on the content type form
    When I am on "/admin/structure/types/manage/marketing_campaign"
    Then the "canvas override fieldset" element should be attached
    And the "canvas override enable checkbox" element should be attached

  Scenario: The enable checkbox is checked for a Canvas Override-enabled type
    # The CI before_script enables Canvas Override on the custom
    # "Marketing campaign" content type.
    When I am on "/admin/structure/types/manage/marketing_campaign"
    Then the "canvas override enable checkbox checked" element should have a count of 1

  Scenario: Enabling Canvas Override on a content type creates the layout field
    # The "Design department" type ships from the department_test_base recipe with
    # Canvas Override NOT activated, so this exercises turning it on.
    Given I enable Canvas Override on the "department" content type
    Then the "drupal status messages" element should be visible
    And the "drupal error messages" element should have a count of 0
    When I am on "/admin/structure/types/manage/department/fields"
    Then the "drupal main content" element should contain text "Canvas Layout"

  Scenario: No JavaScript errors on the content type form
    When I am on "/admin/structure/types/manage/marketing_campaign"
    Then there should be no JavaScript errors
