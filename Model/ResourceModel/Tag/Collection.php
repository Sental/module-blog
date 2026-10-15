<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel\Tag;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Model\ResourceModel\Tag as TagResource;
use MageOS\Blog\Model\Tag;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'tag_id';

    protected function _construct(): void
    {
        $this->_init(Tag::class, TagResource::class);
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $connection = $this->getConnection();

        $storeIdsSelect = $connection->select()
        ->from(
            ['ms' => $this->getTable('mageos_blog_tag_store')],
            [
                'tag_id',
                'store_ids' => new \Zend_Db_Expr(
                    'GROUP_CONCAT(ms.store_id)'
                ),
            ]
        )
        ->group('ms.tag_id');

        $this->getSelect()->joinLeft(
            ['ms' => $storeIdsSelect],
            'main_table.tag_id = ms.tag_id',
            [
                TagInterface::STORE_IDS => 'ms.store_ids',
            ]
        );
    }

    /**
     * Add field filter to collection
     *
     * @see self::_getConditionSql for $condition
     *
     * @param string|array $field
     * @param null|string|array $condition
     *
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (is_string($field) && $field === 'tag_id') {
            $field = 'main_table.tag_id';
        } elseif (is_array($field)) {
            $field = array_map(
                static fn (string $fieldName): string =>
                    $fieldName === 'tag_id'
                        ? 'main_table.tag_id'
                        : $fieldName,
                $field
            );
        }

        return parent::addFieldToFilter($field, $condition);
    }
}
