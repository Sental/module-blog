<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Plugin\Repository;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterfaceFactory;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
final class AuthorUrlRewritePluginTest extends TestCase
{
    public function test_save_creates_url_rewrite_row(): void
    {
        $author = $this->authorFactory()->create();
        $author->setName('Jane Doe')
            ->setSlug('jane-doe');
        $saved = $this->repository()->save($author);

        $connection = $this->resource()->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_AUTHOR)
                ->where('entity_id = ?', (int) $saved->getAuthorId())
        );
        self::assertNotEmpty($row);
        self::assertSame('blog/author/jane-doe', $row['request_path']);
        self::assertStringContainsString('blog/author/view/id/', $row['target_path']);
    }

    /**
     * Author has no store pivot: the shared store fallback must still cover every store.
     */
    public function test_rewrites_cover_every_store(): void
    {
        $author = $this->authorFactory()->create();
        $author->setName('Every Store')
            ->setSlug('every-store');
        $saved = $this->repository()->save($author);

        $connection = $this->resource()->getConnection();
        $count = (int) $connection->fetchOne(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'), ['COUNT(*)'])
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_AUTHOR)
                ->where('entity_id = ?', (int) $saved->getAuthorId())
                ->where('redirect_type = ?', 0)
        );
        $storeCount = \count(
            Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->getStores()
        );

        self::assertSame($storeCount, $count);
    }

    private function repository(): AuthorRepositoryInterface
    {
        return Bootstrap::getObjectManager()->get(AuthorRepositoryInterface::class);
    }

    private function authorFactory(): AuthorInterfaceFactory
    {
        return Bootstrap::getObjectManager()->get(AuthorInterfaceFactory::class);
    }

    private function resource(): ResourceConnection
    {
        return Bootstrap::getObjectManager()->get(ResourceConnection::class);
    }
}
