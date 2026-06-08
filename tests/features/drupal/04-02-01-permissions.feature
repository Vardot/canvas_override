@canvas-override @admin @permissions
Feature: Canvas Override - permissions registration
  As a site administrator
  I want the Canvas Override permissions listed on the permissions page
  So that I can grant per-content-type layout rights to roles

  Background:
    Given I am a logged in user with the "Webmaster" user
    And I am on "/admin/people/permissions"

  Scenario: The global Canvas Override permissions are registered
    Then the "permission administer canvas override row" element should have a count of at least 1
    And the "permission use canvas override row" element should have a count of at least 1
    And the "permission edit canvas default template row" element should have a count of at least 1
    And the "permission reset canvas layout row" element should have a count of at least 1

  Scenario: A per-bundle Canvas Override permission is generated for enabled types
    # The CI before_script enables Canvas Override on the Marketing campaign
    # type, generating "Marketing campaign: Use Canvas Override" and related
    # per-bundle permissions.
    Then the "permission per bundle use canvas override row" element should have a count of at least 1
    And the "permission per bundle reset canvas layout row" element should have a count of at least 1
