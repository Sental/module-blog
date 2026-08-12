<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\UrlKeyGenerator;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;

/**
 * Enforces the url_key (Author: slug) invariant at save time.
 *
 * Called from each entity's resource-model _beforeSave, so admin controllers, GraphQL, REST, data
 * patches and direct resource saves all funnel through it and none of them can persist a missing,
 * blank or unnormalized slug. It cannot stop a caller passing null to a non-nullable setter, which
 * is a TypeError before any save happens.
 */
class UrlKeySaveGuard
{
    public function __construct(
        private readonly UrlKeyResolver $resolver,
        private readonly UrlKeyGeneratorInterface $generator,
    ) {
    }

    /**
     * Resolve the slug, write it back onto the entity, then validate it.
     *
     * The previous value comes from getOrigData(), which the resource model populates on load, so
     * blanking the field on an edit keeps the current slug. Collection-hydrated entities carry no
     * orig data; no save path in this module saves one.
     *
     * @throws LocalizedException
     */
    public function apply(AbstractModel $object, SlugEntity $entity): void
    {
        $slugField = $entity->slugField();

        $resolved = $this->resolver->resolve(
            $entity,
            new SlugCandidates(
                submitted: $this->asString($object->getData($slugField)),
                existing: $this->asString($object->getOrigData($slugField)),
                titleSource: $this->asString($object->getData($entity->titleField())),
            )
        );

        $object->setData($slugField, $resolved);

        $id = $object->getId();
        try {
            $this->generator->validate(
                $resolved,
                $entity->entityType(),
                null,
                $id === null ? null : (int) $id
            );
        } catch (\InvalidArgumentException $e) {
            throw new LocalizedException(__($e->getMessage()), $e);
        }
    }

    private function asString(mixed $value): ?string
    {
        return \is_scalar($value) ? (string) $value : null;
    }
}
