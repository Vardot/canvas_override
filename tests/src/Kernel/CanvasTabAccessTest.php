~1<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the Canvas tab access check.
 *
 * @group canvas_override
 */
class CanvasTabAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
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
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->installConfig(['node', 'filter']);

    // Create a content type with canvas_override enabled.
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->setThirdPartySetting('canvas_override', 'enabled', TRUE);
    $node_type->save();

    // Create a content type without canvas_override.
    $node_type_basic = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $node_type_basic->save();
  }

  /**
   * Tests access is denied for users without any Canvas Override permission.
   */
  public function testAccessDeniedWithoutPermission(): void {
    $user = $this->createUser([]);
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test',
      'uid' => $user->id(),
    ]);
    $node->save();

    $access_checker = \Drupal::service('canvas_override.access_check');
    $result = $access_checker->access($node, $user);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Tests access is granted with global 'use canvas override' permission.
   */
  public function testAccessGrantedWithGlobalPermission(): void {
    $user = $this->createUser(['use canvas override']);
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test',
      'uid' => $user->id(),
    ]);
    $node->save();

    $access_checker = \Drupal::service('canvas_override.access_check');
    $result = $access_checker->access($node, $user);
    $this->assertTrue($result->isAllowed());
  }

  /**
   * Tests access is granted with per-bundle permission.
   */
  public function testAccessGrantedWithBundlePermission(): void {
    $user = $this->createUser(['use canvas override for article']);
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test',
      'uid' => $user->id(),
    ]);
    $node->save();

    $access_checker = \Drupal::service('canvas_override.access_check');
    $result = $access_checker->access($node, $user);
    $this->assertTrue($result->isAllowed());
  }

  /**
   * Tests access is denied for a bundle that does not have Canvas Override.
   */
  public function testAccessDeniedForDisabledBundle(): void {
    $user = $this->createUser(['use canvas override']);
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test Page',
      'uid' => $user->id(),
    ]);
    $node->save();

    $access_checker = \Drupal::service('canvas_override.access_check');
    $result = $access_checker->access($node, $user);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Tests access with 'administer canvas override' permission.
   */
  public function testAccessGrantedWithAdminPermission(): void {
    $user = $this->createUser(['administer canvas override']);
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test',
      'uid' => $user->id(),
    ]);
    $node->save();

    $access_checker = \Drupal::service('canvas_override.access_check');
    $result = $access_checker->access($node, $user);
    $this->assertTrue($result->isAllowed());
  }

  /**
   * Creates a user with the given permissions.
   */
  private function createUser(array $permissions): User {
    $role = Role::create([
      'id' => 'test_role_' . $this->randomMachineName(),
      'label' => 'Test Role',
    ]);
    foreach ($permissions as $permission) {
      $role->grantPermission($permission);
    }
    $role->save();

    $user = User::create([
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $user->addRole($role->id());
    $user->save();

    return $user;
  }

}
