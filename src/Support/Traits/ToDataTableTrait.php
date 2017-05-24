<?php

namespace Khill\Lavacharts\Support\Traits;

use Khill\Lavacharts\Exceptions\DataTableCastingException;

trait ToDataTableTrait
{
    /**
     * Create a new DataTable from column and row definitions.
     *
     * @return \Khill\Lavacharts\DataTables\DataTable
     * @throws \Khill\Lavacharts\Exceptions\DataTableCastingException
     */
    public function toDataTable()
    {
        $data = new \Khill\Lavacharts\DataTables\DataTable;

        if (property_exists($this, 'columns')) {
            $columns = $this->columns;
        }

        if (method_exists($this, 'getColumns')) {
            $columns = $this->getColumns();
        }

        if (property_exists($this, 'rows')) {
            $rows = $this->rows;
        }

        if (method_exists($this, 'getRows')) {
            $rows = $this->getRows();
        }

        if (! isset($rows) || ! isset($columns)) {
            throw new DataTableCastingException($this);
        }

        $data->addColumns($columns);
        $data->addRows($rows);

        return $data;
    }


    private function getColumnDefinition()
    {
        return $this->columns;
    }

    public function getRowDefinition()
    {
        return $this->rows;
    }
}

