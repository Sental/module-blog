<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Tag;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use MageOS\Blog\Block\BlogCacheIdentities;
use MageOS\Blog\Model\Tag;
use MageOS\Blog\ViewModel\Tag\Detail;

class View extends Template implements IdentityInterface
{
    use BlogCacheIdentities;

    /**
     * The tag, plus every post listed on it.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $viewModel = $this->getData('view_model');
        if (!$viewModel instanceof Detail) {
            return [];
        }

        $tag = $viewModel->getTag();
        if ($tag === null) {
            return [];
        }

        return array_values(array_unique(array_merge(
            $this->entityIdentity(Tag::CACHE_TAG, $tag->getTagId()),
            $this->postIdentities($viewModel->getPosts())
        )));
    }
}
