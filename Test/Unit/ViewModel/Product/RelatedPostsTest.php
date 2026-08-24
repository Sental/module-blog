<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\ViewModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Post\PostsByAssignmentProvider;
use MageOS\Blog\ViewModel\Product\RelatedPosts;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RelatedPostsTest extends TestCase
{
    private const STORE_ID = 1;
    private const PRODUCT_ID = 42;

    private RequestInterface&MockObject $request;
    private ProductRepositoryInterface&MockObject $productRepository;
    private Config&MockObject $config;
    private PostsByAssignmentProvider&MockObject $provider;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->config = $this->createMock(Config::class);
        $this->provider = $this->createMock(PostsByAssignmentProvider::class);
    }

    #[Test]
    public function returns_nothing_when_the_request_has_no_product_id(): void
    {
        $this->request->method('getParam')->willReturn(null);
        $this->enabled();
        $this->productRepository->expects(self::never())->method('getById');
        $this->provider->expects(self::never())->method('byProduct');

        $vm = $this->viewModel();

        self::assertSame([], $vm->getPosts());
        self::assertFalse($vm->hasPosts());
    }

    #[Test]
    public function returns_nothing_when_the_product_does_not_exist(): void
    {
        $this->request->method('getParam')->willReturn((string) self::PRODUCT_ID);
        $this->productRepository->method('getById')
            ->willThrowException(new NoSuchEntityException(__('nope')));
        $this->enabled();
        $this->provider->expects(self::never())->method('byProduct');

        self::assertSame([], $this->viewModel()->getPosts());
    }

    #[Test]
    public function loads_the_product_with_the_same_arguments_the_pdp_controller_uses(): void
    {
        // Catalog\Helper\Product::initProduct() calls
        //   getById($id, false, $storeManager->getStore()->getId())
        // and ProductRepository memoizes on [editMode, storeId]. Diverging here
        // would miss that cache and load the product a second time per render.
        $this->request->method('getParam')->willReturn((string) self::PRODUCT_ID);
        $this->productRepository->expects(self::once())
            ->method('getById')
            ->with(self::PRODUCT_ID, false, self::STORE_ID)
            ->willReturn($this->makeProduct());
        $this->enabled(3);
        $this->provider->method('byProduct')->willReturn([]);

        $this->viewModel()->getPosts();
    }

    #[Test]
    public function returns_nothing_when_the_module_is_disabled(): void
    {
        $this->withProduct();
        $this->config->method('isEnabled')->willReturn(false);
        $this->config->method('isRelatedPostsEnabled')->willReturn(true);
        $this->provider->expects(self::never())->method('byProduct');

        self::assertSame([], $this->viewModel()->getPosts());
    }

    #[Test]
    public function returns_nothing_when_the_feature_is_disabled(): void
    {
        $this->withProduct();
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isRelatedPostsEnabled')->willReturn(false);
        $this->provider->expects(self::never())->method('byProduct');

        self::assertSame([], $this->viewModel()->getPosts());
    }

    #[Test]
    public function returns_nothing_when_the_limit_is_not_positive(): void
    {
        $this->withProduct();
        $this->enabled(0);
        $this->provider->expects(self::never())->method('byProduct');

        self::assertSame([], $this->viewModel()->getPosts());
    }

    #[Test]
    public function passes_product_store_and_limit_to_the_provider(): void
    {
        $this->withProduct();
        $this->enabled(3);
        $this->provider->expects(self::once())
            ->method('byProduct')
            ->with(self::PRODUCT_ID, self::STORE_ID, 3)
            ->willReturn([$this->makePost(7)]);

        $vm = $this->viewModel();
        $posts = $vm->getPosts();

        self::assertCount(1, $posts);
        self::assertSame(7, (int) $posts[0]->getPostId());
        self::assertTrue($vm->hasPosts());
    }

    #[Test]
    public function queries_the_provider_only_once_across_repeated_calls(): void
    {
        $this->withProduct();
        $this->enabled(3);
        // The template calls getPosts(), and the block calls getPostIds() for
        // its cache identities. Without memoization that is two queries per
        // product page render.
        $this->provider->expects(self::once())
            ->method('byProduct')
            ->willReturn([$this->makePost(7), $this->makePost(8)]);

        $vm = $this->viewModel();
        $vm->getPosts();
        $vm->hasPosts();
        $vm->getPostIds();
        $vm->getPosts();

        self::assertTrue($vm->hasPosts());
    }

    #[Test]
    public function exposes_post_ids_for_cache_identities(): void
    {
        $this->withProduct();
        $this->enabled(3);
        $this->provider->method('byProduct')->willReturn([$this->makePost(7), $this->makePost(8)]);

        self::assertSame([7, 8], $this->viewModel()->getPostIds());
    }

    #[Test]
    public function exposes_no_post_ids_when_there_are_no_posts(): void
    {
        $this->withProduct();
        $this->enabled(3);
        $this->provider->method('byProduct')->willReturn([]);

        self::assertSame([], $this->viewModel()->getPostIds());
        self::assertFalse($this->viewModel()->hasPosts());
    }

    #[Test]
    public function title_comes_from_config(): void
    {
        $this->config->method('getRelatedPostsTitle')->willReturn('From the blog');

        self::assertSame('From the blog', $this->viewModel()->getTitle());
    }

    private function enabled(int $limit = 3): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isRelatedPostsEnabled')->willReturn(true);
        $this->config->method('getRelatedPostsLimit')->willReturn($limit);
    }

    private function withProduct(): void
    {
        $this->request->method('getParam')->willReturn((string) self::PRODUCT_ID);
        $this->productRepository->method('getById')->willReturn($this->makeProduct());
    }

    private function makeProduct(): ProductInterface
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(self::PRODUCT_ID);

        return $product;
    }

    private function makePost(int $id): PostInterface
    {
        $post = $this->createMock(PostInterface::class);
        $post->method('getPostId')->willReturn($id);

        return $post;
    }

    private function viewModel(): RelatedPosts
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new RelatedPosts(
            $this->request,
            $this->productRepository,
            $storeManager,
            $this->createMock(UrlInterface::class),
            $this->createMock(AuthorRepositoryInterface::class),
            $this->config,
            $this->provider
        );
    }
}
