<?php
require_once('Response.php');
require_once('Interfaces/Pageable.php');

class Decks extends Response {

    public $decks;
    public $deckCategories;
    private $username;
    private $userId;


    function __construct($username = null) {
        if (!Utils::IsNullOrWhitespace($username)) {
            $this->username = Utils::UrlSafe($username);
        }
        $this->Load();
    }

    function __destruct() {
        DB::closeConnection();
    }

    public function Read() {
        if ($this->exception) return;
        return $this;
    }

    public function Load() {
        if (Utils::IsNullOrWhitespace($this->username))
            return $this->ResponseError(400, 105, "Unable to load user, username not found.");

        $this->userId = User::GetIdByName($this->username);
        if ($this->userId == -1)
            return $this->ResponseError(400, 105, "Unable to load user, username not found.");

        DB::executeQuery('decks',"SELECT id, category, name, description FROM decks WHERE user_id = $this->userId");
        if (count(DB::$results['decks']) > 0) {
            $this->decks = DB::$results['decks'];
        }

        DB::executeQuery('deckCategories',"SELECT DISTINCT category as name FROM decks WHERE user_id = $this->userId AND decks.category != '' ORDER BY decks.category ASC");
        if (count(DB::$results['deckCategories']) > 0) {
            $this->deckCategories = DB::$results['deckCategories'];
        }
    }

    public function ResetValues() {
        $this->decks = null;
        $this->deckCategories = null;
        $this->username = null;
        $this->userId = null;
    }
}

?>