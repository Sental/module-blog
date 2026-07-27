<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\UrlKeyGenerator;

use Magento\Framework\Exception\LocalizedException;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\UrlKeyGenerator\SlugNormalizer;
use MageOS\Blog\Model\UrlKeyGenerator\UrlKeyResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UrlKeyResolverTest extends TestCase
{
    private UrlKeyGeneratorInterface&MockObject $generator;
    private UrlKeyResolver $resolver;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(UrlKeyGeneratorInterface::class);
        $this->resolver = new UrlKeyResolver($this->generator, new SlugNormalizer());
    }

    #[Test]
    public function submitted_value_wins_over_existing_and_title(): void
    {
        $this->generator->expects(self::never())->method('generate');

        self::assertSame(
            'chosen-slug',
            $this->resolver->resolve(
                'chosen-slug',
                'Some Title',
                UrlKeyGeneratorInterface::ENTITY_POST,
                'stored-slug'
            )
        );
    }

    #[Test]
    #[DataProvider('submittedNormalizationCases')]
    public function normalizes_a_hand_typed_slug(string $submitted, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->resolver->resolve($submitted, null, UrlKeyGeneratorInterface::ENTITY_POST, 'stored-slug')
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function submittedNormalizationCases(): array
    {
        return [
            'spaces and punctuation' => ['My Slug!', 'my-slug'],
            'uppercase'              => ['MySlug', 'myslug'],
            'accents'                => ['Café', 'cafe'],
            'surrounding slashes'    => ['/my/slug/', 'my-slug'],
            'surrounding whitespace' => ['  my-slug  ', 'my-slug'],
        ];
    }

    #[Test]
    #[DataProvider('blankSubmittedCases')]
    public function blanking_the_field_on_an_edit_keeps_the_stored_slug(?string $submitted): void
    {
        $this->generator->expects(self::never())->method('generate');

        self::assertSame(
            'stored-slug',
            $this->resolver->resolve(
                $submitted,
                'A Brand New Title',
                UrlKeyGeneratorInterface::ENTITY_POST,
                'stored-slug'
            )
        );
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function blankSubmittedCases(): array
    {
        return [
            'field absent'          => [null],
            'field empty'           => [''],
            'field whitespace only' => ['   '],
            'field unsluggable'     => ['!!!'],
        ];
    }

    #[Test]
    public function generates_from_title_when_creating_without_a_slug(): void
    {
        $this->generator->expects(self::once())
            ->method('generate')
            ->with('Hello World', UrlKeyGeneratorInterface::ENTITY_POST, null)
            ->willReturn('hello-world');

        self::assertSame(
            'hello-world',
            $this->resolver->resolve('', 'Hello World', UrlKeyGeneratorInterface::ENTITY_POST)
        );
    }

    #[Test]
    public function passes_the_store_id_through_to_the_generator(): void
    {
        $this->generator->expects(self::once())
            ->method('generate')
            ->with('Hello World', UrlKeyGeneratorInterface::ENTITY_CATEGORY, 3)
            ->willReturn('hello-world');

        self::assertSame(
            'hello-world',
            $this->resolver->resolve(null, 'Hello World', UrlKeyGeneratorInterface::ENTITY_CATEGORY, '', 3)
        );
    }

    #[Test]
    public function trims_the_stored_slug_before_reusing_it(): void
    {
        self::assertSame(
            'stored-slug',
            $this->resolver->resolve(null, null, UrlKeyGeneratorInterface::ENTITY_POST, "  stored-slug\n")
        );
    }

    #[Test]
    public function unsluggable_title_surfaces_as_a_form_error_not_a_type_error(): void
    {
        $this->generator->method('generate')
            ->willThrowException(new \InvalidArgumentException("Cannot generate a URL key from 'category'."));

        $this->expectException(LocalizedException::class);
        $this->resolver->resolve('', 'category', UrlKeyGeneratorInterface::ENTITY_POST);
    }

    #[Test]
    public function nothing_usable_at_all_surfaces_as_a_form_error(): void
    {
        $this->generator->expects(self::never())->method('generate');

        $this->expectException(LocalizedException::class);
        $this->resolver->resolve(null, null, UrlKeyGeneratorInterface::ENTITY_POST);
    }

    #[Test]
    public function whitespace_only_title_is_treated_as_no_title(): void
    {
        $this->generator->expects(self::never())->method('generate');

        $this->expectException(LocalizedException::class);
        $this->resolver->resolve('', '   ', UrlKeyGeneratorInterface::ENTITY_POST);
    }
}
