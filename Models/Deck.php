<?php
require_once('Response.php');
require_once('Interfaces/Pageable.php');

class Deck extends Response implements Pageable {

    public $id;
    public $user;
    public $createdOn;
    public $name;
    public $description;

    public static function IsNameAvailable($username, $name) {
        $name = Utils::UrlSafe($name);
        DB::executeQuery('name',"SELECT decks.id FROM decks join users on decks.user_id = users.id WHERE decks.name = '$name' AND users.username = '$username'");
        if (count(DB::$results['name']) > 0) {
            return false;
        }

        return true;
    }

    public static function GetIdByName($username, $name) {
        $userId = User::GetIdByName($username);
        $name = Utils::UrlSafe($name);
        DB::executeQuery('name',"SELECT id FROM decks WHERE user_id = $userId AND name = '$name'");
        if (count(DB::$results['name']) > 0) {
            return intval(DB::$results['name'][0]['id']);
        }
        else {
            return -1;
        }
    }

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
        return ['decks.id', 'decks.user_id', 'decks.created_on', 'decks.name', 'decks.description'];
    }

    public function PagerJoinTable() {
        return 'JOIN users on decks.user_id = users.id';
    }

    public function PagerJoinColumns() {
        return 'users.username as username';
    }

    public function PagerDefaultDirection() {
        return 'ASC';
    }

    public function PagerDefaultOrderBy() {
        return 'id';
    }

    public function PagerTableName() {
        return 'decks';
    }
}

?>