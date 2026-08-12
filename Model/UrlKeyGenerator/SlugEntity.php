<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\UrlKeyGenerator;

use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;

/**
 * The four slug-bearing entities and the columns each one uses.
 *
 * Case values must stay equal to UrlKeyGeneratorInterface::ENTITY_*, which the generator and the
 * collision checker key on. SlugEntityTest asserts that.
 */
enum SlugEntity: string
{
    case Post = 'post';
    case Category = 'category';
    case Tag = 'tag';
    case Author = 'author';

    public function entityType(): string
    {
        return $this->value;
    }

    public function slugField(): string
    {
        return match ($this) {
            self::Post => PostInterface::URL_KEY,
            self::Category => CategoryInterface::URL_KEY,
            self::Tag => TagInterface::URL_KEY,
            self::Author => AuthorInterface::SLUG,
        };
    }

    public function titleField(): string
    {
        return match ($this) {
            self::Post => PostInterface::TITLE,
            self::Category => CategoryInterface::TITLE,
            self::Tag => TagInterface::TITLE,
            self::Author => AuthorInterface::NAME,
        };
    }
}
