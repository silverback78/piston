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

Flight::start();
?>