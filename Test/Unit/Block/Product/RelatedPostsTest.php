<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Block\Product;

use Magento\Framework\View\Element\Template\Context;
use MageOS\Blog\Block\Product\RelatedPosts;
use MageOS\Blog\ViewModel\Product\RelatedPosts as RelatedPostsViewModel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Cache identities are the difference between a product page that updates when
 * a linked post changes and one that serves stale content until something else
 * flushes it, so the mapping is worth pinning down.
 */
final class RelatedPostsTest extends TestCase
{
    #[Test]
    public function maps_rendered_posts_to_post_cache_tags(): void
    {
        $viewModel = $this->createStub(RelatedPostsViewModel::class);
        $viewModel->method('getPostIds')->willReturn([7, 8]);

        $block = $this->block($viewModel);

        self::assertSame(
            ['mageos_blog_post_7', 'mageos_blog_post_8'],
            $block->getIdentities()
        );
    }

    #[Test]
    public function has_no_identities_when_nothing_rendered(): void
    {
        $viewModel = $this->createStub(RelatedPostsViewModel::class);
        $viewModel->method('getPostIds')->willReturn([]);

        // Correct rather than a fallback: a block that rendered no posts
        // depends on no post, so the page should not be tagged with any.
        self::assertSame([], $this->block($viewModel)->getIdentities());
    }

    #[Test]
    public function has_no_identities_without_a_view_model(): void
    {
        self::assertSame([], $this->block(null)->getIdentities());
    }

    #[Test]
    public function ignores_a_foreign_view_model(): void
    {
        // Layout XML could be overridden to inject anything; the block must not
        // fatal on a view model that is not ours.
        self::assertSame([], $this->block(new \stdClass())->getIdentities());
    }

    private function block(mixed $viewModel): RelatedPosts
    {
        $context = $this->createStub(Context::class);
        $block = new RelatedPosts($context, []);
        $block->setData('view_model', $viewModel);

        return $block;
    }
}
