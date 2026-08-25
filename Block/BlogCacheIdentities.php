<?php

declare(strict_types=1);

namespace MageOS\Blog\Block;

use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\Post;

/**
 * Cache-identity helpers shared by the storefront blocks.
 *
 * Every block that renders blog content must declare the entities it rendered,
 * or the page's full-page-cache entry carries no blog cache tags and editing a
 * post leaves the page stale until something else flushes it. That also breaks
 * Cron\PublishScheduledPosts, whose entire mechanism is re-saving a post to
 * invalidate FPC — with nothing tagged, there is nothing for it to invalidate.
 */
trait BlogCacheIdentities
{
    /**
     * Cache tags for a set of posts.
     *
     * An empty list yields no tags, which is correct rather than a fallback:
     * a block that rendered no posts depends on no post.
     *
     * @param PostInterface[] $posts
     *
     * @return string[]
     */
    private function postIdentities(array $posts): array
    {
        $identities = [];
        foreach ($posts as $post) {
            $postId = (int) $post->getPostId();
            if ($postId > 0) {
                $identities[] = Post::CACHE_TAG . '_' . $postId;
            }
        }

        return $identities;
    }

    /**
     * Cache tag for a single entity, or none when the id is missing.
     *
     * @return string[]
     */
    private function entityIdentity(string $cacheTag, int|string|null $entityId): array
    {
        $id = (int) $entityId;

        return $id > 0 ? [$cacheTag . '_' . $id] : [];
    }
}
