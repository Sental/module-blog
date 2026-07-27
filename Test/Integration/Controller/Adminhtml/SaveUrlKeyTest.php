<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Controller\Adminhtml;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Regression cover for the empty-slug TypeError.
 *
 * All four admin Save controllers hydrated scalars in a loop that mapped '' to $setter(null).
 * url_key/slug went through that loop, so submitting the field blank called setUrlKey(null) against
 * a non-nullable setter and the request died with a TypeError before the generate-from-title
 * fallback could run.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class SaveUrlKeyTest extends AbstractBackendController
{
    public function test_post_save_with_blank_url_key_generates_a_slug(): void
    {
        $this->dispatchSave('mageos_blog/post/save', [
            'title' => 'Blank Slug Post',
            'url_key' => '',
        ]);

        $this->assertNoErrors();
        self::assertSame(
            'blank-slug-post',
            $this->fetchSlug('mageos_blog_post', 'url_key', 'Blank Slug Post')
        );
    }

    public function test_category_save_with_blank_url_key_generates_a_slug(): void
    {
        $this->dispatchSave('mageos_blog/category/save', [
            'title' => 'Blank Slug Category',
            'url_key' => '',
        ]);

        $this->assertNoErrors();
        self::assertSame(
            'blank-slug-category',
            $this->fetchSlug('mageos_blog_category', 'url_key', 'Blank Slug Category')
        );
    }

    public function test_tag_save_with_blank_url_key_generates_a_slug(): void
    {
        $this->dispatchSave('mageos_blog/tag/save', [
            'title' => 'Blank Slug Tag',
            'url_key' => '',
        ]);

        $this->assertNoErrors();
        self::assertSame(
            'blank-slug-tag',
            $this->fetchSlug('mageos_blog_tag', 'url_key', 'Blank Slug Tag')
        );
    }

    public function test_author_save_with_blank_slug_generates_a_slug(): void
    {
        $this->dispatchSave('mageos_blog/author/save', [
            'name' => 'Blank Slug Author',
            'slug' => '',
        ]);

        $this->assertNoErrors();
        self::assertSame(
            'blank-slug-author',
            $this->fetchSlug('mageos_blog_author', 'slug', 'Blank Slug Author', 'name')
        );
    }

    public function test_post_save_with_a_hand_typed_slug_normalizes_it(): void
    {
        $this->dispatchSave('mageos_blog/post/save', [
            'title' => 'Typed Slug Post',
            'url_key' => 'My Slug!',
        ]);

        $this->assertNoErrors();
        self::assertSame(
            'my-slug',
            $this->fetchSlug('mageos_blog_post', 'url_key', 'Typed Slug Post')
        );
    }

    public function test_blanking_the_url_key_on_an_edit_keeps_the_current_slug(): void
    {
        $this->dispatchSave('mageos_blog/post/save', [
            'title' => 'Keep My Slug',
            'url_key' => 'original-slug',
        ]);
        $this->assertNoErrors();

        $postId = (int) $this->fetchSlug('mageos_blog_post', 'post_id', 'Keep My Slug');

        $this->dispatchSave('mageos_blog/post/save', [
            'post_id' => $postId,
            'title' => 'Keep My Slug Renamed',
            'url_key' => '',
        ]);

        $this->assertNoErrors();
        self::assertSame(
            'original-slug',
            $this->fetchSlug('mageos_blog_post', 'url_key', 'Keep My Slug Renamed')
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

    private function fetchSlug(
        string $table,
        string $column,
        string $identifier,
        string $identifierColumn = 'title'
    ): string {
        $resource = $this->_objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        return (string) $connection->fetchOne(
            $connection->select()
                ->from($resource->getTableName($table), [$column])
                ->where($identifierColumn . ' = ?', $identifier)
        );
    }
}
