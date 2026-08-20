<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\RelatedPostsProvider;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;

class ManualRelationLoader
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly PostRepositoryInterface $repository
    ) {
    }

    /**
     * Manually assigned related posts, newest merchant-chosen order first.
     *
     * Filters on published status and store scope for the same reason the
     * storefront listings do: an editor assigning a relation is expressing
     * intent, not overriding visibility. A draft or another store view's post
     * must never reach the storefront through this path.
     *
     * @return PostInterface[]
     */
    public function load(PostInterface $post, int $limit, int $storeId): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                ['rel' => $this->resource->getTableName('mageos_blog_post_related_post')],
                ['related_post_id']
            )
            ->joinInner(
                ['p' => $this->resource->getTableName('mageos_blog_post')],
                'p.post_id = rel.related_post_id',
                []
            )
            ->joinLeft(
                ['s' => $this->resource->getTableName('mageos_blog_post_store')],
                's.post_id = p.post_id',
                []
            )
            ->where('rel.post_id = ?', (int) $post->getPostId())
            ->where('p.status = ?', BlogPostStatus::Published->value)
            ->where('s.store_id IN (?) OR s.store_id IS NULL', [$storeId, 0])
            ->group(['rel.related_post_id', 'rel.position'])
            ->order('rel.position ASC')
            ->limit($limit);

        $ids = array_map('intval', $connection->fetchCol($select));
        $items = [];
        foreach ($ids as $id) {
            try {
                $items[] = $this->repository->getById($id);
            } catch (NoSuchEntityException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // Post was deleted between pivot-fetch and hydrate; skip.
            }
        }
        return $items;
    }
}
