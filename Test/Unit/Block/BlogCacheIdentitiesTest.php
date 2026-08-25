<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Block;

use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Block\BlogCacheIdentities;
use MageOS\Blog\Model\Category;
use MageOS\Blog\Model\Post;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The trait behind every blog block's getIdentities(). Ten blocks share it, so
 * a defect here silently disables full-page-cache invalidation across the whole
 * storefront.
 */
final class BlogCacheIdentitiesTest extends TestCase
{
    #[Test]
    public function maps_posts_to_cache_tags(): void
    {
        $subject = $this->subject();

        self::assertSame(
            ['mageos_blog_post_3', 'mageos_blog_post_9'],
            $subject->posts([$this->post(3), $this->post(9)])
        );
    }

    #[Test]
    public function skips_posts_without_an_id(): void
    {
        // An unsaved or partially hydrated entity would otherwise produce
        // "mageos_blog_post_0", a tag that matches nothing and quietly weakens
        // invalidation instead of failing.
        $subject = $this->subject();

        self::assertSame(
            ['mageos_blog_post_5'],
            $subject->posts([$this->post(0), $this->post(5), $this->post(null)])
        );
    }

    #[Test]
    public function returns_no_tags_for_an_empty_list(): void
    {
        self::assertSame([], $this->subject()->posts([]));
    }

    #[Test]
    public function builds_an_entity_tag_from_its_id(): void
    {
        self::assertSame(
            ['mageos_blog_category_4'],
            $this->subject()->entity(Category::CACHE_TAG, 4)
        );
    }

    #[Test]
    public function returns_no_entity_tag_without_an_id(): void
    {
        $subject = $this->subject();

        self::assertSame([], $subject->entity(Post::CACHE_TAG, null));
        self::assertSame([], $subject->entity(Post::CACHE_TAG, 0));
    }

    private function post(?int $id): PostInterface
    {
        $post = $this->createStub(PostInterface::class);
        $post->method('getPostId')->willReturn($id);

        return $post;
    }

    /**
     * The trait's helpers are private by design — blocks use them, callers do
     * not. This exposes them for testing without widening the real API.
     */
    private function subject(): object
    {
        return new class () {
            use BlogCacheIdentities;

            /**
             * @param PostInterface[] $posts
             *
             * @return string[]
             */
            public function posts(array $posts): array
            {
                return $this->postIdentities($posts);
            }

            /**
             * @return string[]
             */
            public function entity(string $tag, int|string|null $id): array
            {
                return $this->entityIdentity($tag, $id);
            }
        };
    }
}
