<?php
require_once('Response.php');
require_once('Interfaces/Pageable.php');

class Card extends Response implements Pageable {

    public $id;
    public $deck;
    public $createdOn;
    public $term;
    public $definition;

    function __construct() {
    }

    public function Create() {
    }

    public function Read() {
    }

    public function Update() {
    }

    public function Delete() {
    }

    public function ResetValues() {}

    public function PagerReturnColumns() {
        return ['id', 'deck_id', 'created_on', 'term', 'definition'];
    }

    public function PagerDefaultDirection() {
        return 'ASC';
    }

    public function PagerDefaultOrderBy() {
        return 'id';
    }

    public function PagerTableName() {
        return 'cards';
    }

    public function PagerJoinTable() {
        return null;
    }

    public function PagerJoinColumns(){
        return null;
    }
}

?>