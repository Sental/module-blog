<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Plugin\Repository;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\TagInterfaceFactory;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
final class TagUrlRewritePluginTest extends TestCase
{
    public function test_save_creates_url_rewrite_row(): void
    {
        $tag = $this->tagFactory()->create();
        $tag->setTitle('Magento')
            ->setUrlKey('magento')
            ->setStoreIds([1]);
        $saved = $this->repository()->save($tag);

        $connection = $this->resource()->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_TAG)
                ->where('entity_id = ?', (int) $saved->getTagId())
        );
        self::assertNotEmpty($row);
        self::assertSame('blog/tag/magento', $row['request_path']);
        self::assertStringContainsString('blog/tag/view/id/', $row['target_path']);
    }

    public function test_all_store_views_covers_every_store(): void
    {
        $tag = $this->tagFactory()->create();
        $tag->setTitle('All Stores')
            ->setUrlKey('all-stores')
            ->setStoreIds([0]);
        $saved = $this->repository()->save($tag);

        self::assertSame([0], $saved->getStoreIds());
        self::assertSame($this->storeCount(), $this->countRewrites((int) $saved->getTagId()));
    }

    public function test_save_without_store_assignment_still_creates_rewrites(): void
    {
        $tag = $this->tagFactory()->create();
        $tag->setTitle('Unassigned')
            ->setUrlKey('unassigned')
            ->setStoreIds([]);
        $saved = $this->repository()->save($tag);

        self::assertSame($this->storeCount(), $this->countRewrites((int) $saved->getTagId()));
    }

    public function test_slug_change_produces_301_redirect(): void
    {
        $tag = $this->tagFactory()->create();
        $tag->setTitle('Original')
            ->setUrlKey('original-tag')
            ->setStoreIds([1]);
        $saved = $this->repository()->save($tag);

        $saved->setUrlKey('new-tag');
        $this->repository()->save($saved);

        $connection = $this->resource()->getConnection();
        $redirectRow = $connection->fetchRow(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_TAG)
                ->where('entity_id = ?', (int) $saved->getTagId())
                ->where('request_path = ?', 'blog/tag/original-tag')
        );
        self::assertNotEmpty($redirectRow);
        self::assertSame('301', (string) $redirectRow['redirect_type']);
    }

    private function countRewrites(int $tagId): int
    {
        $connection = $this->resource()->getConnection();

        return (int) $connection->fetchOne(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'), ['COUNT(*)'])
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_TAG)
                ->where('entity_id = ?', $tagId)
                ->where('redirect_type = ?', 0)
        );
    }

    private function storeCount(): int
    {
        return \count(Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->getStores());
    }

    private function repository(): TagRepositoryInterface
    {
        return Bootstrap::getObjectManager()->get(TagRepositoryInterface::class);
    }

    private function tagFactory(): TagInterfaceFactory
    {
        return Bootstrap::getObjectManager()->get(TagInterfaceFactory::class);
    }

    private function resource(): ResourceConnection
    {
        return Bootstrap::getObjectManager()->get(ResourceConnection::class);
    }
}
