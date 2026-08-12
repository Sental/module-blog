<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Console\Command\RegenerateUrlRewrites;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
final class RegenerateUrlRewritesTest extends TestCase
{
    public function test_regenerate_restores_rewrites_and_store_links(): void
    {
        $postId = $this->createBrokenPost('backfill-me');

        $tester = $this->tester();
        $tester->execute(['--entity' => 'post']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertSame([0], $this->storeLinks($postId));
        self::assertGreaterThan(0, $this->countRewrites($postId));
    }

    public function test_dry_run_writes_nothing(): void
    {
        $postId = $this->createBrokenPost('leave-me-alone');

        $tester = $this->tester();
        $tester->execute(['--entity' => 'post', '--dry-run' => true]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('[dry run]', $tester->getDisplay());
        self::assertSame([], $this->storeLinks($postId));
        self::assertSame(0, $this->countRewrites($postId));
    }

    public function test_unknown_entity_type_fails(): void
    {
        $tester = $this->tester();
        $tester->execute(['--entity' => 'widget']);

        self::assertSame(1, $tester->getStatusCode());
    }

    /**
     * Reproduces pre-fix data: the row exists, its store pivot is empty and no rewrite was written.
     */
    private function createBrokenPost(string $urlKey): int
    {
        $post = $this->postFactory()->create();
        $post->setTitle('Backfill ' . $urlKey)
            ->setUrlKey($urlKey)
            ->setStoreIds([1]);
        $postId = (int) $this->repository()->save($post)->getPostId();

        $connection = $this->resource()->getConnection();
        $connection->delete(
            $this->resource()->getTableName('mageos_blog_post_store'),
            ['post_id = ?' => $postId]
        );
        $connection->delete(
            $this->resource()->getTableName('url_rewrite'),
            [
                'entity_type = ?' => UrlRewriteBuilder::ENTITY_TYPE_POST,
                'entity_id = ?' => $postId,
            ]
        );

        return $postId;
    }

    /**
     * @return int[]
     */
    private function storeLinks(int $postId): array
    {
        $connection = $this->resource()->getConnection();

        return array_map('intval', $connection->fetchCol(
            $connection->select()
                ->from($this->resource()->getTableName('mageos_blog_post_store'), ['store_id'])
                ->where('post_id = ?', $postId)
        ));
    }

    private function countRewrites(int $postId): int
    {
        $connection = $this->resource()->getConnection();

        return (int) $connection->fetchOne(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'), ['COUNT(*)'])
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_POST)
                ->where('entity_id = ?', $postId)
        );
    }

    private function tester(): CommandTester
    {
        return new CommandTester(
            Bootstrap::getObjectManager()->create(RegenerateUrlRewrites::class)
        );
    }

    private function repository(): PostRepositoryInterface
    {
        return Bootstrap::getObjectManager()->get(PostRepositoryInterface::class);
    }

    private function postFactory(): PostInterfaceFactory
    {
        return Bootstrap::getObjectManager()->get(PostInterfaceFactory::class);
    }

    private function resource(): ResourceConnection
    {
        return Bootstrap::getObjectManager()->get(ResourceConnection::class);
    }
}
