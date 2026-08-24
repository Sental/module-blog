<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Product;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use MageOS\Blog\Model\Post;
use MageOS\Blog\ViewModel\Product\RelatedPosts as RelatedPostsViewModel;

/**
 * Related blog posts on the product detail page.
 *
 * Unlike the other storefront blocks in this module this one declares cache
 * identities, because it renders blog content inside a page owned by the
 * catalog. Without them the product page's full-page-cache entry carries no
 * blog cache tags, so editing or unpublishing a linked post would leave the
 * product page serving the old list until something else flushed it.
 */
class RelatedPosts extends Template implements IdentityInterface
{
    /**
     * Drive the tab label from configuration.
     *
     * details.phtml renders the label with
     * `$block->getChildData($alias, 'title')`, which reads this block's `title`
     * data. Setting it here lets mageos_blog/related_posts/title control the
     * tab, which layout XML arguments alone cannot do. The layout argument
     * remains as the fallback when the config value is empty.
     */
    protected function _prepareLayout()
    {
        $viewModel = $this->getRelatedPostsViewModel();
        if ($viewModel !== null) {
            $title = $viewModel->getTitle();
            if ($title !== '') {
                $this->setData('title', $title);
            }
        }

        return parent::_prepareLayout();
    }

    /**
     * Cache tags for the posts actually rendered.
     *
     * Returns [] when nothing rendered — correct, not a fallback: the block
     * emitted no blog content, so the page depends on no blog entity.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $viewModel = $this->getRelatedPostsViewModel();
        if ($viewModel === null) {
            return [];
        }

        return array_map(
            static fn (int $postId): string => Post::CACHE_TAG . '_' . $postId,
            $viewModel->getPostIds()
        );
    }

    private function getRelatedPostsViewModel(): ?RelatedPostsViewModel
    {
        $viewModel = $this->getData('view_model');

        return $viewModel instanceof RelatedPostsViewModel ? $viewModel : null;
    }
}
