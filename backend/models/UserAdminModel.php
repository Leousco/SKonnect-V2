<?php
require_once __DIR__ . '/../config/database.php';

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
                   up.mobile_number, up.purok, up.street_address,
                   up.civil_status, up.nationality, up.religion,
                   up.educational_attainment, up.school_institution, up.course_strand,
                   up.employment_status, up.is_registered_voter
            FROM users u
            JOIN user_status us ON us.user_id = u.id
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE us.is_deleted = FALSE
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['is_verified'] = $this->toBoolean($row['is_verified']);
            $row['is_active']   = $this->toBoolean($row['is_active']);
            $row['is_banned']   = $this->toBoolean($row['is_banned']);
            $row['is_registered_voter'] = $this->toBoolean($row['is_registered_voter']);
        }
        unset($row);
        return $rows;
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT u.*, us.is_active, us.is_banned, us.is_deleted,
                   up.mobile_number, up.purok, up.street_address,
                   up.civil_status, up.nationality, up.religion,
                   up.educational_attainment, up.school_institution, up.course_strand,
                   up.employment_status, up.is_registered_voter
            FROM users u
            JOIN user_status us ON us.user_id = u.id
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE u.id = ? AND us.is_deleted = FALSE
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['is_verified'] = $this->toBoolean($row['is_verified']);
            $row['is_active']   = $this->toBoolean($row['is_active']);
            $row['is_banned']   = $this->toBoolean($row['is_banned']);
            $row['is_deleted']  = $this->toBoolean($row['is_deleted']);
            $row['is_registered_voter'] = $this->toBoolean($row['is_registered_voter']);
        }
        return $row;
    }

    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare("
            SELECT u.id FROM users u
            JOIN user_status us ON us.user_id = u.id
            WHERE u.email = ? AND u.id != ? AND us.is_deleted = FALSE
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
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE, ?, NOW())
        ");
        $stmt->execute([
            $d['first_name'], $d['last_name'], $d['middle_name'],
            $d['gender'],     $d['birth_date'], $d['age'],
            $d['email'],      $d['password'],   $d['role'],
            $d['verify_token'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $d, array $profile): void
    {
        $this->db->beginTransaction();
        try {
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

            $this->db->prepare("
                INSERT INTO user_profiles (
                    user_id, mobile_number, purok, street_address, civil_status,
                    nationality, religion, educational_attainment, school_institution,
                    course_strand, employment_status, is_registered_voter
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT (user_id) DO UPDATE SET
                    mobile_number = EXCLUDED.mobile_number,
                    purok = EXCLUDED.purok,
                    street_address = EXCLUDED.street_address,
                    civil_status = EXCLUDED.civil_status,
                    nationality = EXCLUDED.nationality,
                    religion = EXCLUDED.religion,
                    educational_attainment = EXCLUDED.educational_attainment,
                    school_institution = EXCLUDED.school_institution,
                    course_strand = EXCLUDED.course_strand,
                    employment_status = EXCLUDED.employment_status,
                    is_registered_voter = EXCLUDED.is_registered_voter,
                    updated_at = NOW()
            ")->execute([
                $id,
                $profile['mobile_number'],
                $profile['purok'],
                $profile['street_address'],
                $profile['civil_status'],
                $profile['nationality'],
                $profile['religion'],
                $profile['educational_attainment'],
                $profile['school_institution'],
                $profile['course_strand'],
                $profile['employment_status'],
                $profile['is_registered_voter'] ? 1 : 0,
            ]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
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
            SET is_deleted = TRUE, is_active = FALSE, deleted_at = NOW()
            WHERE user_id = ?
        ")->execute([$id]);

        $this->db->prepare("
            UPDATE users
            SET email = CONCAT(email, '_deleted_', EXTRACT(EPOCH FROM NOW())::bigint),
                otp_code = NULL, otp_expires = NULL
            WHERE id = ?
        ")->execute([$id]);
    }

    public function verifyByToken(string $token): bool
    {
        $stmt = $this->db->prepare("
            SELECT id FROM users
            WHERE verify_token = ? AND is_verified = FALSE
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        $this->db->prepare("
            UPDATE users
            SET is_verified = TRUE, verified_at = NOW(), verify_token = NULL
            WHERE id = ?
        ")->execute([$row['id']]);

        return true;
    }

    private function toBoolean(mixed $value): bool
    {
        return $value === true || in_array(strtolower((string) $value), ['1', 't', 'true', 'yes', 'on'], true);
    }
}