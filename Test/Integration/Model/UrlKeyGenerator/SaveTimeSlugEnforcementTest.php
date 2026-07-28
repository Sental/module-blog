<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model\UrlKeyGenerator;

use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterfaceFactory;
use MageOS\Blog\Api\Data\CategoryInterfaceFactory;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\Data\TagInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\ResourceModel\Post as PostResource;
use PHPUnit\Framework\TestCase;

/**
 * Save paths that bypass the admin controllers and the GraphQL resolvers still cannot persist a
 * blank slug, because the invariant lives in each resource model's _beforeSave.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
final class SaveTimeSlugEnforcementTest extends TestCase
{
    public function test_post_saved_without_a_slug_gets_one_generated(): void
    {
        $post = $this->om()->get(PostInterfaceFactory::class)->create();
        $post->setTitle('Guarded Post Alpha');

        $saved = $this->om()->get(PostRepositoryInterface::class)->save($post);

        self::assertSame('guarded-post-alpha', $saved->getUrlKey());
    }

    public function test_category_saved_without_a_slug_gets_one_generated(): void
    {
        $category = $this->om()->get(CategoryInterfaceFactory::class)->create();
        $category->setTitle('Guarded Category Alpha');

        $saved = $this->om()->get(CategoryRepositoryInterface::class)->save($category);

        self::assertSame('guarded-category-alpha', $saved->getUrlKey());
    }

    public function test_tag_saved_without_a_slug_gets_one_generated(): void
    {
        $tag = $this->om()->get(TagInterfaceFactory::class)->create();
        $tag->setTitle('Guarded Tag Alpha');

        $saved = $this->om()->get(TagRepositoryInterface::class)->save($tag);

        self::assertSame('guarded-tag-alpha', $saved->getUrlKey());
    }

    public function test_author_saved_without_a_slug_gets_one_generated(): void
    {
        $author = $this->om()->get(AuthorInterfaceFactory::class)->create();
        $author->setName('Guarded Author Alpha');

        $saved = $this->om()->get(AuthorRepositoryInterface::class)->save($author);

        self::assertSame('guarded-author-alpha', $saved->getSlug());
    }

    public function test_slug_set_to_empty_string_is_repaired_rather_than_stored(): void
    {
        $post = $this->om()->get(PostInterfaceFactory::class)->create();
        $post->setTitle('Guarded Post Beta')->setUrlKey('');

        $saved = $this->om()->get(PostRepositoryInterface::class)->save($post);

        self::assertSame('guarded-post-beta', $saved->getUrlKey());
    }

    public function test_hand_typed_slug_is_normalized_on_save(): void
    {
        $post = $this->om()->get(PostInterfaceFactory::class)->create();
        $post->setTitle('Guarded Post Gamma')->setUrlKey('Not A Slug!');

        $saved = $this->om()->get(PostRepositoryInterface::class)->save($post);

        self::assertSame('not-a-slug', $saved->getUrlKey());
    }

    public function test_blanking_the_slug_on_update_keeps_the_stored_one(): void
    {
        $repository = $this->om()->get(PostRepositoryInterface::class);

        $post = $this->om()->get(PostInterfaceFactory::class)->create();
        $post->setTitle('Guarded Post Delta')->setUrlKey('delta-original');
        $postId = (int) $repository->save($post)->getPostId();

        $loaded = $repository->getById($postId);
        $loaded->setTitle('Guarded Post Delta Renamed')->setUrlKey('');

        self::assertSame('delta-original', $repository->save($loaded)->getUrlKey());
    }

    public function test_direct_resource_save_is_also_guarded(): void
    {
        // Data patches and imports commonly skip the repository.
        $post = $this->om()->get(PostInterfaceFactory::class)->create();
        $post->setTitle('Guarded Post Epsilon');

        $this->om()->get(PostResource::class)->save($post);

        self::assertSame('guarded-post-epsilon', $post->getUrlKey());
    }

    public function test_unsluggable_submitted_slug_is_reported_not_silently_replaced(): void
    {
        $post = $this->om()->get(PostInterfaceFactory::class)->create();
        $post->setTitle('Guarded Post Zeta')->setUrlKey('!!!');

        $this->expectExceptionMessageMatches('/is not valid/');
        $this->expectException(LocalizedException::class);
        $this->om()->get(PostRepositoryInterface::class)->save($post);
    }

    public function test_unsluggable_slug_on_update_does_not_silently_keep_the_old_one(): void
    {
        $repository = $this->om()->get(PostRepositoryInterface::class);

        $post = $this->om()->get(PostInterfaceFactory::class)->create();
        $post->setTitle('Guarded Post Eta')->setUrlKey('eta-original');
        $postId = (int) $repository->save($post)->getPostId();

        $loaded = $repository->getById($postId);
        $loaded->setUrlKey('???');

        $this->expectExceptionMessageMatches('/is not valid/');
        $this->expectException(LocalizedException::class);
        $repository->save($loaded);
    }

    public function test_unsluggable_title_and_no_slug_is_a_localized_exception(): void
    {
        $post = $this->om()->get(PostInterfaceFactory::class)->create();
        $post->setTitle('category'); // reserved, so no slug can be generated

        $this->expectException(LocalizedException::class);
        $this->om()->get(PostRepositoryInterface::class)->save($post);
    }

    private function om(): \Magento\Framework\ObjectManagerInterface
    {
        return Bootstrap::getObjectManager();
    }
}
