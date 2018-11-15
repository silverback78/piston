<?php
require_once('Models/User.php');
require_once('Services/Utils.php');
require_once('Mocks/ReCaptcha.php');
require_once('Services/DB.php');

use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public $testUsername = 'username';
    public $testLongUsername = 'usernameusernameusernameusernameusernameusernameusernameusernameusernameusernameusernameusernameusername';
    public $testPassword = 'password';
    public $testBadPassword = 'notpassword';
    public $testNewPassword = 'newpassword';
    public $testEmail = 'email@email.com';
    public $testEmailObfuscated = 'ema**@email.com';
    public $testNewEmail = 'newemail@newemail.com';
    public $testNewEmailObfuscated = 'new*****@newemail.com';
    public $testRecoveryCode = '12345';
    public $testWrongRecoveryCode = '54321';

    public function DeleteUser() {
        DB::executeSql("DELETE FROM users WHERE username = $this->testUsername");
    }

    public function CreateUser() {
        $this->DeleteUser();
        ReCaptcha::$success = true;
        $user = new User();
        $data = [];
        $data['username'] = $this->testUsername;
        $data['password'] = $this->testPassword;
        $data['email'] = $this->testEmail;
        $user->Create($data);
    }

    public function CreateUserWithoutEmail() {
        $this->DeleteUser();
        ReCaptcha::$success = true;
        $user = new User();
        $data = [];
        $data['username'] = $this->testUsername;
        $data['password'] = $this->testPassword;
        $user->Create($data);
    }

    public function SetRecoveryCode() {
        $recoveryCodeTimestamp = date('Y-m-d H:i:s');
        DB::executeSql("UPDATE users SET recovery_code = $this->testRecoveryCode, recovery_code_timestamp = '$recoveryCodeTimestamp' WHERE username = $this->testUsername");
    }

    public function SetOldRecoveryCode() {
        $recoveryCodeTimestamp = date("Y-m-d H:i:s", strtotime( '-1 days' ) );
        
        DB::executeSql("UPDATE users SET recovery_code = $this->testRecoveryCode, recovery_code_timestamp = '$recoveryCodeTimestamp' WHERE username = $this->testUsername");
    }

    public function testInstantiateUserWithNoParameters()
    {
        $user = new User();
        $this->assertEquals($user->id, null);
        $this->assertEquals($user->createdOn, null);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 200);
    }

    public function testCreateUserValidationEmptyUser()
    {
        $this->DeleteUser();

        $user = new User();
        $data = [];
        $user->Create($data);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 100);
    }

    public function testCreateUserValidationUsernameOnly() {
        $this->DeleteUser();

        $user = new User();
        $data = [];
        $data['username'] = $this->testUsername;
        $user->Create($data);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 103);
    }

    public function testCreateUserValidationUsernameTooLong() {
        $this->DeleteUser();

        $user = new User();
        $data = [];
        $data['username'] = $this->testLongUsername;
        $data['password'] = $this->testPassword;
        $user->Create($data);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 104);
    }

    public function testCreateUserValidationFailedCaptcha() {
        $this->DeleteUser();

        ReCaptcha::$success = false;
        $user = new User();
        $data = [];
        $data['username'] = $this->testUsername;
        $data['password'] = $this->testPassword;
        $user->Create($data);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 102);
    }

    public function testCreateUserValidationDuplicateUserName() {
        $this->CreateUser();

        ReCaptcha::$success = true;
        $user = new User();
        $data = [];
        $data['username'] = $this->testUsername;
        $data['password'] = $this->testPassword;
        $user->Create($data);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 101);
    }

    public function testCreateUserValidationSuccess() {
        $this->DeleteUser();

        ReCaptcha::$success = true;
        $user = new User();
        $data = [];
        $data['username'] = $this->testUsername;
        $data['password'] = $this->testPassword;
        $user->Create($data);
        $this->assertEquals($user->statusCode, 200);
        $this->assertEquals($user->username, $this->testUsername);
        $this->assertEquals($user->obfuscatedEmail, null);
    }

    public function testCreateUserValidationSuccessWithEmail() {
        $this->DeleteUser();

        ReCaptcha::$success = true;
        $user = new User();
        $data = [];
        $data['username'] = $this->testUsername;
        $data['password'] = $this->testPassword;
        $data['email'] = $this->testEmail;
        $user->Create($data);
        $this->assertEquals($user->statusCode, 200);
        $this->assertEquals($user->username, $this->testUsername);
        $this->assertEquals($user->obfuscatedEmail, $this->testEmailObfuscated);
    }

    public function testReadUser() {
        $this->CreateUser();

        $user = new User($this->testUsername);
        $this->assertEquals($user->username, $this->testUsername);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, $this->testEmailObfuscated);
        $this->assertEquals($user->statusCode, 200);
    }

    public function testAuthenticate() {
        $this->CreateUser();

        $user = new User($this->testUsername, $this->testPassword);
        $this->assertEquals($user->username, $this->testUsername);
        $this->assertEquals($user->authenticated, true);
        $this->assertEquals($user->obfuscatedEmail, $this->testEmailObfuscated);
        $this->assertEquals($user->statusCode, 200);
    }

    public function testAuthenticationFailureEmailOnFile() {
        $this->CreateUser();

        $user = new User($this->testUsername, $this->testBadPassword);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 107);
    }

    public function testAuthenticationFailureNoEmailOnFile() {
        $this->CreateUserWithoutEmail();

        $user = new User($this->testUsername, $this->testBadPassword);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 108);
    }

    public function testUpdateUserEmailBadAuthentication() {
        $this->CreateUser();

        $data['email'] = $this->testEmail;
        $user = new User();
        $user->Update($data);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 113);

        $this->CreateUser();

        $data['email'] = $this->testEmail;
        $user = new User($this->testUsername, $this->testBadPassword);
        $user->Update($data);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 113);

        $this->CreateUser();

        $data['email'] = $this->testEmail;
        $user = new User($this->testUsername, $this->testBadPassword);
        $user->Update($data);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 113);

        $this->CreateUser();

        $data['email'] = $this->testEmail;
        $user = new User('', $this->testPassword);
        $user->Update($data);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 113);

        $this->CreateUser();

        $data['email'] = $this->testEmail;
        $user = new User($this->testUsername, '');
        $user->Update($data);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 113);
    }

    public function testUpdateUserEmail() {
        $this->CreateUser();

        $data['email'] = $this->testNewEmail;
        $user = new User($this->testUsername, $this->testPassword);
        $user->Update($data);
        $this->assertEquals($user->username, $this->testUsername);
        $this->assertEquals($user->authenticated, true);
        $this->assertEquals($user->obfuscatedEmail, $this->testNewEmailObfuscated);
        $this->assertEquals($user->statusCode, 200);
    }

    public function testUpdateUserPasswordWithoutRecoveryCode() {
        $this->CreateUser();

        $data['password'] = $this->testNewPassword;
        $user = new User($this->testUsername, $this->testPassword);
        $user->Update($data);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 109);
    }

    public function testUpdateUserPasswordWrongRecoveryCode() {
        $this->CreateUser();
        $this->SetRecoveryCode();

        $data['password'] = $this->testNewPassword;
        $user = new User($this->testUsername, $this->testPassword, $this->testWrongRecoveryCode);
        $user->Update($data);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 109);
    }

    public function testUpdateUserPasswordOldRecoveryCode() {
        $this->CreateUser();
        $this->SetOldRecoveryCode();

        $data['password'] = $this->testNewPassword;
        $user = new User($this->testUsername, $this->testPassword, $this->testRecoveryCode);
        $user->Update($data);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 110);
    }

    public function testUpdateUserPasswordSuccess() {
        $this->CreateUser();
        $this->SetRecoveryCode();

        $data['password'] = $this->testNewPassword;
        $user = new User($this->testUsername, $this->testPassword, $this->testRecoveryCode);
        $user->Update($data);
        $this->assertEquals($user->username, $this->testUsername);
        $this->assertEquals($user->authenticated, true);
        $this->assertEquals($user->obfuscatedEmail, $this->testEmailObfuscated);
        $this->assertEquals($user->statusCode, 200);

        $user = new User($this->testUsername, $this->testNewPassword);
        $this->assertEquals($user->username, $this->testUsername);
        $this->assertEquals($user->authenticated, true);
        $this->assertEquals($user->obfuscatedEmail, $this->testEmailObfuscated);
        $this->assertEquals($user->statusCode, 200);
    }

    public function testDeleteUser() {
        $this->CreateUser();
        $user = new User($this->testUsername, $this->testPassword);
        $user->Delete();
        $this->assertEquals($user->username, $this->testUsername);
        $this->assertEquals($user->authenticated, true);
        $this->assertEquals($user->obfuscatedEmail, $this->testEmailObfuscated);
        $this->assertEquals($user->statusCode, 200);

        $user = new User($this->testUsername, $this->testNewPassword);
        $this->assertEquals($user->username, null);
        $this->assertEquals($user->authenticated, false);
        $this->assertEquals($user->obfuscatedEmail, null);
        $this->assertEquals($user->statusCode, 400);
        $this->assertEquals($user->referenceCode, 105);
    }
}


