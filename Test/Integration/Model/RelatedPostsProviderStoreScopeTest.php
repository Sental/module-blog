<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\RelatedPostsProviderInterface;
use MageOS\Blog\Model\BlogPostStatus;
use PHPUnit\Framework\TestCase;

/**
 * Related posts must never surface content the storefront should not show:
 * unpublished drafts, or posts scoped to a different store view.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
final class RelatedPostsProviderStoreScopeTest extends TestCase
{
    public function test_manual_relation_excludes_drafts(): void
    {
        $draft = $this->createPost('draft-related', BlogPostStatus::Draft, [0]);
        $subject = $this->createPost('subject-a', BlogPostStatus::Published, [0], [
            (int) $draft->getPostId(),
        ]);

        $related = $this->provider()->forPost($subject, 5);

        self::assertSame([], $this->idsOf($related), 'A draft must not appear as a related post.');
    }

    /**
     * @magentoDataFixture Magento/Store/_files/store.php
     */
    public function test_manual_relation_excludes_posts_from_another_store(): void
    {
        $otherStoreOnly = $this->createPost(
            'other-store-related',
            BlogPostStatus::Published,
            [$this->secondStoreId()]
        );
        $subject = $this->createPost('subject-b', BlogPostStatus::Published, [0], [
            (int) $otherStoreOnly->getPostId(),
        ]);

        $related = $this->provider()->forPost($subject, 5);

        self::assertSame(
            [],
            $this->idsOf($related),
            'A post assigned only to store 2 must not appear while resolving for the default store.'
        );
    }

    public function test_manual_relation_includes_published_all_store_post(): void
    {
        $visible = $this->createPost('visible-related', BlogPostStatus::Published, [0]);
        $subject = $this->createPost('subject-c', BlogPostStatus::Published, [0], [
            (int) $visible->getPostId(),
        ]);

        $related = $this->provider()->forPost($subject, 5);

        self::assertSame([(int) $visible->getPostId()], $this->idsOf($related));
    }

    /**
     * The algorithmic fallback filters on status but historically ignored store
     * scope, so it needs the same guarantee as the manual path.
     *
     * @magentoDataFixture Magento/Store/_files/store.php
     */
    public function test_algorithmic_fallback_excludes_posts_from_another_store(): void
    {
        $connection = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $categoryTable = $connection->getTableName('mageos_blog_category');
        $connection->getConnection()->insert($categoryTable, [
            'url_key' => 'shared-cat',
            'title' => 'Shared Cat',
        ]);
        $categoryId = (int) $connection->getConnection()->lastInsertId();

        $otherStoreOnly = $this->createPost(
            'algo-other-store',
            BlogPostStatus::Published,
            [$this->secondStoreId()]
        );
        $otherStoreOnly->setCategoryIds([$categoryId]);
        $this->repository()->save($otherStoreOnly);

        $subject = $this->createPost('algo-subject', BlogPostStatus::Published, [0]);
        $subject->setCategoryIds([$categoryId]);
        $subject = $this->repository()->save($subject);

        $related = $this->provider()->forPost($subject, 5);

        self::assertSame(
            [],
            $this->idsOf($related),
            'The algorithmic fallback must respect store scope too.'
        );
    }

    /**
     * @param int[] $storeIds
     * @param int[] $relatedPostIds
     */
    private function createPost(
        string $urlKey,
        BlogPostStatus $status,
        array $storeIds,
        array $relatedPostIds = []
    ): PostInterface {
        $post = Bootstrap::getObjectManager()->create(PostInterfaceFactory::class)->create();
        $post->setTitle(ucfirst($urlKey))
            ->setUrlKey($urlKey)
            ->setContent('<p>Body</p>')
            ->setStatus($status->value)
            ->setStoreIds($storeIds);

        if ($relatedPostIds !== []) {
            $post->setRelatedPostIds($relatedPostIds);
        }

        return $this->repository()->save($post);
    }

    /**
     * @param PostInterface[] $posts
     *
     * @return int[]
     */
    private function idsOf(array $posts): array
    {
        return array_map(static fn (PostInterface $p): int => (int) $p->getPostId(), $posts);
    }

    private function provider(): RelatedPostsProviderInterface
    {
        return Bootstrap::getObjectManager()->get(RelatedPostsProviderInterface::class);
    }

    private function repository(): PostRepositoryInterface
    {
        return Bootstrap::getObjectManager()->get(PostRepositoryInterface::class);
    }

    /**
     * Resolved rather than hardcoded: mageos_blog_post_store carries a foreign key
     * to store.store_id, so an invented id fails the insert instead of the assertion.
     */
    private function secondStoreId(): int
    {
        $resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        $id = $connection->fetchOne(
            $connection->select()
                ->from($resource->getTableName('store'), ['store_id'])
                ->where('store_id > ?', 1)
                ->order('store_id ASC')
                ->limit(1)
        );

        self::assertNotFalse($id, 'The store fixture should have added a second store view.');

        return (int) $id;
    }
}
