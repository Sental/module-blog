<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Post\PostsByAssignmentProvider;
use PHPUnit\Framework\TestCase;

/**
 * Reverse lookup behind the PDP related-posts block: given a product, which
 * published posts has an editor linked to it in this store view?
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
final class PostsByProductTest extends TestCase
{
    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function test_returns_published_linked_post(): void
    {
        $productId = $this->productId();
        $post = $this->createPost('linked-post', BlogPostStatus::Published, [0], [$productId]);

        $found = $this->provider()->byProduct($productId, 1, 3);

        self::assertSame([(int) $post->getPostId()], $this->idsOf($found));
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function test_excludes_draft_posts(): void
    {
        $productId = $this->productId();
        $this->createPost('draft-linked', BlogPostStatus::Draft, [0], [$productId]);

        $found = $this->provider()->byProduct($productId, 1, 3);

        self::assertSame([], $this->idsOf($found), 'A draft must not reach the product page.');
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Store/_files/store.php
     */
    public function test_excludes_posts_scoped_to_another_store(): void
    {
        $productId = $this->productId();
        $this->createPost('other-store-linked', BlogPostStatus::Published, [$this->secondStoreId()], [$productId]);

        $found = $this->provider()->byProduct($productId, 1, 3);

        self::assertSame([], $this->idsOf($found));
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function test_honours_the_limit(): void
    {
        $productId = $this->productId();
        foreach (['limit-a', 'limit-b', 'limit-c'] as $slug) {
            $this->createPost($slug, BlogPostStatus::Published, [0], [$productId]);
        }

        $found = $this->provider()->byProduct($productId, 1, 2);

        self::assertCount(2, $found);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function test_returns_empty_for_a_product_with_no_links(): void
    {
        self::assertSame([], $this->provider()->byProduct($this->productId(), 1, 3));
    }

    /**
     * @param int[] $storeIds
     * @param int[] $relatedProductIds
     */
    private function createPost(
        string $urlKey,
        BlogPostStatus $status,
        array $storeIds,
        array $relatedProductIds
    ): PostInterface {
        $post = Bootstrap::getObjectManager()->create(PostInterfaceFactory::class)->create();
        $post->setTitle(ucfirst($urlKey))
            ->setUrlKey($urlKey)
            ->setContent('<p>Body</p>')
            ->setStatus($status->value)
            ->setStoreIds($storeIds)
            ->setRelatedProductIds($relatedProductIds);

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

    private function productId(): int
    {
        $product = Bootstrap::getObjectManager()
            ->get(ProductRepositoryInterface::class)
            ->get('simple');

        return (int) $product->getId();
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

    private function provider(): PostsByAssignmentProvider
    {
        return Bootstrap::getObjectManager()->get(PostsByAssignmentProvider::class);
    }

    private function repository(): PostRepositoryInterface
    {
        return Bootstrap::getObjectManager()->get(PostRepositoryInterface::class);
    }
}
