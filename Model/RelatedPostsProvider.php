<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\RelatedPostsProviderInterface;
use MageOS\Blog\Model\RelatedPostsProvider\AlgorithmicLoader;
use MageOS\Blog\Model\RelatedPostsProvider\ManualRelationLoader;

class RelatedPostsProvider implements RelatedPostsProviderInterface
{
    public function __construct(
        private readonly ManualRelationLoader $manualLoader,
        private readonly AlgorithmicLoader $algorithmicLoader,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * The store is resolved here rather than accepted as a parameter: no caller
     * wants anything but the current store, and adding an optional argument to
     * RelatedPostsProviderInterface would break third-party implementers, which
     * PHP treats as a fatal signature incompatibility even when it has a default.
     *
     * @return PostInterface[]
     */
    public function forPost(PostInterface $post, int $limit = 5): array
    {
        if ($limit <= 0) {
            return [];
        }

        $storeId = (int) $this->storeManager->getStore()->getId();

        $manual = $this->manualLoader->load($post, $limit, $storeId);
        if (\count($manual) >= $limit) {
            return \array_slice($manual, 0, $limit);
        }

        $excluded = array_merge(
            [(int) $post->getPostId()],
            array_map(static fn (PostInterface $p): int => (int) $p->getPostId(), $manual)
        );

        $needed = $limit - \count($manual);
        $algorithmic = $this->algorithmicLoader->load($post, $needed, $storeId, $excluded);

        return \array_slice(array_merge($manual, $algorithmic), 0, $limit);
    }
}
