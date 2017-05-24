<?php

namespace Khill\Lavacharts\Exceptions;

class DataTableCastingException extends LavaException
{
    public function __construct($obj)
    {
        $message = get_class($obj) . ' failed to be cast as a DataTable.';

        parent::__construct($message);
    }
}
