<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Repository\Category;

use \Exception;
use Magento\Cms\Model\Template\FilterProvider;
use MageOS\Blog\Api\Data\CategoryInterface;
use Psr\Log\LoggerInterface;

class CategoryContentPlugin
{
    public function __construct(
        private readonly FilterProvider $filterProvider,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * after plugin to filter description directives
     *
     * @param CategoryInterface $subject
     * @param string|null $description
     * @return string|null
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function afterGetDescription(CategoryInterface $subject, ?string $description): ?string
    {
        if ($description === null) {
            return $description;
        }

        $pageFilter = $this->filterProvider->getPageFilter();
        
        try {
            return $pageFilter->filter($description);
        } catch(Exception $e) {
            $this->logger->warning($e);
            return $description;
        }
    }
}