<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Canvas Override reset layout functionality.
 *
 * Covers the reset route, access control, and redirect behavior.
 *
 * @group canvas_override
 */
#[RunTestsInSeparateProcesses]
class CanvasOverrideResetTest extends WebDriverTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if (!NodeType::load('article')) {
      NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    }

    $article_type = NodeType::load('article');
    $article_type->setThirdPartySetting('canvas_override', 'enabled', TRUE);
    $article_type->save();

    $admin = $this->drupalCreateUser([
      'administer canvas override',
      'access content',
      'create article content',
      'edit any article content',
    ]);

    $this->articleNode = Node::create([
      'type' => 'article',
      'title' => 'Reset Test Article',
      'uid' => $admin->id(),
      'status' => 1,
    ]);
    $this->articleNode->save();
  }

  /**
   * Tests reset layout redirects to node page with success message.
   */
  public function testResetLayoutRedirectsWithMessage(): void {
    $user = $this->drupalCreateUser([
      'use canvas override',
      'access content',
      'edit any article content',
    ]);
    $this->drupalLogin($user);

    // Navigate to the reset route.
    $this->drupalGet('/node/' . $this->articleNode->id() . '/canvas/reset');

    // Should redirect to the node page with a success message.
    $this->assertSession()->addressMatches('/\/node\/' . $this->articleNode->id() . '$/');
    $this->assertSession()->pageTextContains('Canvas layout reset to the shared default template');
    $this->assertSession()->pageTextContains('Reset Test Article');
  }

  /**
   * Tests reset route denies access without Canvas Override permission.
   */
  public function testResetDeniedWithoutPermission(): void {
    $user = $this->drupalCreateUser([
      'access content',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/node/' . $this->articleNode->id() . '/canvas/reset');
    $this->assertSession()->statusCodeEquals(403);
  }

}
