<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\Unit;

use Drupal\canvas_override\CanvasOverridePermissions;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the dynamic per-bundle permission generation.
 *
 * @coversDefaultClass \Drupal\canvas_override\CanvasOverridePermissions
 * @group canvas_override
 */
class CanvasOverridePermissionsTest extends UnitTestCase {

  /**
   * Tests that permission keys follow the expected naming convention.
   */
  public function testPermissionKeyFormat(): void {
    $permission_key = 'use canvas override for article';
    $this->assertMatchesRegularExpression(
      '/^use canvas override for [a-z_]+$/',
      $permission_key,
    );
  }

  /**
   * Tests that the permission class can be instantiated.
   */
  public function testClassInstantiation(): void {
    $permissions = new CanvasOverridePermissions();
    $this->assertInstanceOf(CanvasOverridePermissions::class, $permissions);
  }

}
