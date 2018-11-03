<?php
require_once('Response.php');
require_once('Services/Utils.php');

class Pager extends Response {
    public $page;

    function __construct($pageableObj, $index, $length, $orderBy, $direction, $filter) {
        return $this->GetPage($pageableObj, $index, $length, $orderBy, $direction, $filter);
    }

    private function GetPage($pageableObj, $index, $length, $orderBy, $direction, $filter) {
        $defaultDirection = $pageableObj->PagerDefaultDirection();
        $defaultOrderBy = $pageableObj->PagerDefaultOrderBy();
        $tableName = $pageableObj->PagerTableName();
        $returnColumns = implode(',', $pageableObj->PagerReturnColumns());
        $joinTable = $pageableObj->PagerJoinTable();
        $joinColumns = $pageableObj->PagerJoinColumns();

        if (!Utils::IsNullOrWhitespace($joinColumns)) {
            $joinColumns = ',' . $joinColumns;
        }
        
        $direction = strtoupper($direction);
        if ($direction != 'ASC' && $direction != 'DESC') {
            $direction = $defaultDirection;
        }
    
        if (!empty($orderBy)) {
            DB::executeQuery('orderBy',"SHOW COLUMNS FROM $tableName LIKE '$orderBy'");
            if (count(DB::$results['orderBy']) < 1) {
                $orderBy = null;
            }
        }    
    
        $orderBy = empty($orderBy) ? $defaultOrderBy : $orderBy;
    
        $index = intval($index);
        $length = intval($length);
    
        $index = is_int($index) ? abs($index) : 0;
        $length = is_int($length) ? abs($length) : 0;

        $limit = $length == 0 ? "" : "LIMIT $index, $length";

        $filterSql = '';
        if (is_array($filter) && count($filter) > 0) {
            $sqlPrefix = 'WHERE';

            foreach ($filter as $key => $value) {
                if (!Utils::IsNullOrWhitespace($filterSql)) {
                    $sqlPrefix = 'AND';
                }
                $filterSql = $filterSql . "$sqlPrefix $key = $value ";
            }
        }
   
        DB::executeQuery('page', "SELECT $returnColumns $joinColumns FROM $tableName $joinTable $filterSql ORDER BY $orderBy $direction $limit");
        DB::executeQuery('total_count', "SELECT COUNT(*) as total_count FROM $tableName $filterSql ORDER BY $orderBy $direction");
        DB::closeConnection();
        
        $page['totalCount'] = DB::$results['total_count'][0]['total_count'];
        $page['totalPages'] = $length == 0 ? 1 : floor($page['totalCount'] / $length);
        $page['items'] = DB::$results['page'];

        $this->page = $page;
        return $this;
    }
}

?>