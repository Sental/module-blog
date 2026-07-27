<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\UrlKeyGenerator;

use Magento\Framework\Exception\LocalizedException;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;

/**
 * Resolves the url_key (slug, for Author) to persist for an entity being saved.
 *
 * The four admin Save controllers and the eight GraphQL create/update resolvers all need the same
 * fallback chain. It lives here rather than being repeated because the entity setters are
 * non-nullable: handing them a blank value is a TypeError, not a validation error.
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
     * Keeping the stored value ahead of the title means blanking the field on an edit form leaves
     * the current URL alone instead of silently moving the page.
     *
     * @param string|null $submitted Raw submitted url_key/slug, or null when the field was absent.
     * @param string|null $titleSource Post/Category/Tag title, or Author name.
     * @param string $entityType One of UrlKeyGeneratorInterface::ENTITY_*.
     * @param string $existing Currently stored url_key/slug; '' for a new entity.
     * @throws LocalizedException when no usable value is available.
     */
    public function resolve(
        ?string $submitted,
        ?string $titleSource,
        string $entityType,
        string $existing = '',
        ?int $storeId = null,
    ): string {
        $normalized = $this->normalizer->normalize((string) $submitted);
        if ($normalized !== '') {
            return $normalized;
        }

        $existing = trim($existing);
        if ($existing !== '') {
            return $existing;
        }

        $title = trim((string) $titleSource);
        if ($title === '') {
            throw new LocalizedException(
                __('A URL key is required and could not be generated automatically.')
            );
        }

        try {
            return $this->generator->generate($title, $entityType, $storeId);
        } catch (\InvalidArgumentException $e) {
            throw new LocalizedException(
                __('Could not build a URL key from "%1". Please enter one manually.', $title),
                $e
            );
        }
    }
}
