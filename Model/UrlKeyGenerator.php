<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\UrlKeyGenerator\CollisionChecker;
use MageOS\Blog\Model\UrlKeyGenerator\SlugNormalizer;

class UrlKeyGenerator implements UrlKeyGeneratorInterface
{
    public function __construct(
        private readonly CollisionChecker $checker,
        private readonly SlugNormalizer $normalizer,
    ) {
    }

    public function generate(string $title, string $entityType, ?int $storeId = null): string
    {
        $base = $this->normalizer->normalize($title);
        if ($base === '' || \in_array($base, self::RESERVED, true)) {
            throw new \InvalidArgumentException("Cannot generate a URL key from '{$title}'.");
        }

        $candidate = $base;
        $suffix = 1;
        while ($this->checker->isTaken($candidate, $entityType, $storeId)) {
            $suffix++;
            $candidate = "{$base}-{$suffix}";
        }

        return $candidate;
    }

    public function validate(string $urlKey, string $entityType, ?int $storeId, ?int $excludingEntityId = null): void
    {
        if (\in_array($urlKey, self::RESERVED, true)) {
            throw new \InvalidArgumentException("URL key '{$urlKey}' is reserved.");
        }
        if ($this->checker->isTaken($urlKey, $entityType, $storeId, $excludingEntityId)) {
            throw new \InvalidArgumentException("URL key '{$urlKey}' is already in use.");
        }
    }
}
