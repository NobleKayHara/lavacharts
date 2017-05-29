<?php

namespace Khill\Lavacharts\Exceptions;

use Khill\Lavacharts\DataTables\Columns\ColumnFactory;

class InvalidColumnType extends LavaException
{
    /**
     * InvalidColumnType constructor.
     *
     * @param mixed $invalidType
     */
    public function __construct($invalidType)
    {
        if (is_string($invalidType)) {
            $message = "$invalidType is not a valid column type.";
        } else {
            $message  = gettype($invalidType) . ' is not a valid column type.';
        }

        $message .= ' Must one of [ ' . implode(' | ', ColumnFactory::$types) . ' ]';

        parent::__construct($message);
    }
}
