<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests Canvas Override module installation and permissions.
 *
 * @group canvas_override
 */
class CanvasOverrideInstallTest extends KernelTestBase {

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
  }

  /**
   * Tests that static permissions are defined.
   */
  public function testStaticPermissionsExist(): void {
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $this->assertArrayHasKey('administer canvas override', $permissions);
    $this->assertArrayHasKey('use canvas override', $permissions);
  }

  /**
   * Tests that per-bundle permissions are generated for enabled types.
   */
  public function testPerBundlePermissionsGenerated(): void {
    // Create a content type with canvas_override enabled.
    $node_type = NodeType::create([
      'type' => 'test_page',
      'name' => 'Test Page',
    ]);
    $node_type->setThirdPartySetting('canvas_override', 'enabled', TRUE);
    $node_type->save();

    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $this->assertArrayHasKey('use canvas override for test_page', $permissions);
  }

  /**
   * Tests that per-bundle permissions are NOT generated for disabled types.
   */
  public function testPerBundlePermissionsNotGeneratedForDisabledType(): void {
    $node_type = NodeType::create([
      'type' => 'basic_page',
      'name' => 'Basic Page',
    ]);
    $node_type->save();

    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $this->assertArrayNotHasKey('use canvas override for basic_page', $permissions);
  }

  /**
   * Tests the CANVAS_OVERRIDE_FIELD_NAME constant.
   */
  public function testFieldNameConstant(): void {
    $this->assertSame('field_canvas_layout', CANVAS_OVERRIDE_FIELD_NAME);
  }

}
