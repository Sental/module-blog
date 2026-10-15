<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Category;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Controller\Category\View as CategoryViewController;
use MageOS\Blog\Model\Post\PostsByAssignmentProvider;

class Detail implements ArgumentInterface
{
    /**
     * @var PostInterface[]|null
     */
    private ?array $cachedPosts = null;

    /**
     * @var array<int, AuthorInterface|false>
     */
    private array $authorCache = [];

    public function __construct(
        private readonly Registry $registry,
        private readonly UrlInterface $urlBuilder,
        private readonly PostsByAssignmentProvider $postsProvider,
        private readonly StoreManagerInterface $storeManager,
        private readonly AuthorRepositoryInterface $authorRepository,
    ) {
    }

    public function getCategory(): ?CategoryInterface
    {
        $category = $this->registry->registry(CategoryViewController::REGISTRY_KEY);

        return $category instanceof CategoryInterface ? $category : null;
    }

    public function getTitle(): string
    {
        $category = $this->getCategory();

        return $category !== null ? (string) $category->getTitle() : '';
    }

    public function getDescription(): ?string
    {
        return $this->getCategory()?->getDescription();
    }

    public function getCanonicalUrl(): string
    {
        $category = $this->getCategory();

        return $category === null
            ? ''
            : $this->urlBuilder->getUrl(
                '',
                ['_direct' => 'blog/category/' . $category->getUrlKey()]
            );
    }

    /**
     * @return PostInterface[]
     */
    public function getPosts(): array
    {
        if ($this->cachedPosts !== null) {
            return $this->cachedPosts;
        }
        $category = $this->getCategory();
        if ($category === null) {
            return $this->cachedPosts = [];
        }

        $categoryId = $category->getCategoryId();
        if ($categoryId === null) {
            return $this->cachedPosts = [];
        }

        return $this->cachedPosts = $this->postsProvider->byCategory(
            $categoryId,
            (int) $this->storeManager->getStore()->getId(),
        );
    }

    public function getPostUrl(PostInterface $post): string
    {
        return $this->urlBuilder->getUrl(
            '',
            ['_direct' => 'blog/' . $post->getUrlKey()]
        );
    }

    public function getFormattedPublishDate(PostInterface $post): string
    {
        $date = $post->getPublishDate();
        if ($date === null || $date === '') {
            return '';
        }
        try {
            return (new \DateTimeImmutable($date))->format('F j, Y');
        } catch (\Throwable) {
            return '';
        }
    }

    public function getFeaturedImageUrl(PostInterface $post): ?string
    {
        $path = (string) $post->getFeaturedImage();
        if ($path === '') {
            return null;
        }
        $media = rtrim($this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]), '/');
        return $media . '/mageos_blog/' . ltrim($path, '/');
    }

    public function getAuthorName(PostInterface $post): ?string
    {
        $author = $this->loadAuthor($post);
        return $author === null ? null : (string) $author->getName();
    }

    public function getAuthorUrl(PostInterface $post): ?string
    {
        $author = $this->loadAuthor($post);
        if ($author === null) {
            return null;
        }
        $slug = (string) $author->getSlug();
        return $slug === '' ? null : $this->urlBuilder->getUrl(
            '',
            ['_direct' => 'blog/author/' . $slug]
        );
    }

    private function loadAuthor(PostInterface $post): ?AuthorInterface
    {
        $id = $post->getAuthorId();
        if ($id === null || $id <= 0) {
            return null;
        }
        if (\array_key_exists($id, $this->authorCache)) {
            $cached = $this->authorCache[$id];
            return $cached === false ? null : $cached;
        }
        try {
            $author = $this->authorRepository->getById((int) $id);
        } catch (NoSuchEntityException) {
            $this->authorCache[$id] = false;
            return null;
        }
        $this->authorCache[$id] = $author;
        return $author;
    }
}
