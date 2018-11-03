<?php

interface Pageable  {
    public function PagerReturnColumns();
    public function PagerDefaultDirection();
    public function PagerDefaultOrderBy();
    public function PagerTableName();
    public function PagerJoinTable();
    public function PagerJoinColumns();
}

?>