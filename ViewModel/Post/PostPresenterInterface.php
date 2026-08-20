<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Post;

use MageOS\Blog\Api\Data\PostInterface;

/**
 * Presentation helpers a template needs to render a post summary.
 *
 * This is the contract `MageOS_Blog::post/card.phtml` consumes, so any ViewModel
 * that wants to render post cards implements it rather than growing its own copy.
 *
 * @see PostPresenterTrait for the shared implementation.
 */
interface PostPresenterInterface
{
    public function getPostUrl(PostInterface $post): string;

    public function getFeaturedImageUrl(PostInterface $post): ?string;

    public function getFormattedPublishDate(PostInterface $post): string;

    public function getAuthorName(PostInterface $post): ?string;

    public function getAuthorUrl(PostInterface $post): ?string;
}
