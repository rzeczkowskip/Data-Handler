<?php
namespace Rzeka\DataHandler\Tests;

use PHPUnit\Framework\TestCase;
use Rzeka\DataHandler\DataHydrationTrait;

class DataHydrationTraitTest extends TestCase
{
    public function testHydrateEmptyData()
    {
        $obj = new class {
            use DataHydrationTrait;

            public $test;
        };

        $obj->hydrate([]);

        static::assertNull($obj->test);
    }

    public function testHydrateProperties()
    {
        $title = 'example';
        $description = 'test';

        $obj = new class {
            use DataHydrationTrait;

            public $title;
            public $description;
        };

        $data = [
            'title' => $title,
            'description' => $description
        ];

        $obj->hydrate($data);

        static::assertEquals($title, $obj->title);
        static::assertEquals($description, $obj->description);
    }

    public function testHydrateSetters()
    {
        $title = 'example';
        $description = 'test';

        $obj = new class {
            use DataHydrationTrait;

            private $title;
            private $description;

            public function setTitle(string $title)
            {
                $this->title = $title;
            }

            public function getTitle()
            {
                return $this->title;
            }

            public function setDescription(string $description)
            {
                $this->description = $description;
            }

            public function getDescription()
            {
                return $this->description;
            }
        };

        $data = [
            'title' => $title,
            'description' => $description
        ];

        $obj->hydrate($data);

        static::assertEquals($title, $obj->getTitle());
        static::assertEquals($description, $obj->getDescription());
    }

    public function testConvertToCamelCase()
    {
        $value = 'test';

        $obj = new class {
            use DataHydrationTrait;

            public $camelCase;
        };

        $data = [
            'camel_case' => $value
        ];

        $obj->hydrate($data);

        static::assertEquals($value, $obj->camelCase);
    }

    public function testExceptionOnInvalidDataKey()
    {
        $obj = new class {
            use DataHydrationTrait;

            public $title;
        };

        $data = ['test' => ''];

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Invalid attribute provided "test"');

        $obj->hydrate($data);
    }
}
