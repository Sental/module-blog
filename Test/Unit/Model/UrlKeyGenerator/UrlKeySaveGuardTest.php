<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\UrlKeyGenerator;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\UrlKeyGenerator\SlugEntity;
use MageOS\Blog\Model\UrlKeyGenerator\SlugNormalizer;
use MageOS\Blog\Model\UrlKeyGenerator\UrlKeyResolver;
use MageOS\Blog\Model\UrlKeyGenerator\UrlKeySaveGuard;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UrlKeySaveGuardTest extends TestCase
{
    private UrlKeyGeneratorInterface&MockObject $generator;
    private UrlKeySaveGuard $guard;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(UrlKeyGeneratorInterface::class);
        $this->guard = new UrlKeySaveGuard(
            new UrlKeyResolver($this->generator, new SlugNormalizer()),
            $this->generator
        );
    }

    #[Test]
    public function generates_from_title_when_the_caller_never_set_a_slug(): void
    {
        $this->generator->expects(self::once())
            ->method('generate')
            ->with('Bypass Path Post', 'post', null)
            ->willReturn('bypass-path-post');

        $object = $this->entity(['title' => 'Bypass Path Post'], [], null);
        $object->expects(self::once())->method('setData')->with('url_key', 'bypass-path-post');

        $this->guard->apply($object, SlugEntity::Post);
    }

    #[Test]
    public function normalizes_a_slug_the_caller_set_verbatim(): void
    {
        $this->generator->expects(self::never())->method('generate');

        $object = $this->entity(['url_key' => 'My Slug!', 'title' => 'Ignored'], [], null);
        $object->expects(self::once())->method('setData')->with('url_key', 'my-slug');

        $this->guard->apply($object, SlugEntity::Post);
    }

    #[Test]
    public function keeps_the_stored_slug_when_an_update_blanks_it(): void
    {
        $this->generator->expects(self::never())->method('generate');

        $object = $this->entity(
            ['url_key' => '', 'title' => 'Renamed'],
            ['url_key' => 'stored-slug'],
            7
        );
        $object->expects(self::once())->method('setData')->with('url_key', 'stored-slug');

        $this->guard->apply($object, SlugEntity::Post);
    }

    #[Test]
    public function validates_the_resolved_slug_excluding_the_entity_itself(): void
    {
        $this->generator->expects(self::once())
            ->method('validate')
            ->with('stored-slug', 'post', null, 7);

        $this->guard->apply(
            $this->entity(['url_key' => 'stored-slug'], ['url_key' => 'stored-slug'], 7),
            SlugEntity::Post
        );
    }

    #[Test]
    public function passes_a_null_exclude_id_for_a_new_entity(): void
    {
        $this->generator->expects(self::once())
            ->method('validate')
            ->with('fresh', 'post', null, null);

        $this->guard->apply($this->entity(['url_key' => 'fresh'], [], null), SlugEntity::Post);
    }

    #[Test]
    public function converts_a_collision_into_a_localized_exception(): void
    {
        $this->generator->method('validate')
            ->willThrowException(new \InvalidArgumentException("URL key 'taken' is already in use."));

        $this->expectException(LocalizedException::class);
        $this->guard->apply($this->entity(['url_key' => 'taken'], [], null), SlugEntity::Post);
    }

    #[Test]
    public function reads_and_writes_the_author_columns(): void
    {
        $this->generator->expects(self::once())
            ->method('generate')
            ->with('Jane Roe', 'author', null)
            ->willReturn('jane-roe');

        $object = $this->entity(['name' => 'Jane Roe'], [], null);
        $object->expects(self::once())->method('setData')->with('slug', 'jane-roe');

        $this->guard->apply($object, SlugEntity::Author);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $origData
     */
    private function entity(array $data, array $origData, ?int $id): AbstractModel&MockObject
    {
        $object = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getOrigData', 'setData', 'getId'])
            ->getMock();

        $object->method('getData')->willReturnCallback(
            static fn (string $key = '', $index = null) => $data[$key] ?? null
        );
        $object->method('getOrigData')->willReturnCallback(
            static fn (string $key = '') => $origData[$key] ?? null
        );
        $object->method('getId')->willReturn($id);

        return $object;
    }
}
