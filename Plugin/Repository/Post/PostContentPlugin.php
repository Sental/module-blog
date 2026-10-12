<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Repository\Post;

use \Exception;
use Magento\Cms\Model\Template\FilterProvider;
use MageOS\Blog\Api\Data\PostInterface;
use Psr\Log\LoggerInterface;

class PostContentPlugin
{
    public function __construct(
        private readonly FilterProvider $filterProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * after plugin to filter content directives
     *
     * @param PostInterface $subject
     * @param string|null $content
     * @return string|null
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function afterGetContent(PostInterface $subject, ?string $content): ?string
    {
        if ($content === null) {
            return $content;
        }

        $pageFilter = $this->filterProvider->getPageFilter();
        
        try {
            return $pageFilter->filter($content);
        } catch (Exception $e) {
            $this->logger->warning($e);
            return $content;
        }
    }

    /**
     * after plugin to filter short content directives
     *
     * @param PostInterface $subject
     * @param string|null $content
     * @return string|null
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function afterGetShortContent(PostInterface $subject, ?string $content): ?string
    {
        if ($content === null) {
            return $content;
        }

        $pageFilter = $this->filterProvider->getPageFilter();
        
        try {
            return $pageFilter->filter($content);
        } catch(Exception $e) {
            $this->logger->warning($e);
            return $content;
        }
    }
}
