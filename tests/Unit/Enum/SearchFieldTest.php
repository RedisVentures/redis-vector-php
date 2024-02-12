<?php

namespace Vladvildanov\PredisVl\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Predis\Command\Argument\Search\SchemaFields\GeoField;
use Predis\Command\Argument\Search\SchemaFields\NumericField;
use Predis\Command\Argument\Search\SchemaFields\TagField;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SchemaFields\VectorField;
use Vladvildanov\PredisVl\Enum\SearchField;

class SearchFieldTest extends TestCase
{
    /**
     * @dataProvider mappingProvider
     * @param SearchField $enum
     * @param string $expectedClass
     * @return void
     */
    public function testFieldMapping(SearchField $enum, string $expectedClass): void
    {
        $this->assertSame($expectedClass, $enum->fieldMapping());
    }

    /**
     * @return void
     */
    public function testNames(): void
    {
        $this->assertSame(['tag', 'text', 'numeric', 'vector', 'geo'], SearchField::names());
    }

    /**
     * @dataProvider fromNameProvider
     * @param string $name
     * @param SearchField $expectedEnum
     * @return void
     */
    public function testFromName(string $name, SearchField $expectedEnum): void
    {
        $this->assertSame(SearchField::fromName($name), $expectedEnum);
    }

    public static function mappingProvider(): array
    {
        return [
            'tag' => [SearchField::tag, TagField::class],
            'text' => [SearchField::text, TextField::class],
            'numeric' => [SearchField::numeric, NumericField::class],
            'vector' => [SearchField::vector, VectorField::class],
            'geo' => [SearchField::geo, GeoField::class],
        ];
    }

    public static function fromNameProvider(): array
    {
        return [
            ['tag', SearchField::tag],
            ['text', SearchField::text],
            ['numeric', SearchField::numeric],
            ['vector', SearchField::vector],
            ['geo', SearchField::geo],
        ];
    }
}