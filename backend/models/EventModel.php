<?php
require_once __DIR__ . '/../config/Database.php';

class EventModel {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->prepare(
            "SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) AS author_name
             FROM events e
             JOIN users u ON u.id = e.created_by
             ORDER BY e.event_date ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO events (title, event_date, event_time, event_time_end, location, description, created_by)
             VALUES (:title, :event_date, :event_time, :event_time_end, :location, :description, :created_by)"
        );
        $stmt->execute([
            ':title'          => $data['title'],
            ':event_date'     => $data['event_date'],
            ':event_time'     => $data['event_time'] ?: null,
            ':event_time_end' => $data['event_time_end'] ?: null,
            ':location'       => $data['location'] ?: null,
            ':description'    => $data['description'] ?: null,
            ':created_by'     => $data['created_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE events SET title = :title, event_date = :event_date,
             event_time = :event_time, event_time_end = :event_time_end,
             location = :location, description = :description
             WHERE id = :id"
        );
        return $stmt->execute([
            ':title'          => $data['title'],
            ':event_date'     => $data['event_date'],
            ':event_time'     => $data['event_time'] ?: null,
            ':event_time_end' => $data['event_time_end'] ?: null,
            ':location'       => $data['location'] ?: null,
            ':description'    => $data['description'] ?: null,
            ':id'             => $id,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM events WHERE id = ?");
        return $stmt->execute([$id]);
    }
}