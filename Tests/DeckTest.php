<?php
require_once('Models/User.php');
require_once('Models/Deck.php');
require_once('Services/Utils.php');
require_once('Services/DB.php');
require_once('Tests/Mocks/ReCaptcha.php');

use PHPUnit\Framework\TestCase;

final class DeckTest extends TestCase
{
    public $testDeckName = 'deck';
    public $testNewDeckName = 'newdeck';
    public $testWrongDeckName = 'wrongdeck';
    public $testDeckDescription = 'This is the deck description.';
    public $testDeckNewDescription = 'This is the new deck description.';
    public $testUsername = 'username';
    public $testPassword = 'password';
    public $testBadPassword = 'notpassword';

    public function DeleteUser() {
      DB::executeSql("DELETE FROM users WHERE username = '$this->testUsername'");
    }

    public function CreateUser() {
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

    public function setUp() {
        $this->DeleteUser();
    }

    public function tearDown() {
        $this->DeleteUser();
    }

    public function testInstantiateDeckWithNoParameters()
    {
        $this->DeleteUser();

        $deck = new Deck();
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testInstantiateDeckWithNoUser()
    {
        $this->DeleteUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Unable to load user, username not found.');
        $this->assertEquals($deck->referenceCode, 105);
    }

    public function testInstantiateDeckWithBadAuthentication()
    {
        $this->CreateUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed, no email on file.');
        $this->assertEquals($deck->referenceCode, 108);
    }

    public function testInstantiateDeckWithAuthentication()
    {
        $this->CreateUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testCreateDeckNotAuthenticated()
    {
        $this->CreateUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);

        $data['description'] = $this->testDeckDescription;

        $deck->Create($data);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed, no email on file.');
        $this->assertEquals($deck->referenceCode, 108);
    }

    public function testCreateDeck()
    {
        $this->CreateUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckDescription;
        $deck->Create($data);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testCreateDeckNoUser()
    {
        $this->CreateUser();

        $deck = new Deck(null, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckDescription;
        $deck->Create($data);
        $this->assertEquals($deck->user, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed.');
        $this->assertEquals($deck->referenceCode, 113);
    }

    public function testCreateDuplicateDeck()
    {
        $this->CreateUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckDescription;
        $deck->Create($data);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckDescription;
        $deck->Create($data);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Duplicate deck name found.');
        $this->assertEquals($deck->referenceCode, 202);
    }

    public function testReadDeck()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName);
        $deck->Read();
        $this->assertEquals($deck->user->authenticated, false);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testReadDeckAuthenticated()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $deck->Read();
        $this->assertEquals($deck->user->authenticated, true);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testReadDeckBadPassword()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);
        $deck->Read();
        $this->assertEquals($deck->user->authenticated, false);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed, no email on file.');
        $this->assertEquals($deck->referenceCode, 108);
    }

    public function testUpdateDeck()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckNewDescription;
        $deck->Update($data);
        $this->assertEquals($deck->user->authenticated, true);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckNewDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testUpdateNewDeck()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testNewDeckName, $this->testPassword);
        $data['description'] = $this->testDeckNewDescription;
        $deck->Update($data);
        $this->assertEquals($deck->user->authenticated, true);
        $this->assertEquals($deck->deckName, $this->testNewDeckName);
        $this->assertEquals($deck->description, $this->testDeckNewDescription);
        $this->assertEquals($deck->statusCode, 200);

        $deck = new Deck($this->testUsername, $this->testDeckName);
        $deck->Read();
        $this->assertEquals($deck->user->authenticated, false);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testUpdateDeckBadPassword()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);
        $data['description'] = $this->testDeckNewDescription;
        $deck->Update($data);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed, no email on file.');
        $this->assertEquals($deck->referenceCode, 108);
    }

    public function testUpdateDeckNoPassword()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName);
        $data['description'] = $this->testDeckNewDescription;
        $deck->Update($data);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed.');
        $this->assertEquals($deck->referenceCode, 113);
    }

    public function testUpdateDeckNoUser()
    {
        $this->CreateUser();

        $deck = new Deck(null, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckNewDescription;
        $deck->Update($data);
        $this->assertEquals($deck->user, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed.');
        $this->assertEquals($deck->referenceCode, 113);
    }

    public function testDeleteDeckBadPassword()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);
        $deck->Delete();
        $this->assertEquals($deck->user->authenticated, false);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed, no email on file.');
        $this->assertEquals($deck->referenceCode, 108);
    }

    public function testDeleteDeckNoPassword()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName);
        $deck->Delete();
        $this->assertEquals($deck->user->authenticated, false);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed.');
        $this->assertEquals($deck->referenceCode, 113);
    }

    public function testDeleteDeck()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $deck->Delete();
        $this->assertEquals($deck->user->authenticated, true);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testDeleteDeckNoUser()
    {
        $this->CreateDeck();

        $deck = new Deck(null, $this->testDeckName, $this->testPassword);
        $deck->Delete();
        $this->assertEquals($deck->user, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed.');
        $this->assertEquals($deck->referenceCode, 113);
    }
}


