<?php
require_once('Models/User.php');
require_once('Models/Deck.php');
require_once('Models/Cards.php');
require_once('Services/Utils.php');
require_once('Services/ReCaptcha.php');
require_once('Services/DB.php');

use PHPUnit\Framework\TestCase;

final class CardsTest extends TestCase
{
    public $testDeckName = 'deck';
    public $testWrongDeckName = 'wrongdeck';
    public $testDeckDescription = 'This is the deck description.';
    public $testUsername = 'username';
    public $testWrongUsername = 'wrongusername';
    public $testPassword = 'password';
    public $testBadPassword = 'notpassword';
    public $testCardTerm = 'term';
    public $testCardDefinition = 'definition';
    public $testCards = [
        array('term' => 'term1', 'definition' => 'definition1'),
        array('term' => 'term2', 'definition' => 'definition2'),
        array('term' => 'term3', 'definition' => 'definition3')
    ];
    public $testCards2 = [
        array('term' => 'term4', 'definition' => 'definition4'),
        array('term' => 'term5', 'definition' => 'definition5'),
        array('term' => 'term6', 'definition' => 'definition6')
    ];

    public function DeleteUser() {
        DB::executeSql("DELETE FROM users WHERE username = '$this->testUsername'");
      }

    public function CreateUser() {
        $this->DeleteUser();
        $user = new User();
        $data = [];
        $data['username'] = $this->testUsername;
        $data['password'] = $this->testPassword;
        $user->Create($data);
    }

    public function CreateDeck() {
        $this->CreateUser();
        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $data = [];
        $data['description'] = $this->testDeckDescription;
        $deck->Create($data);
    }

    public function CreateCards() {
        $this->CreateDeck();
        $cards = new Cards($this->testUsername, $this->testDeckName, $this->testPassword);
        $data['cards'] = $this->testCards;
        $cards->Create($data);
    }

    public function setUp() {
        $this->DeleteUser();
    }

    public function tearDown() {
        $this->DeleteUser();
    }

    public function testInstantiateCardsWithNoParameters()
    {
        $cards = new Cards();
        $this->assertEquals($cards->statusCode, 200);
    }

    public function testInstantiateCardsWithUsernameOnly()
    {
        $cards = new Cards($this->testUsername);
        $this->assertEquals($cards->cards, null);
        $this->assertEquals($cards->statusCode, 200);
    }

    public function testInstantiateCardsWithWrongUsernameOnly()
    {
        $cards = new Cards($this->testWrongUsername);
        $this->assertEquals($cards->cards, null);
        $this->assertEquals($cards->statusCode, 200);
    }

    public function testInstantiateCardsWithUsernameAndDecknameNoUser()
    {
        $cards = new Cards($this->testUsername, $this->testDeckName);
        $this->assertEquals($cards->cards, null);
        $this->assertEquals($cards->statusCode, 400);
    }

    public function testInstantiateCardsWithUsernameAndDecknameWithUserNoDeck()
    {
        $this->CreateUser();
        $cards = new Cards($this->testUsername, $this->testDeckName);
        $this->assertEquals($cards->cards, null);
        $this->assertEquals($cards->statusCode, 400);
        $this->assertEquals($cards->message, 'Deck was not found.');
        $this->assertEquals($cards->referenceCode, 201);
    }

    public function testInstantiateCardsWithUsernameAndDecknameNotAuthenticated()
    {
        $this->CreateDeck();
        $cards = new Cards($this->testUsername, $this->testDeckName);
        $this->assertEquals($cards->cards, null);
        $this->assertEquals($cards->deck->deckName, $this->testDeckName);
        $this->assertEquals($cards->deck->user->username, $this->testUsername);
        $this->assertEquals($cards->deck->user->authenticated, false);
        $this->assertEquals($cards->statusCode, 200);
    }

    public function testInstantiateCardsWithUsernameAndDecknameAuthenticated()
    {
        $this->CreateDeck();
        $cards = new Cards($this->testUsername, $this->testDeckName, $this->testPassword);
        $this->assertEquals($cards->cards, null);
        $this->assertEquals($cards->deck->deckName, $this->testDeckName);
        $this->assertEquals($cards->deck->user->username, $this->testUsername);
        $this->assertEquals($cards->deck->user->authenticated, true);
        $this->assertEquals($cards->statusCode, 200);
    }

    public function testCreateCards()
    {
        $this->CreateDeck();
        $cards = new Cards($this->testUsername, $this->testDeckName, $this->testPassword);
        $data['cards'] = $this->testCards;
        $cards->Create($data);
        $this->assertEquals($cards->cards[1]['term'], 'term2');
        $this->assertEquals($cards->deck->deckName, $this->testDeckName);
        $this->assertEquals($cards->deck->user->username, $this->testUsername);
        $this->assertEquals($cards->deck->user->authenticated, true);
        $this->assertEquals($cards->statusCode, 200);
    }

    public function testReadCards()
    {
        $this->CreateCards();
        $cards = new Cards($this->testUsername, $this->testDeckName, $this->testPassword);
        $cards->Read();
        $this->assertEquals($cards->cards[2]['definition'], 'definition3');
        $this->assertEquals($cards->deck->deckName, $this->testDeckName);
        $this->assertEquals($cards->deck->user->username, $this->testUsername);
        $this->assertEquals($cards->deck->user->authenticated, true);
        $this->assertEquals($cards->statusCode, 200);
    }

    public function testUpdateCards()
    {
        $this->CreateCards();
        $cards = new Cards($this->testUsername, $this->testDeckName, $this->testPassword);
        $data['cards'] = $this->testCards2;
        $cards->Update($data);
        $this->assertEquals($cards->cards[2]['definition'], 'definition6');
        $this->assertEquals($cards->deck->deckName, $this->testDeckName);
        $this->assertEquals($cards->deck->user->username, $this->testUsername);
        $this->assertEquals($cards->deck->user->authenticated, true);
        $this->assertEquals($cards->statusCode, 200);
    }

    public function testDeleteCards()
    {
        $this->CreateCards();
        $cards = new Cards($this->testUsername, $this->testDeckName, $this->testPassword);
        $cards->Delete();
        $this->assertEquals($cards->cards, null);
        $this->assertEquals($cards->deck->deckName, $this->testDeckName);
        $this->assertEquals($cards->deck->user->username, $this->testUsername);
        $this->assertEquals($cards->deck->user->authenticated, true);
        $this->assertEquals($cards->statusCode, 200);
    }

    public function testDeleteCardsBadPassword()
    {
        $this->CreateCards();
        $cards = new Cards($this->testUsername, $this->testDeckName, $this->testBadPassword);
        $cards->Delete();
        $this->assertEquals($cards->statusCode, 400);
        $this->assertEquals($cards->message, 'Authentication failed, no email on file.');
        $this->assertEquals($cards->referenceCode, 108);
    }

    public function testDeleteCardsNoPassword()
    {
        $this->CreateCards();
        $cards = new Cards($this->testUsername, $this->testDeckName);
        $cards->Delete();
        $this->assertEquals($cards->statusCode, 400);
        $this->assertEquals($cards->message, 'Authentication failed.');
        $this->assertEquals($cards->referenceCode, 113);
    }

    public function testDeleteCardsNoUsername()
    {
        $this->CreateCards();
        $cards = new Cards(null, $this->testDeckName, $this->testPassword);
        $cards->Delete();
        $this->assertEquals($cards->statusCode, 400);
        $this->assertEquals($cards->message, 'Authentication failed.');
        $this->assertEquals($cards->referenceCode, 113);
    }

    public function testDeleteCardsWrongUsername()
    {
        $this->CreateCards();
        $cards = new Cards($this->testWrongUsername, $this->testDeckName, $this->testPassword);
        $cards->Delete();
        $this->assertEquals($cards->statusCode, 400);
        $this->assertEquals($cards->message, 'Unable to load user, username not found.');
        $this->assertEquals($cards->referenceCode, 105);
    }
}


