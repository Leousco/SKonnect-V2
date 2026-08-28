<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $first_name;
    public $last_name;
    public $middle_name;
    public $gender;
    public $birth_date;
    public $age;
    public $email;
    public $password;
    public $role;
    public $is_verified;
    public $otp_code;
    public $otp_expires;

    public $is_active;
    public $is_banned;
    public $banned_reason;
    public $is_deleted;
    public $deleted_at;
    public $feed_ban_level;
    public $feed_ban_expires;

    public $login_attempts;
    public $lockout_until;
    public $lockout_level;

    public function __construct() {
        $database   = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    first_name    = :first_name,
                    last_name     = :last_name,
                    middle_name   = :middle_name,
                    gender        = :gender,
                    birth_date    = :birth_date,
                    age           = :age,
                    email         = :email,
                    password      = :password,
                    role          = 'resident',
                    otp_code      = :otp_code,
                    otp_expires   = :otp_expires,
                    is_verified   = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":first_name",  $this->first_name);
        $stmt->bindParam(":last_name",   $this->last_name);
        $stmt->bindParam(":middle_name", $this->middle_name);
        $stmt->bindParam(":gender",      $this->gender);
        $stmt->bindParam(":birth_date",  $this->birth_date);
        $stmt->bindParam(":age",         $this->age);
        $stmt->bindParam(":email",       $this->email);
        $stmt->bindParam(":password",    $this->password);
        $stmt->bindParam(":otp_code",    $this->otp_code);
        $stmt->bindParam(":otp_expires", $this->otp_expires);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function verifyUser() {
        $query = "SELECT otp_code, otp_expires FROM " . $this->table_name . "
                  WHERE email = :email AND is_verified = 0 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return false;
        if ($row['otp_code'] != $this->otp_code) return false;
        if (strtotime($row['otp_expires']) < time()) return false;

        $update = "UPDATE " . $this->table_name . "
                   SET is_verified = 1,
                       verified_at = NOW(),
                       otp_code    = NULL,
                       otp_expires = NULL
                   WHERE email = :email";
        $stmt2 = $this->conn->prepare($update);
        $stmt2->bindParam(":email", $this->email);
        $stmt2->execute();

        return $stmt2->rowCount() > 0;
    }

    public function getUserByEmail() {
        $query = "SELECT u.*, us.is_active, us.is_banned, us.banned_reason,
                         us.is_deleted, us.deleted_at, us.feed_ban_level, us.feed_ban_expires,
                         us.login_attempts, us.lockout_until, us.lockout_level
                  FROM " . $this->table_name . " u
                  JOIN user_status us ON us.user_id = u.id
                  WHERE u.email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->_hydrate($row);
            return true;
        }
        return false;
    }

    public function getUserById() {
        $query = "SELECT u.*, us.is_active, us.is_banned, us.banned_reason,
                         us.is_deleted, us.deleted_at, us.feed_ban_level, us.feed_ban_expires,
                         us.login_attempts, us.lockout_until, us.lockout_level
                  FROM " . $this->table_name . " u
                  JOIN user_status us ON us.user_id = u.id
                  WHERE u.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->_hydrate($row);
            return true;
        }
        return false;
    }

    public function emailExists() {
        $query = "SELECT u.id, u.is_verified
                  FROM " . $this->table_name . " u
                  JOIN user_status us ON us.user_id = u.id
                  WHERE u.email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['exists' => true, 'is_verified' => $row['is_verified']];
        }
        return ['exists' => false];
    }

    public function updateOTP() {
        $query = "UPDATE " . $this->table_name . "
                  SET otp_code    = :otp_code,
                      otp_expires = :otp_expires
                  WHERE email = :email AND is_verified = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":otp_code",    $this->otp_code);
        $stmt->bindParam(":otp_expires", $this->otp_expires);
        $stmt->bindParam(":email",       $this->email);
        return $stmt->execute();
    }

    public function updateRole() {
        $allowed = ['resident', 'moderator', 'sk_officer', 'admin'];
        if (!in_array($this->role, $allowed)) return false;

        $query = "UPDATE " . $this->table_name . " SET role = :role WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":id",   $this->id, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    public function deleteUnverified() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE email = :email AND is_verified = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        return $stmt->execute();
    }

    // Updates login_attempts, lockout_until, and lockout_level in user_status.
    public function updateLockoutState() {
        $query = "UPDATE user_status
                  SET login_attempts = :attempts,
                      lockout_until  = :until,
                      lockout_level  = :level
                  WHERE user_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":attempts", $this->login_attempts, PDO::PARAM_INT);
        $stmt->bindParam(":until",    $this->lockout_until);
        $stmt->bindParam(":level",    $this->lockout_level,  PDO::PARAM_INT);
        $stmt->bindParam(":id",       $this->id,             PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _hydrate(array $row): void {
        $this->id               = $row['id'];
        $this->first_name       = $row['first_name'];
        $this->last_name        = $row['last_name'];
        $this->middle_name      = $row['middle_name'];
        $this->gender           = $row['gender'];
        $this->birth_date       = $row['birth_date'];
        $this->age              = $row['age'];
        $this->email            = $row['email'];
        $this->password         = $row['password'];
        $this->role             = $row['role'];
        $this->is_verified      = $row['is_verified'];
        $this->otp_code         = $row['otp_code'];
        $this->otp_expires      = $row['otp_expires'];
        $this->is_active        = $row['is_active'];
        $this->is_banned        = $row['is_banned'];
        $this->banned_reason    = $row['banned_reason'];
        $this->is_deleted       = $row['is_deleted'];
        $this->deleted_at       = $row['deleted_at'];
        $this->feed_ban_level   = $row['feed_ban_level'];
        $this->feed_ban_expires = $row['feed_ban_expires'];
        $this->login_attempts   = (int) $row['login_attempts'];
        $this->lockout_until    = $row['lockout_until'];
        $this->lockout_level    = (int) $row['lockout_level'];
    }
}