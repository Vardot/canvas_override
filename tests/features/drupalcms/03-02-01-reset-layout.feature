@canvas-override @drupal-cms @reset
Feature: Canvas Override - reset a layout with a confirmation on Drupal CMS
  As a Drupal CMS editor
  I want a confirmation before a per-content Canvas layout is reset
  So that a layout is never cleared by accident

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: Confirming the reset clears the layout with a success message
    Given I am on the Canvas Override test content
    When I reset the Canvas layout and accept the confirmation
    Then the "drupal status messages" element should contain text "Canvas layout reset to the shared default template"
