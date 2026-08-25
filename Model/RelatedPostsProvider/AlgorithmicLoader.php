<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\RelatedPostsProvider;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;

class AlgorithmicLoader
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly PostRepositoryInterface $repository
    ) {
    }

    /**
     * Posts sharing the most categories / tags with the subject.
     *
     * `$storeId` precedes `$excludedIds` because it is always supplied; PHP
     * deprecates a required parameter following an optional one.
     *
     * @param int[] $excludedIds
     * @return PostInterface[]
     */
    public function load(PostInterface $post, int $limit, int $storeId, array $excludedIds = []): array
    {
        if ($limit <= 0) {
            return [];
        }

        $postId = (int) $post->getPostId();
        if ($postId <= 0) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $categoryTable = $this->resource->getTableName('mageos_blog_post_category');
        $tagTable = $this->resource->getTableName('mageos_blog_post_tag');
        $postTable = $this->resource->getTableName('mageos_blog_post');

        $categoryIds = $connection->fetchCol(
            $connection->select()->from($categoryTable, ['category_id'])->where('post_id = ?', $postId)
        );
        $tagIds = $connection->fetchCol(
            $connection->select()->from($tagTable, ['tag_id'])->where('post_id = ?', $postId)
        );

        if ($categoryIds === [] && $tagIds === []) {
            return [];
        }

        $excludedIds = array_values(array_unique(array_map('intval', array_merge([$postId], $excludedIds))));

        $union = [];
        if ($categoryIds !== []) {
            $union[] = $connection->select()
                ->from(
                    ['c' => $categoryTable],
                    ['post_id', 'category_id AS term_id', new \Zend_Db_Expr("'cat' AS kind")]
                )
                ->where('c.category_id IN (?)', $categoryIds);
        }
        if ($tagIds !== []) {
            $union[] = $connection->select()
                ->from(['t' => $tagTable], ['post_id', 'tag_id AS term_id', new \Zend_Db_Expr("'tag' AS kind")])
                ->where('t.tag_id IN (?)', $tagIds);
        }

        $unionSelect = $connection->select()->union($union, \Magento\Framework\DB\Select::SQL_UNION_ALL);

        $ranked = $connection->select()
            ->from(['u' => $unionSelect], ['post_id', 'overlap' => new \Zend_Db_Expr('COUNT(*)')])
            ->group('post_id');

        $final = $connection->select()
            ->from(['r' => $ranked], ['post_id'])
            ->joinInner(
                ['p' => $postTable],
                'p.post_id = r.post_id AND p.status = ' . BlogPostStatus::Published->value,
                []
            )
            ->joinLeft(
                ['s' => $this->resource->getTableName('mageos_blog_post_store')],
                's.post_id = p.post_id',
                []
            )
            ->where('r.post_id NOT IN (?)', $excludedIds)
            ->where('s.store_id IN (?) OR s.store_id IS NULL', [$storeId, 0])
            ->group(['r.post_id', 'r.overlap', 'p.publish_date'])
            ->order('r.overlap DESC')
            ->order('p.publish_date DESC')
            ->limit($limit);

        $ids = array_map('intval', $connection->fetchCol($final));
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
