<?php

require 'vendor/autoload.php';
require_once('Services/DB.php');
require_once('Services/Utils.php');
require_once('Services/ReCaptcha.php');
require_once('Models/Pager.php');
require_once('Models/User.php');
require_once('Models/Deck.php');
require_once('Models/Decks.php');
require_once('Models/Cards.php');

Flight::route('/', function(){
    Flight::render('index');
});

// Flight::route('GET /users/@index/@length/@orderBy/@direction', function($index, $length, $orderBy, $direction) {
//     $page = new Pager(new User(), $index, $length, $orderBy, $direction, null);
//     Flight::json($page);
// });

// Flight::route('GET /decks/@username/@index/@length/@orderBy/@direction', function($username, $index, $length, $orderBy, $direction) {
//     $userId = User::GetIdByName($username);
    
//     $filter = ['user_id' => $userId];
//     $page = new Pager(new Deck(), $index, $length, $orderBy, $direction, $filter);
//     Flight::json($page);
// });

// Flight::route('GET /decks/@index/@length/@orderBy/@direction', function($index, $length, $orderBy, $direction) {
//     $page = new Pager(new Deck(), $index, $length, $orderBy, $direction, null);
//     Flight::json($page);
// });

// Flight::route('GET /cards/@username/@deckName/@index/@length/@orderBy/@direction', function($username, $deckName, $index, $length, $orderBy, $direction) {
//     $deckId = Deck::GetIdByName($username, $deckName);

//     $filter = ['deck_id' => $deckId];
//     $page = new Pager(new Card(), $index, $length, $orderBy, $direction, $filter);
//     Flight::json($page);
// });

Flight::route('POST /user', function() {
    $data = Flight::request()->data->data;

    $user = new User();
    $user->Create($data);
    Flight::json($user);
});

Flight::route('GET /user/@username', function($username) {
    $user = new User($username);
    $user->Read();
    Flight::json($user);
});

Flight::route('GET /user/resetPassword/@username', function($username) {
    $user = new User($username);
    $user->ResetPassword();
    Flight::json($user);
});

Flight::route('GET /user/authenticate/@username/@password', function($username, $password) {
    $user = new User($username, $password);
    Flight::json($user);
});

Flight::route('GET /user/isNameAvailable/@username', function($username) {
    $user = new User($username);
    Flight::json($user->createdOn == null);
});

Flight::route('PUT /user', function() {
    $username = Flight::request()->data->username;
    $password = Flight::request()->data->password;
    $data = Flight::request()->data->data;

    $user = new User($username, $password);
    $user->Update($data);
    Flight::json($user);
});

Flight::route('DELETE /user/@username/@password', function($username, $password) {
    $user = new User($username, $password);
    $user->Delete();
    Flight::json($user);
});

Flight::route('PUT /deck', function() {
    $username = Flight::request()->data->username;
    $deckName = Flight::request()->data->deckName;
    $password = Flight::request()->data->password;
    $data = Flight::request()->data->data;

    $deck = new Deck($username, $deckName, $password);
    $deck->Update($data);
    Flight::json($deck);

    if (is_array($data) && array_key_exists('cards', $data) && is_array($data['cards'])) {
        $cards = new Cards($username, $deckName, $password);
        $cards->Update($data);
    }
});

Flight::route('GET /deck/isNameAvailable/@username/@deck', function($username, $deck) {
    $deck = new Deck($username, $deck);
    Flight::json($deck->createdOn == null);
});

Flight::route('GET /deck/@username/@deckName', function($username, $deckName) {
    $deck = new Deck($username, $deckName);
    $deck->Read();
    Flight::json($deck);
});

Flight::route('DELETE /deck/@username/@deckName/@password', function($username, $deckName, $password) {
    $deck = new Deck($username, $deckName, $password);
    $deck->Delete();
    Flight::json($deck);
});

Flight::route('GET /decks/@username', function($username) {
    $decks = new Decks($username);
    Flight::json($decks);
});

Flight::route('POST /cards', function() {
    $username = Flight::request()->data->username;
    $deckName = Flight::request()->data->deckName;
    $password = Flight::request()->data->password;
    $data = Flight::request()->data->data;

    $cards = new Cards($username, $deckName, $password);
    $cards->Create($data);
    Flight::json($cards);
});

Flight::route('GET /cards/@username/@deckName', function($username, $deckName) {
    $cards = new Cards($username, $deckName);
    $cards->Read();
    Flight::json($cards);
});

Flight::route('PUT /cards', function() {
    $username = Flight::request()->data->username;
    $deckName = Flight::request()->data->deckName;
    $password = Flight::request()->data->password;
    $data = Flight::request()->data->data;

    $cards = new Cards($username, $deckName, $password);
    $cards->Update($data);
    Flight::json($cards);
});

Flight::route('DELETE /cards/@username/@deckName/@password', function($username, $deckName, $password) {
    $cards = new Cards($username, $deckName, $password);
    $cards->Delete();
    Flight::json($cards);
});

Flight::start();
?>