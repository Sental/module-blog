<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Search;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use MageOS\Blog\Block\BlogCacheIdentities;
use MageOS\Blog\ViewModel\Search\Results as SearchResults;

class Results extends Template implements IdentityInterface
{
    use BlogCacheIdentities;

    /**
     * The posts on the current results page.
     *
     * Search pages vary by query string, so each query caches separately; the
     * identities still ensure an edited post drops every result page it appears
     * on.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $viewModel = $this->getData('view_model');

        return $viewModel instanceof SearchResults
            ? $this->postIdentities($viewModel->getItems())
            : [];
    }
}
