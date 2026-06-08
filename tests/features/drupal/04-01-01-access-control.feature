@canvas-override @access
Feature: Canvas Override access for each permission level
  As a site owner
  I want each Canvas Override permission level to reach exactly the right tools
  So that only the right people can change a content item's layout

  # Who can do what, by role:
  #
  #   Role               Open the Canvas editor   Reset the layout
  #   -----------------  ----------------------   ----------------
  #   Webmaster          yes                      yes
  #   Canvas admin       yes                      yes
  #   Canvas global      yes                      yes
  #   Canvas per-bundle  yes                      yes
  #   Canvas reset       no                       yes
  #   Canvas template    no                       no
  #   Content editor     no                       no
  #   Authenticated      no                       no

  # --- Who can open the Canvas editor (the Canvas Override tab) ---

  Scenario: The Webmaster can open the Canvas editor
    Given I am a logged in user with the "Webmaster" user
    And I am on the Canvas Override test content
    Then the Canvas Override tab is available

  Scenario: A Canvas administrator can open the Canvas editor
    Given I am a logged in user with the "Canvas admin" user
    And I am on the Canvas Override test content
    Then the Canvas Override tab is available

  Scenario: A user allowed on all content types can open the Canvas editor
    Given I am a logged in user with the "Canvas global" user
    And I am on the Canvas Override test content
    Then the Canvas Override tab is available

  Scenario: A user allowed on this content type can open the Canvas editor
    Given I am a logged in user with the "Canvas per-bundle" user
    And I am on the Canvas Override test content
    Then the Canvas Override tab is available

  Scenario: A user who may only reset cannot open the Canvas editor
    Given I am a logged in user with the "Canvas reset" user
    And I am on the Canvas Override test content
    Then the Canvas Override tab is not available

  Scenario: A user who may only edit the default template cannot open the Canvas editor
    Given I am a logged in user with the "Canvas template" user
    And I am on the Canvas Override test content
    Then the Canvas Override tab is not available

  Scenario: A content editor cannot open the Canvas editor
    Given I am a logged in user with the "Content editor" user
    And I am on the Canvas Override test content
    Then the Canvas Override tab is not available

  Scenario: A regular authenticated user cannot open the Canvas editor
    Given I am a logged in user with the "Authenticated user" user
    And I am on the Canvas Override test content
    Then the Canvas Override tab is not available

  # --- Who can reset a layout (the Reset Canvas layout tab) ---

  Scenario: The Webmaster can reset the layout
    Given I am a logged in user with the "Webmaster" user
    And I am on the Canvas Override test content
    Then the Reset Canvas layout tab is available

  Scenario: A user allowed on all content types can reset the layout
    Given I am a logged in user with the "Canvas global" user
    And I am on the Canvas Override test content
    Then the Reset Canvas layout tab is available

  Scenario: A reset-only user can reset the layout but cannot open the Canvas editor
    Given I am a logged in user with the "Canvas reset" user
    And I am on the Canvas Override test content
    Then the Reset Canvas layout tab is available
    And the Canvas Override tab is not available

  Scenario: A template-only user cannot reset the layout
    Given I am a logged in user with the "Canvas template" user
    And I am on the Canvas Override test content
    Then the Reset Canvas layout tab is not available

  Scenario: A regular authenticated user cannot reset the layout
    Given I am a logged in user with the "Authenticated user" user
    And I am on the Canvas Override test content
    Then the Reset Canvas layout tab is not available
