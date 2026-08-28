<?php
require_once __DIR__ . '/../config/Database.php';

class UserAdminModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.first_name, u.last_name, u.middle_name, u.gender,
                   u.birth_date, u.age, u.email, u.role, u.is_verified, u.created_at,
                   us.is_active, us.is_banned, us.banned_reason,
                   up.mobile_number, up.purok, up.street_address
            FROM users u
            JOIN user_status us ON us.user_id = u.id
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE us.is_deleted = 0
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT u.*, us.is_active, us.is_banned, us.is_deleted,
                   up.mobile_number, up.purok, up.street_address
            FROM users u
            JOIN user_status us ON us.user_id = u.id
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE u.id = ? AND us.is_deleted = 0
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare("
            SELECT u.id FROM users u
            JOIN user_status us ON us.user_id = u.id
            WHERE u.email = ? AND u.id != ? AND us.is_deleted = 0
        ");
        $stmt->execute([$email, $excludeId]);
        return (bool) $stmt->fetch();
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users
                (first_name, last_name, middle_name, gender, birth_date, age,
                 email, password, role, is_verified, verify_token, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())
        ");
        $stmt->execute([
            $d['first_name'], $d['last_name'], $d['middle_name'],
            $d['gender'],     $d['birth_date'], $d['age'],
            $d['email'],      $d['password'],   $d['role'],
            $d['verify_token'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $d): void
    {
        $this->db->prepare("
            UPDATE users
            SET first_name = ?, last_name = ?, middle_name = ?,
                email = ?, gender = ?, birth_date = ?, age = ?
            WHERE id = ?
        ")->execute([
            $d['first_name'], $d['last_name'], $d['middle_name'],
            $d['email'],      $d['gender'],    $d['birth_date'],
            $d['age'],        $id,
        ]);
    }

    public function updateRole(int $id, string $role): void
    {
        $this->db->prepare("UPDATE users SET role = ? WHERE id = ?")
                 ->execute([$role, $id]);
    }

    public function setActive(int $id, int $active): void
    {
        $this->db->prepare("UPDATE user_status SET is_active = ? WHERE user_id = ?")
                 ->execute([$active, $id]);
    }

    public function setBanned(int $id, int $banned, ?string $reason): void
    {
        $this->db->prepare("UPDATE user_status SET is_banned = ?, banned_reason = ? WHERE user_id = ?")
                 ->execute([$banned, $reason, $id]);
    }

    public function softDelete(int $id): void
    {
        $this->db->prepare("
            UPDATE user_status
            SET is_deleted = 1, is_active = 0, deleted_at = NOW()
            WHERE user_id = ?
        ")->execute([$id]);

        $this->db->prepare("
            UPDATE users
            SET email = CONCAT(email, '_deleted_', UNIX_TIMESTAMP()),
                otp_code = NULL, otp_expires = NULL
            WHERE id = ?
        ")->execute([$id]);
    }

    public function verifyByToken(string $token): bool
    {
        $stmt = $this->db->prepare("
            SELECT id FROM users
            WHERE verify_token = ? AND is_verified = 0
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        $this->db->prepare("
            UPDATE users
            SET is_verified = 1, verified_at = NOW(), verify_token = NULL
            WHERE id = ?
        ")->execute([$row['id']]);

        return true;
    }
}