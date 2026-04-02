<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\Unit;

use Drupal\canvas_override\CanvasOverridePermissions;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the dynamic per-bundle permission generation.
 *
 * @coversDefaultClass \Drupal\canvas_override\CanvasOverridePermissions
 * @group canvas_override
 */
class CanvasOverridePermissionsTest extends UnitTestCase {

  /**
   * The permissions service under test.
   *
   * @var \Drupal\canvas_override\CanvasOverridePermissions
   */
  protected CanvasOverridePermissions $permissions;

  /**
   * Mock entity storage for node types.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $nodeTypeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->nodeTypeStorage = $this->createMock(EntityStorageInterface::class);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('node_type')
      ->willReturn($this->nodeTypeStorage);

    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(function ($string) {
        return $string;
      });

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('string_translation', $translation);
    \Drupal::setContainer($container);

    $this->permissions = new CanvasOverridePermissions();
  }

  /**
   * Creates a mock NodeType with canvas_override third-party settings.
   *
   * @param string $id
   *   The node type machine name.
   * @param string $label
   *   The node type human-readable label.
   * @param bool $enabled
   *   Whether canvas_override is enabled on this type.
   *
   * @return \Drupal\node\NodeTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked node type.
   */
  protected function createMockNodeType(string $id, string $label, bool $enabled): NodeTypeInterface {
    $node_type = $this->createMock(NodeTypeInterface::class);
    $node_type->method('id')->willReturn($id);
    $node_type->method('label')->willReturn($label);
    $node_type->method('getThirdPartySetting')
      ->with('canvas_override', 'enabled', FALSE)
      ->willReturn($enabled);
    return $node_type;
  }

  /**
   * Tests that no permissions are generated when no content types exist.
   *
   * @covers ::perBundlePermissions
   */
  public function testNoContentTypes(): void {
    $this->nodeTypeStorage->method('loadMultiple')
      ->with(NULL)
      ->willReturn([]);

    $result = $this->permissions->perBundlePermissions();

    $this->assertSame([], $result);
  }

  /**
   * Tests that disabled content types produce no permissions.
   *
   * @covers ::perBundlePermissions
   */
  public function testDisabledContentTypesSkipped(): void {
    $this->nodeTypeStorage->method('loadMultiple')
      ->with(NULL)
      ->willReturn([
        'page' => $this->createMockNodeType('page', 'Basic page', FALSE),
        'article' => $this->createMockNodeType('article', 'Article', FALSE),
      ]);

    $result = $this->permissions->perBundlePermissions();

    $this->assertSame([], $result);
  }

  /**
   * Tests permission generation for a single enabled content type.
   *
   * @covers ::perBundlePermissions
   */
  public function testSingleEnabledContentType(): void {
    $this->nodeTypeStorage->method('loadMultiple')
      ->with(NULL)
      ->willReturn([
        'article' => $this->createMockNodeType('article', 'Article', TRUE),
      ]);

    $result = $this->permissions->perBundlePermissions();

    $this->assertCount(1, $result);
    $this->assertArrayHasKey('use canvas override for article', $result);
    $this->assertNotEmpty((string) $result['use canvas override for article']['title']);
    $this->assertNotEmpty((string) $result['use canvas override for article']['description']);
  }

  /**
   * Tests that only enabled types get permissions in a mixed set.
   *
   * @covers ::perBundlePermissions
   */
  public function testMixedEnabledAndDisabledTypes(): void {
    $this->nodeTypeStorage->method('loadMultiple')
      ->with(NULL)
      ->willReturn([
        'page' => $this->createMockNodeType('page', 'Basic page', FALSE),
        'article' => $this->createMockNodeType('article', 'Article', TRUE),
        'landing_page' => $this->createMockNodeType('landing_page', 'Landing page', TRUE),
        'blog' => $this->createMockNodeType('blog', 'Blog post', FALSE),
      ]);

    $result = $this->permissions->perBundlePermissions();

    $this->assertCount(2, $result);
    $this->assertArrayHasKey('use canvas override for article', $result);
    $this->assertArrayHasKey('use canvas override for landing_page', $result);
    $this->assertArrayNotHasKey('use canvas override for page', $result);
    $this->assertArrayNotHasKey('use canvas override for blog', $result);
  }

  /**
   * Tests that permission keys follow the expected naming convention.
   *
   * @covers ::perBundlePermissions
   */
  public function testPermissionKeyFormat(): void {
    $this->nodeTypeStorage->method('loadMultiple')
      ->with(NULL)
      ->willReturn([
        'article' => $this->createMockNodeType('article', 'Article', TRUE),
        'landing_page' => $this->createMockNodeType('landing_page', 'Landing page', TRUE),
      ]);

    $result = $this->permissions->perBundlePermissions();

    foreach (array_keys($result) as $key) {
      $this->assertMatchesRegularExpression(
        '/^use canvas override for [a-z_]+$/',
        $key,
      );
    }
  }

  /**
   * Tests that each permission has required title and description keys.
   *
   * @covers ::perBundlePermissions
   */
  public function testPermissionStructure(): void {
    $this->nodeTypeStorage->method('loadMultiple')
      ->with(NULL)
      ->willReturn([
        'article' => $this->createMockNodeType('article', 'Article', TRUE),
      ]);

    $result = $this->permissions->perBundlePermissions();
    $permission = $result['use canvas override for article'];

    $this->assertArrayHasKey('title', $permission);
    $this->assertArrayHasKey('description', $permission);
  }

  /**
   * Tests permissions for multiple enabled types all have correct keys.
   *
   * @covers ::perBundlePermissions
   */
  public function testMultipleEnabledContentTypes(): void {
    $types = [
      'article' => $this->createMockNodeType('article', 'Article', TRUE),
      'page' => $this->createMockNodeType('page', 'Basic page', TRUE),
      'landing_page' => $this->createMockNodeType('landing_page', 'Landing page', TRUE),
    ];

    $this->nodeTypeStorage->method('loadMultiple')
      ->with(NULL)
      ->willReturn($types);

    $result = $this->permissions->perBundlePermissions();

    $this->assertCount(3, $result);
    foreach (array_keys($types) as $bundle) {
      $this->assertArrayHasKey("use canvas override for $bundle", $result);
    }
  }

  /**
   * Tests that the class can be instantiated.
   *
   * @covers ::__construct
   */
  public function testClassInstantiation(): void {
    $this->assertInstanceOf(CanvasOverridePermissions::class, $this->permissions);
  }

}
