<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\UrlKeyGenerator;

use Magento\Framework\Exception\LocalizedException;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;

/**
 * Picks the url_key (slug, for Author) to persist for an entity being saved.
 */
class UrlKeyResolver
{
    public function __construct(
        private readonly UrlKeyGeneratorInterface $generator,
        private readonly SlugNormalizer $normalizer,
    ) {
    }

    /**
     * Resolution order: submitted value, then the value already stored, then generated from title.
     *
     * Stored ahead of title means blanking the field on an edit keeps the current URL. A submitted
     * value that normalizes to nothing is rejected rather than quietly replaced.
     *
     * @throws LocalizedException
     */
    public function resolve(
        SlugEntity $entity,
        SlugCandidates $candidates,
        ?int $storeId = null
    ): string {
        $submitted = trim((string) $candidates->submitted);
        $normalized = $this->normalizer->normalize($submitted);
        if ($normalized !== '') {
            return $normalized;
        }
        if ($submitted !== '') {
            throw new LocalizedException(__(
                'URL key "%1" is not valid. Use letters, numbers and hyphens, or leave it empty to '
                . 'generate one automatically.',
                $submitted
            ));
        }

        $existing = trim((string) $candidates->existing);
        if ($existing !== '') {
            return $existing;
        }

        $title = trim((string) $candidates->titleSource);
        if ($title === '') {
            throw new LocalizedException(
                __('A URL key is required and could not be generated automatically.')
            );
        }

        try {
            return $this->generator->generate($title, $entity->entityType(), $storeId);
        } catch (\InvalidArgumentException) {
            throw new LocalizedException(
                __('Could not build a URL key from "%1". Please enter one manually.', $title)
            );
        }
    }
}
