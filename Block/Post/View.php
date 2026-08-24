<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Post;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use MageOS\Blog\Block\BlogCacheIdentities;
use MageOS\Blog\Model\Post;
use MageOS\Blog\ViewModel\Post\Detail;

class View extends Template implements IdentityInterface
{
    use BlogCacheIdentities;

    /**
     * The post itself, plus the related posts rendered in its footer — editing
     * one of those changes this page's markup too.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $viewModel = $this->getData('view_model');
        if (!$viewModel instanceof Detail) {
            return [];
        }

        $post = $viewModel->getPost();
        if ($post === null) {
            return [];
        }

        return array_values(array_unique(array_merge(
            $this->entityIdentity(Post::CACHE_TAG, $post->getPostId()),
            $this->postIdentities($viewModel->getRelatedPosts())
        )));
    }
}
