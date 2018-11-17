<?php
require_once('Response.php');
require_once('Interfaces/Pageable.php');
require_once('Services/DB.php');

class User extends Response implements Pageable {

    public $id;
    public $createdOn;
    public $username;
    public $authenticated = false;
    public $obfuscatedEmail;
    private $suppliedPassword;
    private $password;
    private $email;
    private $recoveryCode;
    private $recoveryCodeTimestamp;
    private $recaptchaResponse;
    private $valid = false;
    private $updatingPassword = false;

    public static function GetIdByName($username) {
        $username = Utils::UrlSafe($username);
        DB::executeQuery('username',"SELECT id FROM users WHERE username = '$username'");
        if (count(DB::$results['username']) > 0) {
            return intval(DB::$results['username'][0]['id']);
        }
        else {
            return -1;
        }
    }

    function __construct($username = null, $suppliedPassword = null) {
        if (!Utils::IsNullOrWhitespace($username)) {
            $this->username = Utils::UrlSafe($username);
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
        if ($data === null) return;

        $this->username = array_key_exists('username', $data) ? Utils::UrlSafe($data['username']) : null;
        $this->password = array_key_exists('password', $data) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
        $this->email = array_key_exists('email', $data) ? $data['email'] : null;
        $this->recaptchaResponse = array_key_exists('recaptchaResponse', $data) ? $data['recaptchaResponse'] : null;

        $this->Validate('create');
        if (!$this->valid) return;

        DB::executeSql("INSERT INTO users (username, password, email) VALUES ('$this->username', '$this->password', '$this->email')");
        $id = DB::lastInsertId();
        DB::executeQuery('user', "SELECT username FROM users WHERE id = $id");
        $this->username = DB::$results['user'][0]['username'];
        $this->Load();
    }

    public function Read() {
        if ($this->exception) return;
        return $this;
    }

    public function Update($data) {
        if ($this->exception) return;

        DB::executeQuery('user', "SELECT id FROM users WHERE username = '$this->username'");
        if (count(DB::$results['user']) <= 0) {
            return $this->Create($data);
        }

        if (!$this->authenticated) {
            return $this->AuthenticationFailed();
        }

        if (array_key_exists('password', $data) && !Utils::IsNullOrWhitespace($data['password'])){
            $this->updatingPassword = true;
            $this->password = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $this->email = array_key_exists('email', $data) ? $data['email'] : $this->email;
        $this->obfuscatedEmail = Utils::ObfuscateEmail($this->email);

        $this->Validate('update');
        if (!$this->valid) return;

        DB::executeSql("UPDATE users SET password = '$this->password', email = '$this->email', recovery_code = null, recovery_code_timestamp = null WHERE id = $this->id");
    }

    public function Delete() {
        if ($this->exception) return;
        if (!$this->authenticated) {
            return $this->AuthenticationFailed();
        }
        DB::executeSql("DELETE FROM users WHERE id = $this->id");
    }

    public function ResetPassword() {
        if (Utils::IsNullOrWhitespace($this->email)) {
            return;
        };

        try {
            $this->recoveryCode = rand ( 10000 , 99999 );
            $this->recoveryCodeTimestamp = date('Y-m-d H:i:s');
            $this->Store();
            $subject  = 'StudyWidgets password reset';
            $message  = "A request has been made to reset the password for $this->username. Your temporary code is: $this->recoveryCode";
            $from = 'no-reply@studywidgets.com';
            $headers = "From:$from";
            mail($this->email, $subject, $message, $headers);
        }
        catch (Exception $e) {
            return $this->ResponseError(400, 105, "Error sending e-mail.");
        }
    }

    private function Load() {
        if (Utils::IsNullOrWhitespace($this->username)) return;

        DB::executeQuery('user', "SELECT id, username, password, email, created_on, recovery_code, recovery_code_timestamp FROM users WHERE username = '$this->username'");
        if (count(DB::$results['user']) <= 0) {
            return $this->ResponseError(400, 105, "Unable to load user, username not found.");
        }

        $this->SetValues(DB::$results['user'][0]);

        $this->Authenticate();
    }

    public function Store() {
        $this->Validate('update');
        if (!$this->valid) return;

        $recoveryCode = $this->recoveryCode ? $this->recoveryCode : 'NULL';
        $recoveryCodeTimestamp = $this->recoveryCodeTimestamp ? "'$this->recoveryCodeTimestamp'" : 'NULL';
        DB::executeSql("UPDATE users SET username='$this->username', password='$this->password', email='$this->email', recovery_code=$recoveryCode, recovery_code_timestamp=$recoveryCodeTimestamp WHERE id=$this->id");
    }

    private function Authenticate() {
        if (Utils::IsNullOrWhitespace($this->suppliedPassword)) return;

        if (password_verify($this->suppliedPassword, $this->password)) {
            $this->authenticated = true;
            return;
        }

        if ($this->recoveryCode != null && $this->recoveryCodeTimestamp != null) {
            $recoveryAge = time() - $this->recoveryCodeTimestamp;
            if ($recoveryAge > 3600) {
                return $this->ResponseError(400, 110, "Recovery code expired.");
            }

            if ($this->suppliedPassword == $this->recoveryCode) {
                $this->authenticated = true;
                return;
            }
        }

        return $this->AuthenticationFailed();
    }

    private function AuthenticationFailed() {
        if ($this->exception) return;

        if (!Utils::IsNullOrWhitespace($this->email)) {
            return $this->ResponseError(400, 107, "Authentication failed, email on file.");
        }
        else {
            return $this->ResponseError(400, 108, "Authentication failed, no email on file.");
        }
    }

    private function Validate($operation) {
        if (Utils::IsNullOrWhitespace($this->username)) {
            return $this->ResponseError(400, 100, 'Username is a required field.');
        }

        if (Utils::IsNullOrWhitespace($this->password)) {
            return $this->ResponseError(400, 103, 'Password is a required field.');
        }

        if (strlen($this->username) > 64) {
            return $this->ResponseError(400, 104, 'Username must be 64 characters or less.');
        }

        if ($operation == 'create') {
            DB::executeQuery('username',"SELECT id FROM users WHERE username = '$this->username'");
            if (count(DB::$results['username']) > 0) {
                return $this->ResponseError(400, 101, "Duplicate username found.");
            }

            $validateRecaptcha = ReCaptcha::Validate($this->recaptchaResponse);
            if (Config::$recaptchaEnabled && $validateRecaptcha->success != true) {
                return $this->ResponseError(400, 102, 'Captcha failed.');
            }
        }

        $this->valid = true;
    }

    public function ResetValues() {
        $this->id = null;
        $this->username = null;
        $this->password = null;
        $this->email = null;
        $this->obfuscatedEmail = null;
        $this->createdOn = null;
        $this->recoveryCode = null;
        $this->recoveryCodeTimestamp = null;
        $this->authenticated = false;
        $this->valid = false;
        $this->username = null;
        $this->suppliedPassword = null;
    }

    private function SetValues($values) {
        $this->id = $values['id'];
        $this->username = $values['username'];
        $this->password = $values['password'];
        $this->email = $values['email'];
        $this->createdOn = $values['created_on'];
        $this->recoveryCode = $values['recovery_code'];
        $this->recoveryCodeTimestamp = strToTime($values['recovery_code_timestamp']);
        $this->obfuscatedEmail = Utils::ObfuscateEmail($this->email);
    }

    public function PagerReturnColumns() {
        return ['id', 'created_on', 'username'];
    }

    public function PagerDefaultDirection() {
        return 'ASC';
    }

    public function PagerDefaultOrderBy() {
        return 'id';
    }

    public function PagerTableName() {
        return 'users';
    }

    public function PagerFilterColumn() {
        return null;
    }

    public function PagerJoinTable() {
        return null;
    }

    public function PagerJoinColumns(){
        return null;
    }
}

?>