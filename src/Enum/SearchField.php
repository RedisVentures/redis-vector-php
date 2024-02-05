<?php

namespace Vladvildanov\PredisVl\Enum;

use Predis\Command\Argument\Search\SchemaFields\NumericField;
use Predis\Command\Argument\Search\SchemaFields\TagField;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SchemaFields\VectorField;
use Vladvildanov\PredisVl\Enum\Traits\EnumNames;

enum SearchField
{
    use EnumNames;

    case tag;
    case text;
    case numeric;
    case vector;

    /**
     * Returns field class corresponding to given case.
     *
     * @return string
     */
    public function fieldMapping(): string
    {
        return match ($this) {
            self::tag => TagField::class,
            self::text => TextField::class,
            self::numeric => NumericField::class,
            self::vector => VectorField::class,
        };
    }
}
