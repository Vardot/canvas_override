<?php

declare(strict_types=1);

namespace Drupal\Tests\canvas_override\Unit;

use PHPUnit\Framework\Attributes\Group;
use Drupal\canvas_override\CanvasOverridePermissions;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the dynamic per-bundle permission generation.
 *
 * @coversDefaultClass \Drupal\canvas_override\CanvasOverridePermissions
 * @group canvas_override
 */
#[Group('canvas_override')]
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

    $translation = $this->getStringTranslationStub();

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('string_translation', $translation);
    \Drupal::setContainer($container);

    $this->permissions = new CanvasOverridePermissions($entity_type_manager);
    $this->permissions->setStringTranslation($translation);
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
   * Each enabled type generates two permissions: one for editing per-content
   * layouts and one for resetting them.
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

    $this->assertCount(3, $result);
    $this->assertArrayHasKey('use canvas override for article', $result);
    $this->assertArrayHasKey('reset canvas layout for article', $result);
    $this->assertArrayHasKey('edit canvas default template for article', $result);
    $this->assertNotEmpty((string) $result['use canvas override for article']['title']);
    $this->assertNotEmpty((string) $result['reset canvas layout for article']['title']);
    $this->assertNotEmpty((string) $result['edit canvas default template for article']['title']);
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

    // 2 enabled types × 3 permissions each = 6.
    $this->assertCount(6, $result);
    $this->assertArrayHasKey('use canvas override for article', $result);
    $this->assertArrayHasKey('reset canvas layout for article', $result);
    $this->assertArrayHasKey('edit canvas default template for article', $result);
    $this->assertArrayHasKey('use canvas override for landing_page', $result);
    $this->assertArrayHasKey('reset canvas layout for landing_page', $result);
    $this->assertArrayHasKey('edit canvas default template for landing_page', $result);
    $this->assertArrayNotHasKey('use canvas override for page', $result);
    $this->assertArrayNotHasKey('reset canvas layout for page', $result);
    $this->assertArrayNotHasKey('edit canvas default template for page', $result);
    $this->assertArrayNotHasKey('use canvas override for blog', $result);
    $this->assertArrayNotHasKey('reset canvas layout for blog', $result);
    $this->assertArrayNotHasKey('edit canvas default template for blog', $result);
  }

  /**
   * Tests that permission keys follow the expected naming conventions.
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
        '/^(use canvas override|reset canvas layout|edit canvas default template) for [a-z_]+$/',
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

    foreach ($result as $permission) {
      $this->assertArrayHasKey('title', $permission);
      $this->assertArrayHasKey('description', $permission);
    }
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

    // 3 enabled types × 3 permissions each = 9.
    $this->assertCount(9, $result);
    foreach (array_keys($types) as $bundle) {
      $this->assertArrayHasKey("use canvas override for $bundle", $result);
      $this->assertArrayHasKey("reset canvas layout for $bundle", $result);
      $this->assertArrayHasKey("edit canvas default template for $bundle", $result);
    }
  }

  /**
   * Tests per-bundle permissions for a custom "Marketing campaign" type.
   *
   * Mirrors the acceptance-test fixture: a site builder enables Canvas Override
   * on their own content type (machine name marketing_campaign) so the
   * marketing team can override the full-content Canvas layout per campaign.
   * The generator must mint the three per-bundle permissions for it and carry
   * the human-readable label into the titles.
   *
   * @covers ::perBundlePermissions
   */
  public function testMarketingCampaignCustomType(): void {
    $this->nodeTypeStorage->method('loadMultiple')
      ->with(NULL)
      ->willReturn([
        'marketing_campaign' => $this->createMockNodeType('marketing_campaign', 'Marketing campaign', TRUE),
        'page' => $this->createMockNodeType('page', 'Basic page', FALSE),
      ]);

    $result = $this->permissions->perBundlePermissions();

    // Only the enabled custom type generates permissions (3 of them).
    $this->assertCount(3, $result);
    $this->assertArrayHasKey('use canvas override for marketing_campaign', $result);
    $this->assertArrayHasKey('reset canvas layout for marketing_campaign', $result);
    $this->assertArrayHasKey('edit canvas default template for marketing_campaign', $result);
    $this->assertArrayNotHasKey('use canvas override for page', $result);

    // The bundle label is carried into the generated permission titles.
    $this->assertStringContainsString(
      'Marketing campaign',
      (string) $result['use canvas override for marketing_campaign']['title'],
    );
  }

  /**
   * Tests that the class can be instantiated.
   */
  public function testClassInstantiation(): void {
    $this->assertInstanceOf(CanvasOverridePermissions::class, $this->permissions);
  }

}
