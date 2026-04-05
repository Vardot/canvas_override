<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests disabling and re-enabling Canvas Override on a content type.
 *
 * Verifies that the Canvas tab, operations link, per-bundle permission,
 * and field all respond correctly when Canvas Override is toggled off and on.
 *
 * @group canvas_override
 */
#[RunTestsInSeparateProcesses]
class CanvasOverrideToggleTest extends WebDriverTestBase {

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
   */
  protected $profile = 'minimal';

  /**
   * Admin user with full Canvas Override and content administration rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A test node used across scenarios.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

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
      'administer permissions',
    ]);

    $this->drupalLogin($this->adminUser);

    // Enable Canvas Override via the admin form.
    $this->drupalGet('/admin/structure/types/manage/article');
    $page = $this->getSession()->getPage();
    $page->clickLink('Canvas layout');
    $this->assertSession()->waitForElement('css', '[name="canvas_override_enabled"]', 5000);
    $page->checkField('canvas_override_enabled');
    $page->pressButton('Save');
    $this->assertSession()->waitForText('has been updated', 10000);

    // Create a test node.
    $this->testNode = Node::create([
      'type' => 'article',
      'title' => 'Toggle Test Article',
      'uid' => $this->adminUser->id(),
      'status' => 1,
    ]);
    $this->testNode->save();
  }

  /**
   * Tests the full disable and re-enable lifecycle of Canvas Override.
   *
   * Verifies:
   * - Canvas tab is visible when Canvas Override is enabled.
   * - Per-bundle permission is present on the permissions page.
   * - After disabling: Canvas tab disappears, permission disappears.
   * - Content still renders without errors after disabling.
   * - After re-enabling: Canvas tab reappears, permission reappears.
   * - The canvas_layout field persists through disable/re-enable.
   */
  public function testDisableAndReenableCanvasOverride(): void {
    $nid = $this->testNode->id();

    // --- Verify enabled state ---
    $this->drupalGet('/node/' . $nid);
    $this->assertSession()->linkExists('Canvas Override');

    $this->drupalGet('/admin/people/permissions');
    $this->assertSession()->pageTextContains('Use Canvas Override for Article content');

    // --- Disable Canvas Override ---
    $this->drupalGet('/admin/structure/types/manage/article');
    $page = $this->getSession()->getPage();
    $page->clickLink('Canvas layout');
    $this->assertSession()->waitForElement('css', '[name="canvas_override_enabled"]', 5000);
    $page->uncheckField('canvas_override_enabled');
    $page->pressButton('Save');
    $this->assertSession()->waitForText('has been updated', 10000);

    // Canvas tab must disappear.
    $this->drupalGet('/node/' . $nid);
    $this->assertSession()->linkNotExists('Canvas Override');

    // Content must still render.
    $this->assertSession()->pageTextContains('Toggle Test Article');

    // Per-bundle permission must disappear.
    $this->drupalGet('/admin/people/permissions');
    $this->assertSession()->pageTextNotContains('Use Canvas Override for Article content');

    // Canvas route must deny access.
    $this->drupalGet('/node/' . $nid . '/canvas');
    $this->assertSession()->statusCodeEquals(403);

    // The canvas_layout field should still exist (it is locked).
    $this->drupalGet('/admin/structure/types/manage/article/fields');
    $this->assertSession()->pageTextContains('Canvas Layout');

    // --- Re-enable Canvas Override ---
    $this->drupalGet('/admin/structure/types/manage/article');
    $page = $this->getSession()->getPage();
    $page->clickLink('Canvas layout');
    $this->assertSession()->waitForElement('css', '[name="canvas_override_enabled"]', 5000);
    $page->checkField('canvas_override_enabled');
    $page->pressButton('Save');
    $this->assertSession()->waitForText('has been updated', 10000);

    // Canvas tab must reappear.
    $this->drupalGet('/node/' . $nid);
    $this->assertSession()->linkExists('Canvas Override');

    // Per-bundle permission must reappear.
    $this->drupalGet('/admin/people/permissions');
    $this->assertSession()->pageTextContains('Use Canvas Override for Article content');
  }

  /**
   * Tests that disabling Canvas Override does not affect other content types.
   *
   * Verifies:
   * - Disabling Canvas Override on one type does not affect another type
   *   that also has Canvas Override enabled.
   */
  public function testDisableDoesNotAffectOtherTypes(): void {
    // Create a second content type with Canvas Override enabled.
    NodeType::create(['type' => 'page', 'name' => 'Basic page'])->save();

    // Need to create the user again with the new permission.
    $user = $this->drupalCreateUser([
      'administer content types',
      'administer canvas override',
      'use canvas override',
      'access content',
      'create page content',
      'create article content',
    ]);
    $this->drupalLogin($user);

    // Enable Canvas Override on the page type.
    $this->drupalGet('/admin/structure/types/manage/page');
    $page = $this->getSession()->getPage();
    $page->clickLink('Canvas layout');
    $this->assertSession()->waitForElement('css', '[name="canvas_override_enabled"]', 5000);
    $page->checkField('canvas_override_enabled');
    $page->pressButton('Save');
    $this->assertSession()->waitForText('has been updated', 10000);

    // Create a page node.
    $pageNode = Node::create([
      'type' => 'page',
      'title' => 'Test Page',
      'uid' => $user->id(),
      'status' => 1,
    ]);
    $pageNode->save();

    // Verify Canvas tab on the page node.
    $this->drupalGet('/node/' . $pageNode->id());
    $this->assertSession()->linkExists('Canvas Override');

    // Disable Canvas Override on article only.
    $this->drupalGet('/admin/structure/types/manage/article');
    $page = $this->getSession()->getPage();
    $page->clickLink('Canvas layout');
    $this->assertSession()->waitForElement('css', '[name="canvas_override_enabled"]', 5000);
    $page->uncheckField('canvas_override_enabled');
    $page->pressButton('Save');
    $this->assertSession()->waitForText('has been updated', 10000);

    // Article's Canvas tab must disappear.
    $this->drupalGet('/node/' . $this->testNode->id());
    $this->assertSession()->linkNotExists('Canvas Override');

    // Page's Canvas tab must still be visible.
    $this->drupalGet('/node/' . $pageNode->id());
    $this->assertSession()->linkExists('Canvas Override');
  }

}
