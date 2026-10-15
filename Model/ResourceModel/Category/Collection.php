<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Model\Category;
use MageOS\Blog\Model\ResourceModel\Category as CategoryResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'category_id';

    protected function _construct(): void
    {
        $this->_init(Category::class, CategoryResource::class);
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $connection = $this->getConnection();

        $storeIdsSelect = $connection->select()
        ->from(
            ['ms' => $this->getTable('mageos_blog_category_store')],
            [
                'category_id',
                'store_ids' => new \Zend_Db_Expr(
                    'GROUP_CONCAT(ms.store_id)'
                ),
            ]
        )
        ->group('ms.category_id');

        $this->getSelect()->joinLeft(
            ['ms' => $storeIdsSelect],
            'main_table.category_id = ms.category_id',
            [
                CategoryInterface::STORE_IDS => 'ms.store_ids',
            ]
        );

        return $this;
    }
}
