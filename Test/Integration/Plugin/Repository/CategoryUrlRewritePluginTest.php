<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Plugin\Repository;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterfaceFactory;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
final class CategoryUrlRewritePluginTest extends TestCase
{
    public function test_save_creates_url_rewrite_row(): void
    {
        $category = $this->categoryFactory()->create();
        $category->setTitle('News')
            ->setUrlKey('news')
            ->setStoreIds([1]);
        $saved = $this->repository()->save($category);

        $connection = $this->resource()->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_CATEGORY)
                ->where('entity_id = ?', (int) $saved->getCategoryId())
        );
        self::assertNotEmpty($row);
        self::assertSame('blog/category/news', $row['request_path']);
        self::assertStringContainsString('blog/category/view/id/', $row['target_path']);
    }

    public function test_all_store_views_covers_every_store(): void
    {
        $category = $this->categoryFactory()->create();
        $category->setTitle('All Stores')
            ->setUrlKey('all-stores')
            ->setStoreIds([0]);
        $saved = $this->repository()->save($category);

        self::assertSame([0], $saved->getStoreIds());
        self::assertSame($this->storeCount(), $this->countRewrites((int) $saved->getCategoryId()));
    }

    public function test_save_without_store_assignment_still_creates_rewrites(): void
    {
        $category = $this->categoryFactory()->create();
        $category->setTitle('Unassigned')
            ->setUrlKey('unassigned')
            ->setStoreIds([]);
        $saved = $this->repository()->save($category);

        self::assertSame($this->storeCount(), $this->countRewrites((int) $saved->getCategoryId()));
    }

    public function test_slug_change_produces_301_redirect(): void
    {
        $category = $this->categoryFactory()->create();
        $category->setTitle('Original')
            ->setUrlKey('original-category')
            ->setStoreIds([1]);
        $saved = $this->repository()->save($category);

        $saved->setUrlKey('new-category');
        $this->repository()->save($saved);

        $connection = $this->resource()->getConnection();
        $redirectRow = $connection->fetchRow(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_CATEGORY)
                ->where('entity_id = ?', (int) $saved->getCategoryId())
                ->where('request_path = ?', 'blog/category/original-category')
        );
        self::assertNotEmpty($redirectRow);
        self::assertSame('301', (string) $redirectRow['redirect_type']);
    }

    private function countRewrites(int $categoryId): int
    {
        $connection = $this->resource()->getConnection();

        return (int) $connection->fetchOne(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'), ['COUNT(*)'])
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_CATEGORY)
                ->where('entity_id = ?', $categoryId)
                ->where('redirect_type = ?', 0)
        );
    }

    private function storeCount(): int
    {
        return \count(Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->getStores());
    }

    private function repository(): CategoryRepositoryInterface
    {
        return Bootstrap::getObjectManager()->get(CategoryRepositoryInterface::class);
    }

    private function categoryFactory(): CategoryInterfaceFactory
    {
        return Bootstrap::getObjectManager()->get(CategoryInterfaceFactory::class);
    }

    private function resource(): ResourceConnection
    {
        return Bootstrap::getObjectManager()->get(ResourceConnection::class);
    }
}
