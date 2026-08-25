<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Post;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;

/**
 * Returns published posts assigned to a category / tag / author, scoped to
 * the current store, ordered by publish_date DESC. Used by the category /
 * tag / author storefront detail ViewModels to populate their post listings.
 */
class PostsByAssignmentProvider
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly PostRepositoryInterface $repository,
    ) {
    }

    /**
     * @return PostInterface[]
     */
    public function byCategory(int $categoryId, int $storeId, int $limit = 50): array
    {
        return $this->load(
            'mageos_blog_post_category',
            'category_id',
            $categoryId,
            $storeId,
            $limit,
        );
    }

    /**
     * @return PostInterface[]
     */
    public function byTag(int $tagId, int $storeId, int $limit = 50): array
    {
        return $this->load(
            'mageos_blog_post_tag',
            'tag_id',
            $tagId,
            $storeId,
            $limit,
        );
    }

    /**
     * Published posts linked to a catalog product, newest first.
     *
     * The pivot's `position` column is deliberately ignored here. It orders the
     * *products within a post*, which is an unrelated merchant intent — reusing
     * it to order posts within a product would surface that arbitrary sequence
     * on the storefront. Recency is the meaningful order for a product page.
     *
     * @return PostInterface[]
     */
    public function byProduct(int $productId, int $storeId, int $limit = 3): array
    {
        return $this->load(
            'mageos_blog_post_related_product',
            'product_id',
            $productId,
            $storeId,
            $limit,
        );
    }

    /**
     * @return PostInterface[]
     */
    public function byAuthor(int $authorId, int $storeId, int $limit = 50): array
    {
        $connection = $this->resource->getConnection();
        $postTable = $this->resource->getTableName('mageos_blog_post');
        $storeTable = $this->resource->getTableName('mageos_blog_post_store');

        $select = $connection->select()
            ->from(['p' => $postTable], ['post_id'])
            ->joinLeft(
                ['s' => $storeTable],
                's.post_id = p.post_id',
                [],
            )
            ->where('p.author_id = ?', $authorId)
            ->where('p.status = ?', BlogPostStatus::Published->value)
            ->where('s.store_id IN (?) OR s.store_id IS NULL', [$storeId, 0])
            ->group('p.post_id')
            ->order('p.publish_date DESC')
            ->limit($limit);

        return $this->hydrate($connection->fetchCol($select));
    }

    /**
     * @return PostInterface[]
     */
    private function load(
        string $pivotTable,
        string $idColumn,
        int $id,
        int $storeId,
        int $limit,
    ): array {
        $connection = $this->resource->getConnection();
        $postTable = $this->resource->getTableName('mageos_blog_post');
        $storeTable = $this->resource->getTableName('mageos_blog_post_store');

        $select = $connection->select()
            ->from(['pv' => $this->resource->getTableName($pivotTable)], [])
            ->joinInner(
                ['p' => $postTable],
                'p.post_id = pv.post_id',
                ['post_id'],
            )
            ->joinLeft(
                ['s' => $storeTable],
                's.post_id = p.post_id',
                [],
            )
            ->where('pv.' . $idColumn . ' = ?', $id)
            ->where('p.status = ?', BlogPostStatus::Published->value)
            ->where('s.store_id IN (?) OR s.store_id IS NULL', [$storeId, 0])
            ->group('p.post_id')
            ->order('p.publish_date DESC')
            ->limit($limit);

        return $this->hydrate($connection->fetchCol($select));
    }

    /**
     * @param string[] $postIds
     *
     * @return PostInterface[]
     */
    private function hydrate(array $postIds): array
    {
        $posts = [];
        foreach ($postIds as $postId) {
            try {
                $posts[] = $this->repository->getById((int) $postId);
            } catch (NoSuchEntityException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // Post was deleted between pivot-fetch and hydrate; skip.
            }
        }

        return $posts;
    }
}
