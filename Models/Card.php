<?php
require_once('Response.php');
require_once('Interfaces/Pageable.php');

class Card extends Response implements Pageable {

    public $id;
    public $deckId;
    public $createdOn;
    public $term;
    public $definition;

    private $password;

    public function Create($password, $deckId, $term, $definition) {
        $this->deckId = $deckId;
        $this->term = $term;
        $this->definition = $definition;
        $this->password = $password;
    
        $this->Save();
    }

    private function Valid() {
        if (Utils::IsNullOrWhitespace($this->deckId)) {
            return $this->ResponseError(400, 300, 'Deck ID not found.');
        }

        DB::executeQuery('deck',"SELECT ID FROM decks WHERE id = $this->deckId");
        if (count(DB::$results['deck']) < 1) {
            return $this->ResponseError(400, 300, 'Deck ID not found.');
        }

        DB::executeQuery('user',"SELECT password FROM users JOIN decks ON decks.user_id = users.id WHERE decks.id = $this->deckId");
        $userPassword = DB::$results['user'][0]['password'];

        if (!password_verify($this->password, $userPassword)) {
            return $this->ResponseError(400, 301, 'Authentication failed.');
        }

        if (Utils::IsNullOrWhitespace($this->term)) {
            return $this->ResponseError(400, 302, 'Term is required.');
        }

        if (Utils::IsNullOrWhitespace($this->definition)) {
            return $this->ResponseError(400, 303, 'Definition is required.');
        }

        return true;
    }

    public function Load($id) {
        $results = self::GetCardById($id);
        $this->SetValues($results[0]);

        DB::closeConnection();
    }

    public static function GetCardById($id) {
        $restraint = "WHERE id = $id";
        return self::GetCards($restraint);
    }

    public static function GetCardsByDeckId($id) {
        $restraint = "WHERE deck_id = $id";
        return self::GetCards($restraint);
    }

    public static function GetCards($restraint) {
        DB::executeQuery('card', "SELECT id, deck_id, created_on, term, definition FROM cards $restraint");
        return DB::$results['card'];
    }

    public function SetValues($values) {
        $this->id = DB::$results['card'][0]['id'];
        $this->deckId = DB::$results['card'][0]['deck_id'];
        $this->createdOn = DB::$results['card'][0]['created_on'];
        $this->term = DB::$results['card'][0]['term'];
        $this->definition = DB::$results['card'][0]['definition'];
    }

    public function Save() {
        if (!$this->Valid()) {
            return;
        }
    
        DB::executeSql("INSERT INTO cards (deck_id, term, definition) VALUES ('$this->deckId', '$this->term', '$this->definition')");   

        $this->Load(DB::lastInsertId());
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