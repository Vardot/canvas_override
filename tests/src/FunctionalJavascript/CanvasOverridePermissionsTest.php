<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Canvas Override permissions: admin, global, per-bundle, and denial.
 *
 * Covers the three-tier permission system and verifies that the Canvas tab
 * visibility responds correctly to each permission level.
 *
 * @group canvas_override
 */
#[RunTestsInSeparateProcesses]
class CanvasOverridePermissionsTest extends WebDriverTestBase {

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
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * An article node with Canvas Override enabled.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $articleNode;

  /**
   * A page node without Canvas Override enabled.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $pageNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create content types.
    if (!NodeType::load('article')) {
      NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    }
    if (!NodeType::load('page')) {
      NodeType::create(['type' => 'page', 'name' => 'Basic page'])->save();
    }

    // Enable Canvas Override on article only.
    $article_type = NodeType::load('article');
    $article_type->setThirdPartySetting('canvas_override', 'enabled', TRUE);
    $article_type->save();

    // Create test nodes.
    $admin = $this->drupalCreateUser([
      'administer content types',
      'administer canvas override',
      'access content',
      'create article content',
      'create page content',
    ]);

    $this->articleNode = Node::create([
      'type' => 'article',
      'title' => 'Permission Test Article',
      'uid' => $admin->id(),
      'status' => 1,
    ]);
    $this->articleNode->save();

    $this->pageNode = Node::create([
      'type' => 'page',
      'title' => 'Permission Test Page',
      'uid' => $admin->id(),
      'status' => 1,
    ]);
    $this->pageNode->save();
  }

  /**
   * Tests admin permission grants Canvas tab access.
   */
  public function testAdminPermissionGrantsAccess(): void {
    $user = $this->drupalCreateUser([
      'administer canvas override',
      'access content',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/node/' . $this->articleNode->id());
    $this->assertSession()->linkExists('Canvas Override');
  }

  /**
   * Tests global permission grants Canvas tab access.
   */
  public function testGlobalPermissionGrantsAccess(): void {
    $user = $this->drupalCreateUser([
      'use canvas override',
      'access content',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/node/' . $this->articleNode->id());
    $this->assertSession()->linkExists('Canvas Override');
  }

  /**
   * Tests per-bundle permission grants Canvas tab access.
   */
  public function testPerBundlePermissionGrantsAccess(): void {
    $user = $this->drupalCreateUser([
      'use canvas override for article',
      'access content',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/node/' . $this->articleNode->id());
    $this->assertSession()->linkExists('Canvas Override');
  }

  /**
   * Tests no Canvas Override permission denies access.
   */
  public function testNoPermissionDeniesAccess(): void {
    $user = $this->drupalCreateUser([
      'access content',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/node/' . $this->articleNode->id());
    $this->assertSession()->linkNotExists('Canvas Override');

    // Direct access to the Canvas route must be denied.
    $this->drupalGet('/node/' . $this->articleNode->id() . '/canvas');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests per-bundle permission does not cross to other content types.
   */
  public function testPerBundlePermissionDoesNotCrossBundles(): void {
    // Enable Canvas Override on page too so the permission exists.
    $page_type = NodeType::load('page');
    $page_type->setThirdPartySetting('canvas_override', 'enabled', TRUE);
    $page_type->save();

    // User only has article permission.
    $user = $this->drupalCreateUser([
      'use canvas override for article',
      'access content',
    ]);
    $this->drupalLogin($user);

    // Article tab: visible.
    $this->drupalGet('/node/' . $this->articleNode->id());
    $this->assertSession()->linkExists('Canvas Override');

    // Page tab: NOT visible (cross-type access denied).
    $this->drupalGet('/node/' . $this->pageNode->id());
    $this->assertSession()->linkNotExists('Canvas Override');
  }

}
