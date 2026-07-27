<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\UrlKeyGenerator;

/**
 * Turns arbitrary text into a url_key-safe slug.
 *
 * Extracted from MageOS\Blog\Model\UrlKeyGenerator so that UrlKeyResolver can normalize a
 * hand-typed slug without also triggering generate()'s collision suffixing.
 */
class SlugNormalizer
{
    public function normalize(string $value): string
    {
        $decomposed = \Normalizer::normalize($value, \Normalizer::FORM_D) ?: $value;
        $stripped = preg_replace('/\p{M}+/u', '', $decomposed) ?? $decomposed;
        $lower = mb_strtolower($stripped, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';

        return trim($slug, '-');
    }
}
