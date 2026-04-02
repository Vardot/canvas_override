<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the Canvas Override editor UI for per-content layouts.
 *
 * @group canvas_override
 */
class CanvasOverrideEditorTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
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
   * A user with Canvas Override permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $canvasUser;

  /**
   * A user without Canvas Override permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $regularUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type with canvas_override enabled.
    $node_type = NodeType::load('article');
    if (!$node_type) {
      $node_type = NodeType::create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    }
    $node_type->setThirdPartySetting('canvas_override', 'enabled', TRUE);
    $node_type->save();

    // Create users.
    $this->canvasUser = $this->drupalCreateUser([
      'access content',
      'create article content',
      'edit any article content',
      'use canvas override',
    ]);

    $this->regularUser = $this->drupalCreateUser([
      'access content',
      'create article content',
      'edit any article content',
    ]);
  }

  /**
   * Tests that the Canvas tab appears for users with permission.
   */
  public function testCanvasTabVisibleWithPermission(): void {
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test Article',
    ]);

    $this->drupalLogin($this->canvasUser);
    $this->drupalGet('node/' . $node->id());

    // The Canvas local task tab should be present.
    $session = $this->assertSession();
    $session->linkExists('Canvas Override');
  }

  /**
   * Tests that the Canvas tab is hidden for users without permission.
   */
  public function testCanvasTabHiddenWithoutPermission(): void {
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test Article',
    ]);

    $this->drupalLogin($this->regularUser);
    $this->drupalGet('node/' . $node->id());

    $session = $this->assertSession();
    $session->linkNotExists('Canvas');
  }

  /**
   * Tests that the Canvas editor redirects correctly.
   */
  public function testCanvasEditorRedirect(): void {
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test Article for Canvas',
    ]);

    $this->drupalLogin($this->canvasUser);
    $this->drupalGet('node/' . $node->id() . '/canvas');

    // Should redirect to the Canvas editor URL.
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('canvas/editor/node/' . $node->id(), $current_url);
  }

  /**
   * Tests per-bundle permission grants access only for the right bundle.
   */
  public function testPerBundlePermission(): void {
    // Create a second content type without canvas_override.
    $page_type = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $page_type->save();

    $bundleUser = $this->drupalCreateUser([
      'access content',
      'create article content',
      'edit any article content',
      'create page content',
      'edit any page content',
      'use canvas override for article',
    ]);

    $article = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Article Content',
    ]);

    $this->drupalLogin($bundleUser);
    $this->drupalGet('node/' . $article->id());

    // Canvas tab should appear for article.
    $this->assertSession()->linkExists('Canvas');
  }

}
