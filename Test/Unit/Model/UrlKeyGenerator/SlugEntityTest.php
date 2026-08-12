<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\UrlKeyGenerator;

use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\UrlKeyGenerator\SlugEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SlugEntityTest extends TestCase
{
    /**
     * The generator and the collision checker key on the ENTITY_* strings.
     */
    #[Test]
    #[DataProvider('entityTypeCases')]
    public function entity_type_matches_the_generator_constant(SlugEntity $entity, string $expected): void
    {
        self::assertSame($expected, $entity->entityType());
    }

    /**
     * @return array<string, array{0: SlugEntity, 1: string}>
     */
    public static function entityTypeCases(): array
    {
        return [
            'post'     => [SlugEntity::Post, UrlKeyGeneratorInterface::ENTITY_POST],
            'category' => [SlugEntity::Category, UrlKeyGeneratorInterface::ENTITY_CATEGORY],
            'tag'      => [SlugEntity::Tag, UrlKeyGeneratorInterface::ENTITY_TAG],
            'author'   => [SlugEntity::Author, UrlKeyGeneratorInterface::ENTITY_AUTHOR],
        ];
    }

    #[Test]
    #[DataProvider('fieldCases')]
    public function maps_to_the_right_columns(SlugEntity $entity, string $slugField, string $titleField): void
    {
        self::assertSame($slugField, $entity->slugField());
        self::assertSame($titleField, $entity->titleField());
    }

    /**
     * @return array<string, array{0: SlugEntity, 1: string, 2: string}>
     */
    public static function fieldCases(): array
    {
        return [
            'post'     => [SlugEntity::Post, PostInterface::URL_KEY, PostInterface::TITLE],
            'category' => [SlugEntity::Category, CategoryInterface::URL_KEY, CategoryInterface::TITLE],
            'tag'      => [SlugEntity::Tag, TagInterface::URL_KEY, TagInterface::TITLE],
            'author'   => [SlugEntity::Author, AuthorInterface::SLUG, AuthorInterface::NAME],
        ];
    }

    #[Test]
    public function author_is_the_only_entity_that_deviates_from_url_key_and_title(): void
    {
        self::assertSame('slug', SlugEntity::Author->slugField());
        self::assertSame('name', SlugEntity::Author->titleField());

        foreach ([SlugEntity::Post, SlugEntity::Category, SlugEntity::Tag] as $entity) {
            self::assertSame('url_key', $entity->slugField());
            self::assertSame('title', $entity->titleField());
        }
    }
}
