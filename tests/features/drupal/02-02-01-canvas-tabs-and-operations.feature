@canvas-override @admin
Feature: Canvas Override tools on a content item
  As an editor with Canvas Override rights
  I want the Canvas editor and Reset actions on the content and the content list
  So that I can reach the per-content layout tools where I work

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: The content offers the Canvas editor and Reset actions
    Given I am on the Canvas Override test content
    Then the Canvas Override tab is available
    And the Reset Canvas layout tab is available

  Scenario: The content list offers a Canvas action per item
    When I am on "/admin/content"
    Then a Canvas action is offered on the content list

  Scenario: The Canvas tools produce no errors for the visitor
    Given I am on the Canvas Override test content
    Then there should be no JavaScript errors
