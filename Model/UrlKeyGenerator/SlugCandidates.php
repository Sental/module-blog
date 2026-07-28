<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\UrlKeyGenerator;

/**
 * The three values UrlKeyResolver picks between, in preference order.
 *
 * Grouped because all three are nullable strings and were trivially swappable as positional
 * parameters. Construct with named arguments.
 */
class SlugCandidates
{
    public function __construct(
        public readonly ?string $submitted = null,
        public readonly ?string $existing = null,
        public readonly ?string $titleSource = null,
    ) {
    }
}
