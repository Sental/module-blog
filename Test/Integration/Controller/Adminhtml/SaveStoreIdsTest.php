<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Controller\Adminhtml;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * "All Store Views" posts store_id 0, which the old parseIdList() dropped along with the assignment.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class SaveStoreIdsTest extends AbstractBackendController
{
    public function test_post_save_keeps_all_store_views(): void
    {
        $this->dispatchSave('mageos_blog/post/save', [
            'title' => 'All Stores Post',
            'url_key' => 'all-stores-post',
            'store_ids' => ['0'],
        ]);

        $this->assertNoErrors();
        self::assertSame(
            [0],
            $this->fetchStoreIds('mageos_blog_post', 'post_id', 'mageos_blog_post_store', 'all-stores-post')
        );
    }

    public function test_category_save_keeps_all_store_views(): void
    {
        $this->dispatchSave('mageos_blog/category/save', [
            'title' => 'All Stores Category',
            'url_key' => 'all-stores-category',
            'store_ids' => ['0'],
        ]);

        $this->assertNoErrors();
        self::assertSame(
            [0],
            $this->fetchStoreIds(
                'mageos_blog_category',
                'category_id',
                'mageos_blog_category_store',
                'all-stores-category'
            )
        );
    }

    public function test_tag_save_keeps_all_store_views(): void
    {
        $this->dispatchSave('mageos_blog/tag/save', [
            'title' => 'All Stores Tag',
            'url_key' => 'all-stores-tag',
            'store_ids' => ['0'],
        ]);

        $this->assertNoErrors();
        self::assertSame(
            [0],
            $this->fetchStoreIds('mageos_blog_tag', 'tag_id', 'mageos_blog_tag_store', 'all-stores-tag')
        );
    }

    public function test_post_save_drops_a_duplicated_store_selection(): void
    {
        $this->dispatchSave('mageos_blog/post/save', [
            'title' => 'Duplicated Stores Post',
            'url_key' => 'duplicated-stores-post',
            'store_ids' => ['1', '1'],
        ]);

        $this->assertNoErrors();
        self::assertSame(
            [1],
            $this->fetchStoreIds('mageos_blog_post', 'post_id', 'mageos_blog_post_store', 'duplicated-stores-post')
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function dispatchSave(string $path, array $data): void
    {
        $formKey = $this->_objectManager->get(FormKey::class)->getFormKey();

        $request = $this->getRequest();
        $request->setMethod(HttpRequest::METHOD_POST);
        $request->setPostValue($data + ['form_key' => $formKey]);

        $this->dispatch('backend/' . $path);
    }

    private function assertNoErrors(): void
    {
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
    }

    /**
     * @return int[]
     */
    private function fetchStoreIds(string $table, string $idColumn, string $pivot, string $urlKey): array
    {
        $resource = $this->_objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        return array_map('intval', $connection->fetchCol(
            $connection->select()
                ->from(['s' => $resource->getTableName($pivot)], ['store_id'])
                ->join(
                    ['e' => $resource->getTableName($table)],
                    'e.' . $idColumn . ' = s.' . $idColumn,
                    []
                )
                ->where('e.url_key = ?', $urlKey)
                ->order('s.store_id ASC')
        ));
    }
}
