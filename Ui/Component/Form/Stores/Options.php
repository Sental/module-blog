<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Form\Stores;

use Magento\Store\Ui\Component\Listing\Column\Store\Options as StoreOptions;

/**
 * Store view options with the "All Store Views" entry the core source omits.
 */
class Options extends StoreOptions
{
    public const ALL_STORE_VIEWS = '0';

    protected function generateCurrentOptions(): void
    {
        parent::generateCurrentOptions();

        $this->currentOptions = array_merge(
            [['label' => __('All Store Views'), 'value' => self::ALL_STORE_VIEWS]],
            array_values($this->currentOptions)
        );
    }
}
