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
    $authenticated = User::Authenticate($username, $password);
    Flight::json($authenticated);
});

Flight::route('POST /user', function() {
    $username = Utils::GetPostVar('username');
    $password = Utils::GetPostVar('password');
    $passwordHint = Utils::GetPostVar('passwordHint');
    $recaptchaResponse = Utils::GetPostVar('reCaptchaResponse');

    $result = ReCaptcha::Validate($recaptchaResponse);

    $user = new User();
    if ($result->success == true) {
        $user->Create($username, $password, $passwordHint);
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

Flight::route('POST /card', function() {
    $deckId = Utils::GetPostVar('deckId');
    $password = Utils::GetPostVar('password');
    $term = Utils::GetPostVar('term');
    $definition = Utils::GetPostVar('definition');

    $card = new Card();
    $card->Create($password, $deckId, $term, $definition);

    Flight::json($card);
});

Flight::route('POST /log', function() {
    $log = Utils::GetPostVar('contents');
    $log = str_replace('%c', '', $log);
    $ip = $_SERVER['REMOTE_ADDR'];
    file_put_contents("logs/{$ip}.log", $log . "\n", FILE_APPEND | LOCK_EX);
});

Flight::start();
?>