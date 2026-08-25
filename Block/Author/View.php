<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Author;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use MageOS\Blog\Block\BlogCacheIdentities;
use MageOS\Blog\Model\Author;
use MageOS\Blog\ViewModel\Author\Detail;

class View extends Template implements IdentityInterface
{
    use BlogCacheIdentities;

    /**
     * The author, plus every post listed on their page.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $viewModel = $this->getData('view_model');
        if (!$viewModel instanceof Detail) {
            return [];
        }

        $author = $viewModel->getAuthor();
        if ($author === null) {
            return [];
        }

        return array_values(array_unique(array_merge(
            $this->entityIdentity(Author::CACHE_TAG, $author->getAuthorId()),
            $this->postIdentities($viewModel->getPosts())
        )));
    }
}
