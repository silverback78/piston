<?php
require_once('Response.php');
require_once('Interfaces/Pageable.php');
require_once('Services/DB.php');

class User extends Response implements Pageable {

    public $username;
    public $email;
    public $id;
    public $createdOn;
    public $password;
    public $recoveryCode;
    public $recoveryCodeTimestamp;
    
    public function Create($username, $password, $email) {
        $this->username = Utils::UrlSafe($username);
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->email = $email;
    
        $this->Save();
    }

    public function Authenticate($username, $password) {
        $username = Utils::UrlSafe($username);
        $email = null;

        DB::executeQuery('username',"SELECT id, password, email FROM users WHERE username = '$username'");
        if (count(DB::$results['username']) > 0) {
            $userPassword = DB::$results['username'][0]['password'];
            $userId = DB::$results['username'][0]['id'];
            $this->Load($userId);
            if (password_verify($password, $userPassword)) {
                return $this;
            }
            else {
                $this->email = Utils::ObfuscateEmail($this->email);
            }
        }

        if (!Utils::IsNullOrWhitespace($this->email)) {
            return $this->ResponseError(400, 107, "Authentication failed, email on file.");
        }
        else {
            return $this->ResponseError(400, 108, "Authentication failed, no email on file.");
        }

    }

    public static function IsNameAvailable($username) {
        $username = Utils::UrlSafe($username);
        if (strlen($username) < 7) return false;
        DB::executeQuery('username',"SELECT id FROM users WHERE username = '$username'");
        if (count(DB::$results['username']) > 0) {
            return false;
        }

        return true;
    }

    public static function Search($query) {
        $query = Utils::UrlSafe($query);

        DB::executeQuery('usernames',"SELECT username as text, username as value FROM users WHERE username LIKE '$query%' LIMIT 20");
        return DB::$results['usernames'];
    }

    public function ResetPassword() {
        if (Utils::IsNullOrWhitespace($this->username) && Utils::IsNullOrWhitespace($this->email)) {
            $this->HideSensitiveData();
            return;
        };

        try {
            $this->recoveryCode = rand ( 10000 , 99999 );
            $this->recoveryCodeTimestamp = date('Y-m-d H:i:s');
            $this->Update();
            $subject  = 'Password reset verification code';
            $message  = "A request has been made to reset the password for $this->username. Your temporary code is: $this->recoveryCode";
            mail($this->email, $subject, $message);
        }
        catch (Exception $e) {
            return false;
        }

        $this->HideSensitiveData();
        return $this;
    }

    public function UpdatePassword($code, $password) {
        $recoveryAge = time() - $this->recoveryCodeTimestamp;
        if (Utils::IsNullOrWhitespace($this->username)) {
            $this->HideSensitiveData();
            return;
        }

        if ($code != $this->recoveryCode) {
            $this->HideSensitiveData();
            $this->ResponseError(400, 109, "Recovery codes did not match.");
            return;
        }

        if ($recoveryAge > 3600) {
            $this->HideSensitiveData();
            $this->ResponseError(400, 110, "Recovery code expired.");
            return;
        }

        $this->recoveryCode = null;
        $this->recoveryCodeTimestamp = null;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->Update();

        $this->HideSensitiveData();
        return $this;
    }

    private function HideSensitiveData() {
        $this->id = null;
        $this->createdOn = null;
        $this->recoveryCode = null;
        $this->email = Utils::ObfuscateEmail($this->email);
        $this->password = null;
    }

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

    private function Valid($existing) {
        if (Utils::IsNullOrWhitespace($this->username)) {
            return $this->ResponseError(400, 100, 'Name is required.');
        }

        if (strlen($this->username) < 7 ) {
            return $this->ResponseError(400, 104, 'User username must be 7 characters or longer.');
        }

        if ($existing != true) {
            DB::executeQuery('username',"SELECT id FROM users WHERE username = '$this->username'");
            if (count(DB::$results['username']) > 0) {
                return $this->ResponseError(400, 101, "Duplicate username found: $this->username");
            }
        }

        return true;
    }

    public function Load($id) {
        DB::executeQuery('user', "SELECT id, username, password, email, created_on, recovery_code, recovery_code_timestamp FROM users WHERE id = $id");

        if (count(DB::$results['user']) <= 0) {
            return $this->ResponseError(400, 105, "Unable to load user, id not found: $id");
        }

        $this->SetValues(DB::$results['user'][0]);
        DB::closeConnection();
    }

    public function LoadByName($username) {
        $username = Utils::UrlSafe($username);
        DB::executeQuery('user', "SELECT id, username, password, email, created_on, recovery_code, recovery_code_timestamp FROM users WHERE username = '$username'");

        if (count(DB::$results['user']) > 0) {
            $this->SetValues(DB::$results['user'][0]);
        }
        DB::closeConnection();
    }

    public function SetValues($values) {
        $this->id = $values['id'];
        $this->username = $values['username'];
        $this->password = $values['password'];
        $this->email = $values['email'];
        $this->createdOn = $values['created_on'];
        $this->recoveryCode = $values['recovery_code'];
        $this->recoveryCodeTimestamp = strToTime($values['recovery_code_timestamp']);
    }

    public function Save() {
        if (!$this->Valid(false)) {
            return;
        }
    
        DB::executeSql("INSERT INTO users (username, password, email) VALUES ('$this->username', '$this->password', '$this->email')");

        $this->Load(DB::lastInsertId());
        DB::closeConnection();
    }

    public function Update() {
        if (!$this->Valid(true) || Utils::IsNullOrWhitespace($this->id)) {
            return;
        }
        $recoveryCode = $this->recoveryCode ? $this->recoveryCode : 'NULL';
        $recoveryCodeTimestamp = $this->recoveryCodeTimestamp ? "'$this->recoveryCodeTimestamp'" : 'NULL';
        DB::executeSql("UPDATE users SET username='$this->username', password='$this->password', email='$this->email', recovery_code=$recoveryCode, recovery_code_timestamp=$recoveryCodeTimestamp WHERE id=$this->id");
        DB::closeConnection();
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