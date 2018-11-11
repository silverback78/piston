<?php

class Utils {
    public static function IsNullOrWhitespace($str) {
        return empty(trim($str));
    }

    public static function GetPostVar($key) {
        return array_key_exists($key, $_POST) ? trim($_POST[$key]) : null;
    }

    public static function UrlSafe($key) {
        $key = preg_replace("/[^A-Za-z0-9 -]/", '', $key);
        $key = preg_replace('/\s+/', '-', $key);

        return $key;
    }

    public static function UrlToString($key) {
        $key = str_replace('-', ' ', $key);

        return $key;
    }

    public static function ObfuscateEmail($email) {
        if (Self::IsNullOrWhitespace($email)) return null;

        $emailParts = explode('@', $email);
        $name = implode(array_slice($emailParts, 0, count($emailParts) - 1), '@');
        $length  = strlen($name) - 3;
        if ($length > 0) {
            return substr($name, 0, 3) . str_repeat('*', $length) . '@' . end($emailParts);
        }
        else {
            return str_repeat('*', strlen($name)) . '@' . end($emailParts);
        }
    }
}

?>