<?php
require_once('Response.php');
require_once('Interfaces/Pageable.php');
require_once('Services/DB.php');

class User extends Response implements Pageable {

    public $username;
    public $passwordHint;
    public $id;
    public $createdOn;
    public $password;
    
    public function Create($username, $password, $passwordHint) {
        $this->username = Utils::UrlSafe($username);
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->passwordHint = $passwordHint;
    
        $this->Save();
    }

    public static function Authenticate($username, $password) {
        $username = Utils::UrlSafe($username);
        DB::executeQuery('username',"SELECT password FROM users WHERE username = '$username'");
        if (count(DB::$results['username']) > 0) {
            $userPassword = DB::$results['username'][0]['password'];
            return password_verify($password, $userPassword);
        }

        return false;
    }

    public static function IsNameAvailable($username) {
        $username = Utils::UrlSafe($username);
        if (strlen($username) < 7) return false;
        DB::executeQuery('username',"SELECT id FROM users WHERE username = '$username'");
        if (count(DB::$results['username']) > 0) {
            return false;
        }

        return true;
    }

    public static function Search($query) {
        $query = Utils::UrlSafe($query);

        DB::executeQuery('usernames',"SELECT username as text, username as value FROM users WHERE username LIKE '$query%' LIMIT 20");
        return DB::$results['usernames'];
    }

    public static function GetIdByName($username) {
        $username = Utils::UrlSafe($username);
        DB::executeQuery('username',"SELECT id FROM users WHERE username = '$username'");
        if (count(DB::$results['username']) > 0) {
            return intval(DB::$results['username'][0]['id']);
        }
        else {
            return -1;
        }
    }

    private function Valid() {
        if (Utils::IsNullOrWhitespace($this->username)) {
            return $this->ResponseError(400, 100, 'Name is required.');
        }

        if (strlen($this->username) < 7 ) {
            return $this->ResponseError(400, 104, 'User username must be 7 characters or longer.');
        }

        DB::executeQuery('username',"SELECT id FROM users WHERE username = '$this->username'");
        if (count(DB::$results['username']) > 0) {
            return $this->ResponseError(400, 101, "Duplicate username found: $this->username");
        }

        return true;
    }

    public function Load($id) {
        DB::executeQuery('user', "SELECT id, username, password, password_hint, created_on FROM users WHERE id = $id");

        if (count(DB::$results['user']) <= 0) {
            return $this->ResponseError(400, 105, "Unable to load user, id not found: $id");
        }

        $this->SetValues(DB::$results['user'][0]);
        DB::closeConnection();
    }

    public function LoadByName($username) {
        DB::executeQuery('user', "SELECT id, username, password, password_hint, created_on FROM users WHERE username = '$username'");

        if (count(DB::$results['user']) <= 0) {
            return $this->ResponseError(400, 106, "Unable to load user, username not found: $id");
        }

        $this->SetValues(DB::$results['user'][0]);
        DB::closeConnection();
    }

    public function SetValues($values) {
        $this->id = $values['id'];
        $this->username = $values['username'];
        $this->password = $values['password'];
        $this->passwordHint = $values['password_hint'];
        $this->createdOn = $values['created_on'];
    }

    public function Save() {
        if (!$this->Valid()) {
            return;
        }
    
        DB::executeSql("INSERT INTO users (username, password, password_hint) VALUES ('$this->username', '$this->password', '$this->passwordHint')");   

        $this->Load(DB::lastInsertId());
    }

    public function PagerReturnColumns() {
        return ['id', 'created_on', 'username'];
    }

    public function PagerDefaultDirection() {
        return 'ASC';
    }

    public function PagerDefaultOrderBy() {
        return 'id';
    }

    public function PagerTableName() {
        return 'users';
    }

    public function PagerFilterColumn() {
        return null;
    }

    public function PagerJoinTable() {
        return null;
    }

    public function PagerJoinColumns(){
        return null;
    }
}

?>