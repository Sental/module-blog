<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Repository\Tag;

use \Exception;
use Magento\Cms\Model\Template\FilterProvider;
use MageOS\Blog\Api\Data\TagInterface;
use Psr\Log\LoggerInterface;

class TagContentPlugin
{
    public function __construct(
        private readonly FilterProvider $filterProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * after plugin to filter description directives
     *
     * @param TagInterface $subject
     * @param string|null $description
     * @return string|null
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function afterGetDescription(TagInterface $subject, ?string $description): ?string
    {
        if ($description === null) {
            return $description;
        }

        $pageFilter = $this->filterProvider->getPageFilter();
        
        try {
            return $pageFilter->filter($description);
        } catch (Exception $e) {
            $this->logger->warning($e);
            return $description;
        }
    }
}
