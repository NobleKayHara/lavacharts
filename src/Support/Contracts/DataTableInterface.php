<?php

namespace Khill\Lavacharts\Support\Contracts;

/**
 * DataTableInterface
 *
 * This interface describes an object that can be turned into a DataTable by Lavacharts
 *
 *
 *
 * @package   Khill\Lavacharts\Support\Contracts
 * @author    Kevin Hill <kevinkhill@gmail.com>
 * @copyright (c) 2017, KHill Designs
 * @link      http://github.com/kevinkhill/lavacharts GitHub Repository Page
 * @link      http://lavacharts.com                   Official Docs Site
 * @license   http://opensource.org/licenses/MIT      MIT
 */
interface DataTableInterface
{
    /**
     * The method that will be called when this object is going to be cast
     * as a DataTable
     *
     * @returns \Khill\Lavacharts\DataTables\DataTable
     */
    public function toDataTable();

    /**
     * Returns an array of arrays describing the columns that will be used
     * to build the DataTable when this object is cast.
     *
     * @return array[][]
     */
    public function getColumns();
    /**
     * return [
     *     ['date', 'Pay Date'],
     *     ['number', 'Direct Deposit']
     * ];
     */


    /**
     * Returns an array of arrays describing the rows that will be added
     * to the DataTable when this object is cast.
     *
     * @return array[][]
     */
    public function getRows();
    /**
     * return $this
     *     ->select(['pay_date', 'direct_deposit'])
     *     ->where('direct_deposit', '<>', null)
     *     ->orderBy('pay_date')
     *     ->get()
     *     ->transform(function ($s) {
     *         return [$s->pay_date, $s->direct_deposit];
     *     })
     *     ->toArray();
     */
}
