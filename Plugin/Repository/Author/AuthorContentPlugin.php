<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Repository\Author;

use \Exception;
use Magento\Cms\Model\Template\FilterProvider;
use MageOS\Blog\Api\Data\AuthorInterface;
use Psr\Log\LoggerInterface;

class AuthorContentPlugin
{
    public function __construct(
        private readonly FilterProvider $filterProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * after plugin to filter bio directives
     *
     * @param AuthorInterface $subject
     * @param string|null $bio
     * @return string|null
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function afterGetBio(AuthorInterface $subject, ?string $bio): ?string
    {
        if ($bio === null) {
            return $bio;
        }

        $pageFilter = $this->filterProvider->getPageFilter();
        try {
            return $pageFilter->filter($bio);
        } catch (Exception $e) {
            $this->logger->warning($e);
            return $bio;
        }
    }
}
