@canvas-override @admin @config-import
Feature: Canvas Override - config import of an inline-enabled content type
  As a distribution or recipe author
  I want to ship a content type with Canvas Override enabled inside its own config
  So that importing it activates the per-content Canvas layout without errors

  # Regression cover for
  # https://www.drupal.org/project/canvas_override/issues/3616302
  #
  # The CI before_script applies tests/fixtures/canvas_override_inline_import_test_base,
  # a recipe whose node.type.inline_campaign config carries the canvas_override
  # "enabled" third-party setting INLINE (not via a post-import config action).
  # With the pre-fix node_type_presave hook that import aborts with
  # "Missing bundle entity", so the recipe apply - and this whole job - fails
  # before the browser even starts. These scenarios then prove the imported type
  # is genuinely Canvas Override-enabled and carries the Canvas Layout field.

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: The inline-imported type is Canvas Override-enabled
    When I am on "/admin/structure/types/manage/inline_campaign"
    Then the "canvas override fieldset" element should be attached
    And the "canvas override enable checkbox checked" element should have a count of 1

  Scenario: The config import created the Canvas Layout field on the bundle
    When I am on "/admin/structure/types/manage/inline_campaign/fields"
    Then the "drupal error messages" element should have a count of 0
    And the "drupal main content" element should contain text "Canvas Layout"
