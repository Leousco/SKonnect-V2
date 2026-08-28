<?php
require_once __DIR__ . '/../config/Database.php';

class UserProfileModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getProfile(int $userId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id, u.first_name, u.last_name, u.middle_name, u.gender,
                u.birth_date, u.age, u.email, u.role, u.created_at,
                up.mobile_number, up.purok, up.street_address, up.civil_status,
                up.nationality, up.religion, up.educational_attainment,
                up.school_institution, up.course_strand, up.employment_status,
                up.is_registered_voter, up.avatar_path
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE u.id = :uid
            LIMIT 1
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isProfileComplete(int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT mobile_number, purok FROM user_profiles WHERE user_id = :uid LIMIT 1
        ");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        return !empty(trim((string)$row['mobile_number'])) && !empty(trim((string)$row['purok']));
    }

    public function getActivitySummary(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'approved')         AS approved,
                SUM(status = 'pending')          AS pending,
                SUM(status = 'rejected')         AS rejected,
                SUM(status = 'cancelled')        AS cancelled,
                SUM(status = 'action_required')  AS action_required
            FROM service_applications
            WHERE resident_id = :uid
        ");
        $stmt->execute([':uid' => $userId]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt2 = $this->db->prepare("
            SELECT COUNT(*) AS thread_count FROM threads WHERE author_id = :uid AND is_removed = 0
        ");
        $stmt2->execute([':uid' => $userId]);
        $threads = $stmt2->fetch(PDO::FETCH_ASSOC);

        return [
            'total'           => (int)($counts['total']           ?? 0),
            'approved'        => (int)($counts['approved']        ?? 0),
            'pending'         => (int)($counts['pending']         ?? 0),
            'rejected'        => (int)($counts['rejected']        ?? 0),
            'cancelled'       => (int)($counts['cancelled']       ?? 0),
            'action_required' => (int)($counts['action_required'] ?? 0),
            'threads'         => (int)($threads['thread_count']   ?? 0),
        ];
    }

    public function getRecentRequests(int $userId, int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT
                sa.id, sa.status, sa.submitted_at,
                sv.name AS service_name
            FROM service_applications sa
            INNER JOIN services sv ON sv.id = sa.service_id
            WHERE sa.resident_id = :uid
            ORDER BY sa.submitted_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserThreads(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                t.id, t.category, t.subject, t.status, t.created_at,
                (SELECT COUNT(*) FROM thread_comments tc WHERE tc.thread_id = t.id AND tc.is_removed = 0) AS comment_count,
                (SELECT COUNT(*) FROM thread_supports ts WHERE ts.thread_id = t.id) AS support_count
            FROM threads t
            WHERE t.author_id = :uid AND t.is_removed = 0
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function savePersonal(int $userId, array $d): void
    {
        $age = (int)(new DateTime())->diff(new DateTime($d['birth_date']))->y;

        $this->db->prepare("
            UPDATE users
            SET first_name = :fn, last_name = :ln, middle_name = :mn,
                gender = :gender, birth_date = :dob, age = :age
            WHERE id = :uid
        ")->execute([
            ':fn'     => trim($d['first_name']),
            ':ln'     => trim($d['last_name']),
            ':mn'     => isset($d['middle_name']) ? trim($d['middle_name']) : null,
            ':gender' => $d['gender'],
            ':dob'    => $d['birth_date'],
            ':age'    => $age,
            ':uid'    => $userId,
        ]);

        $this->db->prepare("
            INSERT INTO user_profiles (user_id, civil_status, nationality, religion)
            VALUES (:uid, :civil, :nat, :rel)
            ON DUPLICATE KEY UPDATE
                civil_status = VALUES(civil_status),
                nationality  = VALUES(nationality),
                religion     = VALUES(religion)
        ")->execute([
            ':uid'   => $userId,
            ':civil' => $d['civil_status'] ?? null,
            ':nat'   => $d['nationality']  ?? null,
            ':rel'   => $d['religion']     ?? null,
        ]);
    }

    public function saveContact(int $userId, array $d): void
    {
        $this->db->prepare("
            UPDATE users SET email = :email WHERE id = :uid
        ")->execute([':email' => strtolower(trim($d['email'])), ':uid' => $userId]);

        $this->db->prepare("
            INSERT INTO user_profiles (user_id, mobile_number, purok, street_address)
            VALUES (:uid, :mob, :purok, :street)
            ON DUPLICATE KEY UPDATE
                mobile_number  = VALUES(mobile_number),
                purok          = VALUES(purok),
                street_address = VALUES(street_address)
        ")->execute([
            ':uid'    => $userId,
            ':mob'    => $d['mobile_number'],
            ':purok'  => trim($d['purok']),
            ':street' => isset($d['street_address']) ? trim($d['street_address']) : null,
        ]);
    }

    public function saveMembership(int $userId, array $d): void
    {
        $this->db->prepare("
            INSERT INTO user_profiles
                (user_id, educational_attainment, school_institution,
                 course_strand, employment_status, is_registered_voter)
            VALUES (:uid, :edu, :school, :course, :emp, :voter)
            ON DUPLICATE KEY UPDATE
                educational_attainment = VALUES(educational_attainment),
                school_institution     = VALUES(school_institution),
                course_strand          = VALUES(course_strand),
                employment_status      = VALUES(employment_status),
                is_registered_voter    = VALUES(is_registered_voter)
        ")->execute([
            ':uid'    => $userId,
            ':edu'    => $d['educational_attainment'] ?? null,
            ':school' => isset($d['school_institution']) ? trim($d['school_institution']) : null,
            ':course' => isset($d['course_strand'])      ? trim($d['course_strand'])      : null,
            ':emp'    => $d['employment_status'] ?? null,
            ':voter'  => isset($d['is_registered_voter']) ? (int)$d['is_registered_voter'] : 0,
        ]);
    }

    public function completeSetup(int $userId, array $d): void
    {
        $this->db->prepare("
            INSERT INTO user_profiles
                (user_id, mobile_number, purok, street_address, nationality, religion)
            VALUES (:uid, :mob, :purok, :street, :nat, :rel)
            ON DUPLICATE KEY UPDATE
                mobile_number  = VALUES(mobile_number),
                purok          = VALUES(purok),
                street_address = VALUES(street_address),
                nationality    = VALUES(nationality),
                religion       = VALUES(religion)
        ")->execute([
            ':uid'    => $userId,
            ':mob'    => $d['mobile_number'],
            ':purok'  => trim($d['purok']),
            ':street' => isset($d['street_address']) ? trim($d['street_address']) : null,
            ':nat'    => isset($d['nationality'])    ? trim($d['nationality'])    : null,
            ':rel'    => isset($d['religion'])       ? trim($d['religion'])       : null,
        ]);
    }

    public function saveAvatar(int $userId, string $path): void
    {
        $this->db->prepare("
            INSERT INTO user_profiles (user_id, avatar_path)
            VALUES (:uid, :path)
            ON DUPLICATE KEY UPDATE avatar_path = VALUES(avatar_path)
        ")->execute([':uid' => $userId, ':path' => $path]);
    }
}