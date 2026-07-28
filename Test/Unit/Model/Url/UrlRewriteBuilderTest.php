<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Url;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UrlRewriteBuilderTest extends TestCase
{
    /**
     * Store 0 ("All Store Views") and an unassigned entity both mean every store view.
     *
     * @param int[] $storeIds
     * @param int[] $expected
     */
    #[Test]
    #[DataProvider('storeIdCases')]
    public function post_rewrites_cover_the_expected_stores(array $storeIds, array $expected): void
    {
        $rows = $this->builder([1, 2, 3])->buildForPost($this->post(), $storeIds);

        self::assertSame($expected, $this->storeIdsOf($rows));
    }

    /**
     * @return array<string, array{0: int[], 1: int[]}>
     */
    public static function storeIdCases(): array
    {
        return [
            'explicit stores'   => [[1, 3], [1, 3]],
            'all store views'   => [[0], [1, 2, 3]],
            'no assignment'     => [[], [1, 2, 3]],
            'duplicates'        => [[2, 2], [2]],
            'zero plus a store' => [[0, 2], [1, 2, 3]],
        ];
    }

    #[Test]
    public function post_rewrites_point_at_the_slug_and_the_view_action(): void
    {
        $rows = $this->builder([1])->buildForPost($this->post(), [1]);

        self::assertCount(1, $rows);
        self::assertSame(UrlRewriteBuilder::ENTITY_TYPE_POST, $rows[0]->getEntityType());
        self::assertSame('blog/my-post', $rows[0]->getRequestPath());
        self::assertSame('blog/post/view/id/7', $rows[0]->getTargetPath());
        self::assertSame(0, $rows[0]->getRedirectType());
    }

    #[Test]
    public function category_rewrites_fall_back_to_every_store(): void
    {
        $category = $this->createMock(CategoryInterface::class);
        $category->method('getCategoryId')->willReturn(4);
        $category->method('getUrlKey')->willReturn('news');

        $rows = $this->builder([1, 2])->buildForCategory($category, []);

        self::assertSame([1, 2], $this->storeIdsOf($rows));
        self::assertSame('blog/category/news', $rows[0]->getRequestPath());
    }

    #[Test]
    public function tag_rewrites_fall_back_to_every_store(): void
    {
        $tag = $this->createMock(TagInterface::class);
        $tag->method('getTagId')->willReturn(9);
        $tag->method('getUrlKey')->willReturn('php');

        $rows = $this->builder([1, 2])->buildForTag($tag, []);

        self::assertSame([1, 2], $this->storeIdsOf($rows));
        self::assertSame('blog/tag/php', $rows[0]->getRequestPath());
    }

    #[Test]
    public function author_rewrites_cover_every_store(): void
    {
        $author = $this->createMock(AuthorInterface::class);
        $author->method('getAuthorId')->willReturn(2);
        $author->method('getSlug')->willReturn('jane-doe');

        $rows = $this->builder([1, 2, 3])->buildForAuthor($author);

        self::assertSame([1, 2, 3], $this->storeIdsOf($rows));
        self::assertSame('blog/author/jane-doe', $rows[0]->getRequestPath());
    }

    /**
     * @param int[] $storeIds
     */
    private function builder(array $storeIds): UrlRewriteBuilder
    {
        $factory = $this->createMock(UrlRewriteFactory::class);
        $factory->method('create')->willReturnCallback(
            static fn (): UrlRewrite => new UrlRewrite([], new Json())
        );

        $stores = [];
        foreach ($storeIds as $storeId) {
            $store = $this->createMock(StoreInterface::class);
            $store->method('getId')->willReturn($storeId);
            $stores[$storeId] = $store;
        }

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn($stores);

        return new UrlRewriteBuilder($factory, $storeManager);
    }

    private function post(): PostInterface
    {
        $post = $this->createMock(PostInterface::class);
        $post->method('getPostId')->willReturn(7);
        $post->method('getUrlKey')->willReturn('my-post');

        return $post;
    }

    /**
     * @param UrlRewrite[] $rows
     *
     * @return int[]
     */
    private function storeIdsOf(array $rows): array
    {
        return array_map(static fn (UrlRewrite $row): int => (int) $row->getStoreId(), $rows);
    }
}
