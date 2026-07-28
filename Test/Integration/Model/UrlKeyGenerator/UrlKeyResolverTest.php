<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model\UrlKeyGenerator;

use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\UrlKeyGenerator\UrlKeyResolver;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
final class UrlKeyResolverTest extends TestCase
{
    public function test_generates_from_title_when_no_slug_is_available(): void
    {
        self::assertSame(
            'integration-fresh-title',
            $this->resolver()->resolve('', 'Integration Fresh Title', UrlKeyGeneratorInterface::ENTITY_POST)
        );
    }

    public function test_suffixes_a_generated_slug_that_is_already_taken(): void
    {
        $this->createPost('Integration Taken', 'integration-taken');

        self::assertSame(
            'integration-taken-2',
            $this->resolver()->resolve('', 'Integration Taken', UrlKeyGeneratorInterface::ENTITY_POST)
        );
    }

    public function test_normalizes_a_hand_typed_slug_without_collision_suffixing(): void
    {
        // Never silently renamed: a clash must surface as a validation error instead.
        self::assertSame(
            'integration-typed',
            $this->resolver()->resolve('Integration Typed!', 'Ignored Title', UrlKeyGeneratorInterface::ENTITY_POST)
        );
    }

    public function test_blank_slug_on_an_edit_keeps_the_stored_value(): void
    {
        self::assertSame(
            'integration-stored',
            $this->resolver()->resolve(
                '',
                'A Completely Different Title',
                UrlKeyGeneratorInterface::ENTITY_POST,
                'integration-stored'
            )
        );
    }

    public function test_reserved_title_surfaces_as_a_localized_exception(): void
    {
        $this->expectException(LocalizedException::class);
        $this->resolver()->resolve('', 'Category', UrlKeyGeneratorInterface::ENTITY_POST);
    }

    public function test_no_slug_and_no_title_surfaces_as_a_localized_exception(): void
    {
        $this->expectException(LocalizedException::class);
        $this->resolver()->resolve('', '', UrlKeyGeneratorInterface::ENTITY_POST);
    }

    private function createPost(string $title, string $urlKey): void
    {
        $factory = Bootstrap::getObjectManager()->get(PostInterfaceFactory::class);
        $repository = Bootstrap::getObjectManager()->get(PostRepositoryInterface::class);

        $post = $factory->create();
        $post->setTitle($title)->setUrlKey($urlKey)->setStoreIds([0]);
        $repository->save($post);
    }

    private function resolver(): UrlKeyResolver
    {
        return Bootstrap::getObjectManager()->get(UrlKeyResolver::class);
    }
}
