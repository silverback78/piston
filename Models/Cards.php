<?php
require_once('Models/Response.php');
require_once('Interfaces/Pageable.php');

class Cards extends Response implements Pageable {

    public $cards;
    public $deck;
    private $username;
    private $deckName;
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

    public function Create($data) {
        if ($this->exception) return;

        if (Utils::IsNullOrWhitespace($this->deckName)) {
            return $this->ResponseError(400, 201, "Deck was not found.");
        }

        if (!$this->Authenticated()) {
            return $this->ResponseError(400, 113, "Authentication failed.");
        }

        $this->cards = array_key_exists('cards', $data) ? $data['cards'] : null;

        $this->Validate('create');
        if (!$this->valid) return;

        foreach ($this->cards as $value) {
            DB::executeSql("INSERT INTO cards (deck_id, term, definition) VALUES (:deckId, :term, :definition)", array(
                ':deckId' => $this->deck->id,
                ':term' => $value['term'],
                ':definition' => $value['definition'],
            ));
        }
        $this->Load();
    }

    public function Read() {
        if ($this->exception) return;
        return $this;
    }

    public function Update($data) {
        if ($this->exception) return;

        if (Utils::IsNullOrWhitespace($this->deckName)) {
            return $this->ResponseError(400, 201, "Deck was not found.");
        }

        if (!$this->Authenticated()) {
            return $this->ResponseError(400, 113, "Authentication failed.");
        }

        $this->cards = array_key_exists('cards', $data) ? $data['cards'] : null;

        $this->Validate('update');
        if (!$this->valid) return;

        DB::executeSql("DELETE FROM cards WHERE deck_id = :deckId", array(
            ':deckId' => $this->deck->id
        ));

        foreach ($this->cards as $value) {
            DB::executeSql("INSERT INTO cards (deck_id, term, definition) VALUES (:deckId, :term, :definition)", array(
                ':deckId' => $this->deck->id,
                ':term' => $value['term'],
                ':definition' => $value['definition'],
            ));
        }
        $this->Load();
    }

    public function Delete() {
        if ($this->exception) return;

        if (Utils::IsNullOrWhitespace($this->deckName)) {
            return $this->ResponseError(400, 201, "Deck was not found.");
        }

        if (!$this->Authenticated()) {
            return $this->ResponseError(400, 113, "Authentication failed.");
        }

        DB::executeSql("DELETE FROM cards WHERE deck_id = :deckId", array(
            ':deckId' => $this->deck->id
        ));

        $this->Load();
    }

    public function Load() {
        if (Utils::IsNullOrWhitespace($this->username) || Utils::IsNullOrWhitespace($this->deckName))
            return;

        if ($this->deck == null) {
            if (Utils::IsNullOrWhitespace($this->suppliedPassword)) {
                $this->deck = new Deck($this->username, $this->deckName);
                if ($this->deck->user->username == null) {
                    return $this->ResponseError($this->deck->user->statusCode, $this->deck->user->referenceCode, $this->deck->user->message);
                }
                if ($this->deck->id == null) {
                    return $this->ResponseError(400, 201, 'Deck was not found.');
                }
            }
            else {
                $this->deck = new Deck($this->username, $this->deckName, $this->suppliedPassword);
                if (!$this->deck->user->authenticated) {
                    return $this->ResponseError($this->deck->user->statusCode, $this->deck->user->referenceCode, $this->deck->user->message);
                }
                if ($this->deck->id == null) {
                    return $this->ResponseError(400, 201, 'Deck was not found.');
                }
            }
        }

        DB::executeQuery('cards',"SELECT id, created_on, term, definition FROM cards WHERE deck_id = :deckId", array(
            ':deckId' => $this->deck->id
        ));
        if (count(DB::$results['cards']) <= 0) {
            $this->cards = null;
            return;
        }

        $this->cards = DB::$results['cards'];
    }

    private function Authenticated() {
        return $this->deck != null && $this->deck->user != null && $this->deck->user->authenticated;
    }

    private function Validate($operation) {
        if ($operation == 'create' || $operation == 'update') {
            if ($this->cards == null || !is_array($this->cards)) {
                return $this->ResponseError(400, 300, 'Invalid data structure for cards.');
            }

            foreach ($this->cards as $value) {
                if (!is_array($value) ||
                    !array_key_exists('term', $value) || !array_key_exists('definition', $value) ||
                    !is_string($value['term']) || !is_string($value['definition'])) {
                    return $this->ResponseError(400, 300, 'Invalid data structure for cards.');
                }
                if (Utils::IsNullOrWhitespace($value['term'])) {
                    return $this->ResponseError(400, 301, 'Term is required.');
                }
                if (Utils::IsNullOrWhitespace($value['definition'])) {
                    return $this->ResponseError(400, 302, 'Definition is required.');
                }
            }
        }

        $this->valid = true;
    }

    public function ResetValues() {
        $this->cards = null;
        $this->username = null;
        $this->deckName = null;
        $this->suppliedPassword = null;
        $this->valid = false;
    }

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