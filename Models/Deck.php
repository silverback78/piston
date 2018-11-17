<?php
require_once('Response.php');
require_once('Interfaces/Pageable.php');

class Deck extends Response implements Pageable {

    public $id;
    public $user = null;
    public $createdOn;
    public $deckName;
    public $description;
    private $username;
    private $suppliedPassword;
    private $valid = false;

    function __construct($username = null, $deckName = null, $suppliedPassword = null) {
        if (!Utils::IsNullOrWhitespace($username)) {
            $this->username = Utils::UrlSafe($username);
        }
        if (!Utils::IsNullOrWhitespace($deckName)) {
            $this->deckName = Utils::UrlSafe($deckName);
        }
        if (!Utils::IsNullOrWhitespace($suppliedPassword)) {
            $this->suppliedPassword = $suppliedPassword;
        }
        $this->Load();
    }

    function __destruct() {
        DB::closeConnection();
    }

    public static function GetIdByName($username, $deckName) {
        $userId = User::GetIdByName($username);
        $name = Utils::UrlSafe($deckName);
        DB::executeQuery('name',"SELECT id FROM decks WHERE user_id = $userId AND name = '$name'");
        if (count(DB::$results['name']) > 0) {
            return intval(DB::$results['name'][0]['id']);
        }
        else {
            return -1;
        }
    }

    public function Create($data) {
        if ($this->exception) return;
        if (!$this->Authenticated()) {
            return $this->ResponseError(400, 113, "Authentication failed.");
        }

        $this->description = is_array($data) && array_key_exists('description', $data) ? $data['description'] : null;

        $this->Validate('create');
        if (!$this->valid) return;

        $userId = $this->user->id;
        DB::executeSql("INSERT INTO decks (user_id, name, description) VALUES ($userId, '$this->deckName', '$this->description')");
        $this->Load();
    }

    public function Read() {
        if ($this->exception) return;
        return $this;
    }

    public function Update($data) {
        if ($this->exception) return;
        $userId = null;
        
        if ($this->user != null) {
            $userId = $this->user->id;
        }
        if ($userId != null) {
            DB::executeQuery('deck',"SELECT id FROM decks WHERE user_id = $userId AND name = '$this->deckName'");
            if (count(DB::$results['deck']) <= 0) {
                $this->Create($data);
            }
        }

        if (!$this->Authenticated()) {
            return $this->ResponseError(400, 113, "Authentication failed.");
        }

        $this->description = array_key_exists('description', $data) ? $data['description'] : null;

        $this->Validate('update');
        if (!$this->valid) return;

        $userId = $this->user->id;
        if ($this->id == null) {
            return $this->ResponseError(400, 201, "Deck was not found.");
        }

        DB::executeSql("UPDATE decks SET description = '$this->description' WHERE user_id = $userId AND name = '$this->deckName'");
        $this->Load();
    }

    public function Delete() {
        if ($this->exception) return;

        if (!$this->Authenticated()) {
            return $this->ResponseError(400, 113, "Authentication failed.");
        }

        DB::executeSql("DELETE FROM decks WHERE id = $this->id");
    }

    public function Load() {
        if (Utils::IsNullOrWhitespace($this->username))
            return;

        if ($this->user == null) {
            if (Utils::IsNullOrWhitespace($this->suppliedPassword)) {
                $this->user = new User($this->username);
                if ($this->user->username == null) {
                    return $this->ResponseError($this->user->statusCode, $this->user->referenceCode, $this->user->message);
                }
            }
            else {
                $this->user = new User($this->username, $this->suppliedPassword);
                if ($this->user == null || !$this->user->authenticated) {
                    return $this->ResponseError($this->user->statusCode, $this->user->referenceCode, $this->user->message);
                }
            }
        }

        if (Utils::IsNullOrWhitespace($this->deckName))
        return;

        $userId = $this->user->id;
        DB::executeQuery('deck',"SELECT id, created_on, name, description FROM decks WHERE user_id = $userId AND name = '$this->deckName'");
        if (count(DB::$results['deck']) <= 0) {
            return;
        }

        $this->id = DB::$results['deck'][0]['id'];
        $this->createdOn = DB::$results['deck'][0]['created_on'];
        $this->deckName = DB::$results['deck'][0]['name'];
        $this->description = DB::$results['deck'][0]['description'];
    }

    private function Authenticated() {
        return $this->user != null && $this->user->authenticated;
    }

    private function Validate($operation) {
        if (Utils::IsNullOrWhitespace($this->deckName)) {
            return $this->ResponseError(400, 202, 'Deck name is required.');
        }

        if ($operation == 'create') {
            $userId = $this->user->id;
            DB::executeQuery('name',"SELECT decks.id FROM decks join users on decks.user_id = users.id WHERE decks.name = '$this->deckName' AND users.id = $userId");
            if (count(DB::$results['name']) > 0) {
                return $this->ResponseError(400, 202, 'Duplicate deck name found.');
            }
        }

        $this->valid = true;
    }

    public function ResetValues() {
        $this->id = null;
        $this->createdOn = null;
        $this->deckName = null;
        $this->description = null;
        $this->username = null;
        $this->suppliedPassword = null;
    }

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