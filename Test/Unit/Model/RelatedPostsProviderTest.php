<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\RelatedPostsProvider;
use MageOS\Blog\Model\RelatedPostsProvider\AlgorithmicLoader;
use MageOS\Blog\Model\RelatedPostsProvider\ManualRelationLoader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RelatedPostsProviderTest extends TestCase
{
    private const STORE_ID = 7;

    private ManualRelationLoader $manual;
    private AlgorithmicLoader $algorithmic;
    private RelatedPostsProvider $provider;

    protected function setUp(): void
    {
        $this->manual = $this->createMock(ManualRelationLoader::class);
        $this->algorithmic = $this->createMock(AlgorithmicLoader::class);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->provider = new RelatedPostsProvider($this->manual, $this->algorithmic, $storeManager);
    }

    #[Test]
    public function returns_empty_when_limit_is_zero(): void
    {
        $this->manual->expects(self::never())->method('load');
        $this->algorithmic->expects(self::never())->method('load');
        self::assertSame([], $this->provider->forPost($this->makePost(1), 0));
    }

    #[Test]
    public function returns_manual_only_when_it_fills_the_limit(): void
    {
        $manualResults = [$this->makePost(2), $this->makePost(3), $this->makePost(4)];
        $this->manual->expects(self::once())
            ->method('load')
            ->willReturn($manualResults);
        $this->algorithmic->expects(self::never())->method('load');

        $result = $this->provider->forPost($this->makePost(1), 3);
        self::assertCount(3, $result);
    }

    #[Test]
    public function fills_gap_with_algorithmic_and_excludes_manual_ids(): void
    {
        $manualResults = [$this->makePost(2)];
        $algorithmicResults = [$this->makePost(3), $this->makePost(4)];

        $this->manual->method('load')->willReturn($manualResults);
        $this->algorithmic->expects(self::once())
            ->method('load')
            ->with(
                self::anything(),
                2,
                self::equalTo(self::STORE_ID),
                self::equalTo([1, 2])
            )
            ->willReturn($algorithmicResults);

        $result = $this->provider->forPost($this->makePost(1), 3);
        self::assertCount(3, $result);
    }

    #[Test]
    public function passes_the_current_store_to_the_manual_loader(): void
    {
        $this->manual->expects(self::once())
            ->method('load')
            ->with(self::anything(), 3, self::equalTo(self::STORE_ID))
            ->willReturn([$this->makePost(2), $this->makePost(3), $this->makePost(4)]);

        $this->provider->forPost($this->makePost(1), 3);
    }

    private function makePost(int $id): PostInterface
    {
        $post = $this->createMock(PostInterface::class);
        $post->method('getPostId')->willReturn($id);
        return $post;
    }
}
