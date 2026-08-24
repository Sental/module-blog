<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Category;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use MageOS\Blog\Block\BlogCacheIdentities;
use MageOS\Blog\Model\Category;
use MageOS\Blog\ViewModel\Category\Detail;

class View extends Template implements IdentityInterface
{
    use BlogCacheIdentities;

    /**
     * The category, plus every post listed on it.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $viewModel = $this->getData('view_model');
        if (!$viewModel instanceof Detail) {
            return [];
        }

        $category = $viewModel->getCategory();
        if ($category === null) {
            return [];
        }

        return array_values(array_unique(array_merge(
            $this->entityIdentity(Category::CACHE_TAG, $category->getCategoryId()),
            $this->postIdentities($viewModel->getPosts())
        )));
    }
}
