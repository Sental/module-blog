<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel\Post;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\Post;
use MageOS\Blog\Model\ResourceModel\Post as PostResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'post_id';

    protected function _construct(): void
    {
        $this->_init(Post::class, PostResource::class);
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $connection = $this->getConnection();

        $storeIdsSelect = $connection->select()
        ->from(
            ['ms' => $this->getTable('mageos_blog_post_store')],
            [
                'post_id',
                'store_ids' => new \Zend_Db_Expr(
                    'GROUP_CONCAT(ms.store_id)'
                ),
            ]
        )
        ->group('ms.post_id');

        $this->getSelect()->joinLeft(
            ['ms' => $storeIdsSelect],
            'main_table.post_id = ms.post_id',
            [
                PostInterface::STORE_IDS => 'ms.store_ids',
            ]
        );

        return $this;
    }
}
