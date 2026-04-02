# Testing

## Overview

Canvas Override includes four test suites covering unit, kernel, and
functional JavaScript testing. Tests are grouped under `canvas_override` and
can be run with PHPUnit.

## Test Suites

### Unit Tests

**File**: `tests/src/Unit/CanvasOverridePermissionsTest.php`

Tests the dynamic per-bundle permission generation in
`CanvasOverridePermissions`. Uses mocked `NodeType` entities via the
container to verify:

- No permissions generated when no content types exist.
- Content types without Canvas Override enabled are skipped.
- Single and multiple enabled types produce correct permissions.
- Permission keys follow the `use canvas override for {bundle}` format.
- Each permission has `title` and `description` keys.

### Kernel Tests

**File**: `tests/src/Kernel/CanvasOverrideInstallTest.php`

Tests module installation and static permission registration:

- Static permissions exist (`administer canvas override`,
  `use canvas override`).
- Per-bundle permissions are generated for enabled content types.
- Per-bundle permissions are not generated for disabled types.
- The `CANVAS_OVERRIDE_FIELD_NAME` constant has the expected value.

**File**: `tests/src/Kernel/CanvasTabAccessTest.php`

Tests the access check system:

- Access denied without any Canvas Override permission.
- Access granted with the global `use canvas override` permission.
- Access granted with the per-bundle permission.
- Access denied when the content type has Canvas Override disabled.
- Access granted with `administer canvas override`.

### Functional JavaScript Tests

**File**: `tests/src/FunctionalJavascript/CanvasOverrideEditorTest.php`

End-to-end tests using a real browser:

- Canvas tab is visible with the correct permission.
- Canvas tab is hidden without permission.
- Canvas editor redirect works correctly.
- Per-bundle permission grants access only for the correct content type.

## Running Tests

### In DrupalCI (GitLab CI)

Tests run automatically in the CI pipeline. The `.gitlab-ci.yml` configures:

```yaml
phpunit:
  parallel:
    matrix:
      - TESTSUITE:
        - kernel
        - functional-javascript
        - unit
```

All tests are filtered by `--group canvas_override`.

### Locally with PHPUnit

From a Drupal installation that includes Canvas Override:

```bash
# Run all Canvas Override tests
./vendor/bin/phpunit --group canvas_override

# Run a specific test suite
./vendor/bin/phpunit --group canvas_override --testsuite unit
./vendor/bin/phpunit --group canvas_override --testsuite kernel
./vendor/bin/phpunit --group canvas_override --testsuite functional-javascript

# Run a single test file
./vendor/bin/phpunit tests/src/Unit/CanvasOverridePermissionsTest.php
```

## Writing New Tests

### Test Group

All tests must include the `@group canvas_override` annotation:

```php
/**
 * @group canvas_override
 */
class MyNewTest extends UnitTestCase {
```

### Unit Test Template

For testing classes that don't require Drupal's database or services:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests [description].
 *
 * @coversDefaultClass \Drupal\canvas_override\ClassName
 * @group canvas_override
 */
class ClassNameTest extends UnitTestCase {

  /**
   * Tests [description].
   *
   * @covers ::methodName
   */
  public function testMethodName(): void {
    // Arrange, Act, Assert.
  }

}
```

### Kernel Test Template

For testing with the database and Drupal services:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests [description].
 *
 * @group canvas_override
 */
class MyKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'field',
    'user',
    'canvas',
    'canvas_override',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->installEntitySchema('user');
  }

}
```

### Testing Permissions

When testing permission checks, create mock users with specific permissions:

```php
$user = $this->createUser(['use canvas override for article']);
$this->setCurrentUser($user);
```

### Testing Access Checks

Use the `CanvasTabAccessCheck` service directly:

```php
$access_check = \Drupal::service('canvas_override.access_check');
$result = $access_check->access($account, $node);
$this->assertTrue($result->isAllowed());
```

## Code Quality

### PHPCodeSniffer

```bash
yarn phpcs
```

### CSpell

```bash
yarn spellcheck
```

### Local CI

```bash
npx gitlab-ci-local --file .gitlab-ci-local.yml
```

## Next Steps

- [Architecture](0-architecture.md) - Understand the module structure.
- [API Reference](2-api-reference.md) - Key classes and methods.
