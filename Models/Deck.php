<?php
require_once('Response.php');
require_once('Interfaces/Pageable.php');

class Deck extends Response implements Pageable {

    public $id;
    public $user;
    public $createdOn;
    public $name;
    public $description;
    public $cards = [];

    private $password;
    private $cardData;

    public function Create($username, $password, $name, $description, $cardData) {

        $user = new User();
        $user->LoadByName($username);
        $this->user = $user;

        if ($this->user->referenceCode == 105) {
            return $this->ResponseError(400, 206, 'Unable to load deck, user was not found.');
        }

        $this->name = Utils::UrlSafe($name);
        $this->description = $description;
        $this->password = $password;
        $this->cardData = $cardData;
    
        $this->Save();
    }
    
    public static function Delete($username, $password, $deckName) {
        $username = Utils::UrlSafe($username);
        $deckName = Utils::UrlSafe($deckName);
        $deck = new Deck();

        $userId = User::GetIdByName($username);
        if ($userId == -1) {
            return $this->ResponseError(400, 206, 'Unable to load deck, user was not found.');
        }

        $deckId = Deck::GetIdByName($username, $deckName);
        if ($deckId == -1) {
            return $deck->ResponseError(400, 207, 'Unable to load deck, deck was not found.');
        }

        DB::executeQuery('user',"SELECT password FROM users WHERE id = $userId");
        $userPassword = DB::$results['user'][0]['password'];

        if (!password_verify($password, $userPassword)) {
            return $deck->ResponseError(400, 201, 'Authentication failed.');
        }

        DB::executeSql("DELETE FROM decks WHERE id = $deckId");

        return $deck;
    }

    public static function IsNameAvailable($username, $name) {
        $name = Utils::UrlSafe($name);
        DB::executeQuery('name',"SELECT decks.id FROM decks join users on decks.user_id = users.id WHERE decks.name = '$name' AND users.username = '$username'");
        if (count(DB::$results['name']) > 0) {
            return false;
        }

        return true;
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

    private function Valid() {
        if (Utils::IsNullOrWhitespace($this->user->id)) {
            return $this->ResponseError(400, 200, 'User ID not found.');
        }

        $userId = $this->user->id;
        DB::executeQuery('user',"SELECT ID FROM users WHERE id = $userId");
        if (count(DB::$results['user']) < 1) {
            return $this->ResponseError(400, 200, 'User ID not found.');
        }

        DB::executeQuery('user',"SELECT password FROM users WHERE id = $userId");
        $userPassword = DB::$results['user'][0]['password'];

        if (!password_verify($this->password, $userPassword)) {
            return $this->ResponseError(400, 201, 'Authentication failed.');
        }

        if (Utils::IsNullOrWhitespace($this->name)) {
            return $this->ResponseError(400, 202, 'Name is required.');
        }

        $cardsArr = json_decode($this->cardData, true);

        if (!Utils::IsNullOrWhitespace($this->cardData) && !is_array($cardsArr)) {
            return $this->ResponseError(400, 203, 'Cards were found but could not be parsed.');
        }

        $this->cardData = is_array($cardsArr) ? $cardsArr : null;

        if ($this->cardData != null) {
            foreach ($this->cardData as $value) {
                if (Utils::IsNullOrWhitespace($value['term']) || Utils::IsNullOrWhitespace($value['definition'])) {
                    return $this->ResponseError(400, 204, 'Cards were parsed but contained some null or empty values.');
                }
            }
        }

        return true;
    }

    public function Load($id) {
        DB::executeQuery('deck', "SELECT id, user_id, created_on, name, description FROM decks WHERE id = $id");

        $user = new User();
        $user->Load(DB::$results['deck'][0]['user_id']);
        $this->user = $user;

        $this->id = DB::$results['deck'][0]['id'];
        $this->user->id = DB::$results['deck'][0]['user_id'];
        $this->createdOn = DB::$results['deck'][0]['created_on'];
        $this->name = DB::$results['deck'][0]['name'];
        $this->description = DB::$results['deck'][0]['description'];

        if (count($this->cards) <= 0) {
            $results = Card::GetCardsByDeckId($id);

            if (count($results) > 0) {
                foreach ($results as $key => $value) {
                    $card = new Card();
                    $card->SetValues($results[$key]);
                    array_push($this->cards, $card);
                }
            }
        }

        DB::closeConnection();
    }

    public function Save() {
        if (!$this->Valid()) {
            return;
        }

        $userId = $this->user->id;

        DB::executeQuery('name',"SELECT id FROM decks WHERE user_id = $userId AND name = '$this->name'");
        if (count(DB::$results['name']) > 0) {
            $lastInsertId = DB::$results['name'][0]['id'];
            DB::executeSql("DELETE FROM cards WHERE deck_id = $lastInsertId");
            DB::executeSql("UPDATE decks SET name = '$this->name', description = '$this->description' WHERE id = $lastInsertId");
        }
        else {
            DB::executeSql("INSERT INTO decks (user_id, name, description) VALUES ('$userId', '$this->name', '$this->description')");
            $lastInsertId = DB::lastInsertId();
        }

        if ($this->cardData != null && is_array($this->cardData)) {
            foreach ($this->cardData as $value) {
                $card = new Card();
                $card->Create($this->password, $lastInsertId, $value['term'], $value['definition']);
                array_push($this->cards, $card);
            }
        }

        $this->Load($lastInsertId);
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