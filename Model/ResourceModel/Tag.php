<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use MageOS\Blog\Model\UrlKeyGenerator\SlugEntity;
use MageOS\Blog\Model\UrlKeyGenerator\UrlKeySaveGuard;

class Tag extends AbstractDb
{
    public function __construct(
        Context $context,
        private readonly UrlKeySaveGuard $urlKeySaveGuard,
        ?string $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    protected function _construct(): void
    {
        $this->_init('mageos_blog_tag', 'tag_id');
    }

    protected function _beforeSave(AbstractModel $object)
    {
        $this->urlKeySaveGuard->apply($object, SlugEntity::Tag);

        return parent::_beforeSave($object);
    }
}
