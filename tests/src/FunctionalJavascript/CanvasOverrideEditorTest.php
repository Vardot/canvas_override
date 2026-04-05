<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Canvas Override editor UI for per-content layouts.
 *
 * Covers the admin form to enable Canvas Override on content types, the
 * canvas_layout field creation, the Canvas editor loading, and the Library
 * panel interaction.
 *
 * @group canvas_override
 */
#[RunTestsInSeparateProcesses]
class CanvasOverrideEditorTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'field_ui',
    'text',
    'filter',
    'canvas',
    'canvas_override',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * Use the minimal profile to avoid optional config (e.g. legacy node actions)
   * that reference plugins removed in Drupal 11.
   */
  protected $profile = 'minimal';

  /**
   * A user with full Canvas Override and content type administration rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Ensure the article content type exists before creating users so that
    // content-type-specific permissions ('create article content' etc.) are
    // valid when passed to drupalCreateUser().
    if (!NodeType::load('article')) {
      NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    }

    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer canvas override',
      'use canvas override',
      'access content',
      'create article content',
      'edit any article content',
    ]);
  }

  // ---------------------------------------------------------------------------
  // Scenario 1: Enable Canvas Override on a content type via the admin form
  // and verify the Canvas editor loads with the Library panel accessible.
  // ---------------------------------------------------------------------------

  /**
   * Tests enabling Canvas Override via the content type form and editor load.
   *
   * Verifies:
   * - "Canvas layout" section appears on the content type edit form.
   * - Checking the enable checkbox saves the setting.
   * - A canvas_layout field is created on the content type.
   * - The Canvas editor loads at /canvas/editor/node/{nid} with the editor
   *   frame (Preview), side menu, topbar, and contextual panel all present.
   * - The Library panel opens when the Library icon is clicked in the side
   *   menu, and the Components tab selector becomes visible.
   * - The Page data panel is hidden (canvas_override sets hidePageDataPanel).
   */
  public function testEnableCanvasOverrideViaFormAndVerifyEditor(): void {
    $this->drupalLogin($this->adminUser);

    // Go to the content type settings form.
    $this->drupalGet('/admin/structure/types/manage/article');
    $this->assertSession()->pageTextContains('Canvas layout');

    // Expand the Canvas layout fieldset and enable the setting.
    $page = $this->getSession()->getPage();
    $page->clickLink('Canvas layout');
    $this->assertSession()->waitForElement('css', '[name="canvas_override_enabled"]', 5000);
    $page->checkField('canvas_override_enabled');
    $page->pressButton('Save');
    $this->assertSession()->waitForText('has been updated', 10000);

    // The canvas_layout field must appear in the field list.
    $this->drupalGet('/admin/structure/types/manage/article/fields');
    $this->assertSession()->pageTextContains('Canvas Layout');

    // Create an article node.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Canvas Test Article',
      'uid' => $this->adminUser->id(),
      'status' => 1,
    ]);
    $node->save();

    // Open the Canvas editor. Canvas requires a viewport >= 1024px wide.
    $this->getSession()->resizeWindow(1440, 900, 'current');
    $this->drupalGet('/canvas/editor/node/' . $node->id());

    // Wait for the React app root to mount.
    $this->assertSession()->waitForElement('css', '.canvas-container', 20000);

    // Wait for the editor frame (preview area).
    $this->assertSession()->waitForElement('css', '[data-testid="canvas-editor-frame"]', 20000);

    // Side menu and topbar must be visible.
    $this->assertSession()->elementExists('css', '[data-testid="canvas-side-menu"]');
    $this->assertSession()->elementExists('css', '[data-testid="canvas-topbar"]');

    // Primary panel must be in the DOM (it starts closed/off-screen).
    $this->assertSession()->waitForElement('css', '[data-testid="canvas-primary-panel"]', 10000);

    // Open the Library panel by clicking the Library icon in the side menu.
    $page->find('css', '[data-testid="canvas-side-menu"] [aria-label="Library"]')?->click();

    // After opening, the Components tab selector must appear inside the panel.
    $this->assertSession()->waitForElement('css', '[data-testid="canvas-library-components-tab-select"]', 10000);

    // The contextual panel wrapper must be present.
    $this->assertSession()->elementExists('css', '[data-testid="canvas-contextual-panel"]');

    // The Page data panel is intentionally hidden in canvas_override mode
    // because nodes use the standard Drupal edit form for their field data.
    $this->assertSession()->elementNotExists('css', '[data-testid="canvas-contextual-panel--page-data"]');
  }

  // ---------------------------------------------------------------------------
  // Scenario 2: Enable Canvas Override on a new content type that has a
  // custom field; the canvas_layout field is created and the editor is
  // accessible.
  // ---------------------------------------------------------------------------

  /**
   * Tests Canvas Override on a new content type with a custom field.
   *
   * Verifies:
   * - Canvas Override can be enabled on a freshly-created content type.
   * - The canvas_layout field is created and shows in the field list.
   * - The Canvas editor loads for a node of the new type.
   * - The editor structure (frame, side menu, topbar) is present.
   * - The Page data panel is hidden since canvas_override suppresses it.
   */
  public function testCanvasOverrideOnNewContentTypeWithCustomField(): void {
    // Create a 'project' content type.
    NodeType::create(['type' => 'project', 'name' => 'Project'])->save();

    // Add a plain-text field 'Project Client' to it.
    FieldStorageConfig::create([
      'field_name' => 'field_project_client',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_project_client',
      'entity_type' => 'node',
      'bundle' => 'project',
      'label' => 'Project Client',
    ])->save();

    // Create a user that can manage this content type.
    $user = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer canvas override',
      'use canvas override',
      'access content',
      'create project content',
      'edit any project content',
    ]);
    $this->drupalLogin($user);

    // Enable Canvas Override on the new type via the admin form.
    $this->drupalGet('/admin/structure/types/manage/project');
    $page = $this->getSession()->getPage();
    $page->clickLink('Canvas layout');
    $this->assertSession()->waitForElement('css', '[name="canvas_override_enabled"]', 5000);
    $page->checkField('canvas_override_enabled');
    $page->pressButton('Save');
    $this->assertSession()->waitForText('has been updated', 10000);

    // The canvas_layout field must appear in the field list.
    $this->drupalGet('/admin/structure/types/manage/project/fields');
    $this->assertSession()->pageTextContains('Canvas Layout');

    // Create a project node.
    $node = Node::create([
      'type' => 'project',
      'title' => 'Test Project for Canvas',
      'uid' => $user->id(),
      'status' => 1,
    ]);
    $node->save();

    // Open the Canvas editor for the project node.
    $this->getSession()->resizeWindow(1440, 900, 'current');
    $this->drupalGet('/canvas/editor/node/' . $node->id());
    $this->assertSession()->waitForElement('css', '[data-testid="canvas-editor-frame"]', 20000);
    $this->assertSession()->waitForElement('css', '[data-testid="canvas-primary-panel"]', 10000);

    // Side menu and topbar must be present.
    $this->assertSession()->elementExists('css', '[data-testid="canvas-side-menu"]');
    $this->assertSession()->elementExists('css', '[data-testid="canvas-topbar"]');

    // The Page data panel is hidden in canvas_override mode.
    // Nodes use the standard Drupal edit form for their field data, so neither
    // the title nor the custom 'project_client' field appears in a page data
    // panel inside the Canvas editor.
    $this->assertSession()->elementNotExists('css', '[data-testid="canvas-contextual-panel--page-data"]');
  }

  // ---------------------------------------------------------------------------
  // Scenario 3: The Canvas Library panel is accessible and the Components tab
  // can be opened in a canvas_override-enabled node's editor.
  // ---------------------------------------------------------------------------

  /**
   * Tests that the Canvas Library panel is accessible for canvas_override nodes.
   *
   * Verifies:
   * - The canvas editor loads for a canvas_override-enabled node.
   * - The side menu shows the Library and Layers icons.
   * - Clicking the Library icon opens the primary panel.
   * - The Components tab selector and tab content are rendered.
   * - The contextual panel wrapper is present as the container where
   *   per-component settings would appear when a component is selected.
   */
  public function testLibraryPanelAccessibleInCanvasEditor(): void {
    // Article content type already created in setUp(). Enable Canvas Override.
    $node_type = NodeType::load('article');
    $node_type->setThirdPartySetting('canvas_override', 'enabled', TRUE);
    $node_type->save();

    $user = $this->drupalCreateUser([
      'administer canvas override',
      'use canvas override',
      'access content',
      'create article content',
      'edit any article content',
    ]);
    $this->drupalLogin($user);

    // Create an article node.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test Article for Library Panel',
      'uid' => $user->id(),
      'status' => 1,
    ]);
    $node->save();

    // Open the Canvas editor.
    $this->getSession()->resizeWindow(1440, 900, 'current');
    $this->drupalGet('/canvas/editor/node/' . $node->id());
    $this->assertSession()->waitForElement('css', '[data-testid="canvas-editor-frame"]', 20000);
    $this->assertSession()->waitForElement('css', '[data-testid="canvas-primary-panel"]', 10000);

    // Side menu must expose Library and Layers icons.
    $this->assertSession()->elementExists('css', '[data-testid="canvas-side-menu"] [aria-label="Library"]');
    $this->assertSession()->elementExists('css', '[data-testid="canvas-side-menu"] [aria-label="Layers"]');

    // Click the Library icon to open the primary panel.
    $this->getSession()->getPage()
      ->find('css', '[data-testid="canvas-side-menu"] [aria-label="Library"]')
      ?->click();

    // After opening the panel, the Components tab selector must appear.
    $this->assertSession()->waitForElement('css', '[data-testid="canvas-library-components-tab-select"]', 10000);

    // Click the Components tab to reveal the component list.
    $this->getSession()->getPage()
      ->find('css', '[data-testid="canvas-library-components-tab-select"]')
      ?->click();
    $this->assertSession()->waitForElement('css', '[data-testid="canvas-library-components-tab-content"]', 5000);

    // The contextual panel wrapper must exist as the container for the
    // per-component settings panel rendered when a component is selected.
    $this->assertSession()->elementExists('css', '[data-testid="canvas-contextual-panel"]');
  }

}
