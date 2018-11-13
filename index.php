<?php

require 'vendor/autoload.php';
require_once('Services/DB.php');
require_once('Services/Utils.php');
require_once('Services/ReCaptcha.php');
require_once('Models/Pager.php');
require_once('Models/User.php');
require_once('Models/Deck.php');
require_once('Models/Card.php');

Flight::route('/', function(){
    Flight::render('index');
});

Flight::route('GET /users/@index/@length/@orderBy/@direction', function($index, $length, $orderBy, $direction) {
    $page = new Pager(new User(), $index, $length, $orderBy, $direction, null);
    Flight::json($page);
});

Flight::route('GET /decks/@username/@index/@length/@orderBy/@direction', function($username, $index, $length, $orderBy, $direction) {
    $userId = User::GetIdByName($username);
    
    $filter = ['user_id' => $userId];
    $page = new Pager(new Deck(), $index, $length, $orderBy, $direction, $filter);
    Flight::json($page);
});

Flight::route('GET /decks/@index/@length/@orderBy/@direction', function($index, $length, $orderBy, $direction) {
    $page = new Pager(new Deck(), $index, $length, $orderBy, $direction, null);
    Flight::json($page);
});

Flight::route('GET /cards/@username/@deckName/@index/@length/@orderBy/@direction', function($username, $deckName, $index, $length, $orderBy, $direction) {
    $deckId = DECK::GetIdByName($username, $deckName);

    $filter = ['deck_id' => $deckId];
    $page = new Pager(new Card(), $index, $length, $orderBy, $direction, $filter);
    Flight::json($page);
});

Flight::route('GET /user/isNameAvailable/@name', function($name) {
    $isNameAvailable = User::IsNameAvailable($name);
    Flight::json($isNameAvailable);
});

Flight::route('GET /user/getIdByName/@name', function($name) {
    $userId = User::GetIdByName($name);
    Flight::json($userId);
});

Flight::route('GET /search/users/', function() {
    $var['name'] = '';
    Flight::json($var);
});

Flight::route('GET /search/users/@query', function($query) {
    $names = User::Search($query);
    Flight::json($names);
});

Flight::route('GET /deck/getIdByName/@username/@deckName', function($username, $deckName) {
    $deckId = Deck::GetIdByName($username, $deckName);
    Flight::json($deckId);
});

Flight::route('POST /user/authenticate', function() {
    $username  = Utils::GetPostVar('username');
    $password = Utils::GetPostVar('password');

    $user = new User();
    $user->Authenticate($username, $password);
    Flight::json($user);
});

Flight::route('POST /user', function() {
    $username = Utils::GetPostVar('username');
    $password = Utils::GetPostVar('password');
    $email = Utils::GetPostVar('email');
    $recaptchaResponse = Utils::GetPostVar('reCaptchaResponse');

    $result = ReCaptcha::Validate($recaptchaResponse);

    $user = new User();
    if ($result->success == true) {
        $user->Create($username, $password, $email);
    }
    else {
        $user->ResponseError(400, 102, 'Captcha failed. ' . json_encode($result));
    }

    Flight::json($user);
});

Flight::route('POST /decks', function() {
    $username = Utils::GetPostVar('username');
    $password = Utils::GetPostVar('password');
    $deckName = Utils::GetPostVar('deckName');
    $description = Utils::GetPostVar('description');
    $cardData = Utils::GetPostVar('cards');

    $deck = new Deck();
    $deck->Create($username, $password, $deckName, $description, $cardData);

    Flight::json($deck);
});

Flight::route('POST /deck/isNameAvailable', function() {
    $username = Utils::GetPostVar('username');
    $name = Utils::GetPostVar('name');

    $isNameAvailable = Deck::IsNameAvailable($username, $name);
    Flight::json($isNameAvailable);
});

Flight::route('POST /resetPassword', function() {
    $username = Utils::GetPostVar('username');

    $user = new User();
    $user->LoadByName($username);
    $user->ResetPassword();

    Flight::json($user);
});

Flight::route('POST /updatePassword', function() {
    $username = Utils::GetPostVar('username');
    $password = Utils::GetPostVar('password');
    $code = Utils::GetPostVar('code');

    $user = new User();
    $user->LoadByName($username);
    $user->UpdatePassword($code, $password);

    Flight::json($user);
});

Flight::start();
?>