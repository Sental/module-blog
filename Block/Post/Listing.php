<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Post;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use MageOS\Blog\Block\BlogCacheIdentities;
use MageOS\Blog\ViewModel\Post\Listing as PostListing;

class Listing extends Template implements IdentityInterface
{
    use BlogCacheIdentities;

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        $viewModel = $this->getData('view_model');

        return $viewModel instanceof PostListing
            ? $this->postIdentities($viewModel->getItems())
            : [];
    }
}
