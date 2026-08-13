<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\Unit;

use Drupal\canvas_override\Hook\CanvasOverrideHooks;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Tests\UnitTestCase;

/**
 * Guards how the node type save hook is registered.
 *
 * Regression cover for
 * https://www.drupal.org/project/canvas_override/issues/3616302: the field was
 * previously ensured from a node_type_presave hook, which fires before the
 * bundle entity exists and aborts a config import with "Missing bundle entity".
 * The fix moves the work to node_type_insert / node_type_update and skips it
 * while config is syncing. This test fails if anyone reintroduces the presave
 * hook or drops the config-sync guard, without needing a booted kernel.
 *
 * @group canvas_override
 */
class CanvasOverrideHookRegistrationTest extends UnitTestCase {

  /**
   * Reads the Hook attribute names declared on a method.
   *
   * @param string $method
   *   The CanvasOverrideHooks method name.
   *
   * @return string[]
   *   The hook names (e.g. "node_type_insert") declared via #[Hook(...)].
   */
  protected function hookNames(string $method): array {
    $reflection = new \ReflectionMethod(CanvasOverrideHooks::class, $method);
    $names = [];
    foreach ($reflection->getAttributes(Hook::class) as $attribute) {
      $names[] = $attribute->newInstance()->hook;
    }
    return $names;
  }

  /**
   * The field is ensured from insert and update, never from presave.
   */
  public function testNodeTypeSaveHooks(): void {
    $names = $this->hookNames('nodeTypeSave');

    $this->assertContains('node_type_insert', $names, 'The field is ensured on node type insert.');
    $this->assertContains('node_type_update', $names, 'The field is ensured on node type update.');
  }

  /**
   * No method reintroduces the node_type_presave hook.
   *
   * Presave fires before the bundle entity exists, which is exactly the
   * config-import failure #3616302 fixed.
   */
  public function testNoPresaveHook(): void {
    $reflection = new \ReflectionClass(CanvasOverrideHooks::class);
    foreach ($reflection->getMethods() as $method) {
      foreach ($method->getAttributes(Hook::class) as $attribute) {
        $this->assertNotSame(
          'node_type_presave',
          $attribute->newInstance()->hook,
          sprintf('%s() must not register node_type_presave (see #3616302).', $method->getName()),
        );
      }
    }
  }

  /**
   * The save hook skips its work while configuration is syncing.
   *
   * During a config import the bundle and its field config are imported as
   * trusted data, so the hook must return early rather than create the field
   * itself. Asserted at the source level so the guard cannot be silently
   * removed.
   */
  public function testConfigSyncGuardPresent(): void {
    $reflection = new \ReflectionMethod(CanvasOverrideHooks::class, 'nodeTypeSave');
    $file = new \SplFileObject($reflection->getFileName());
    $file->seek($reflection->getStartLine() - 1);
    $source = '';
    while (!$file->eof() && $file->key() < $reflection->getEndLine()) {
      $source .= $file->current();
      $file->next();
    }

    $this->assertStringContainsString(
      'isConfigSyncing',
      $source,
      'nodeTypeSave() must skip its work while config is syncing (see #3616302).',
    );
  }

}
