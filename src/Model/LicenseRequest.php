<?php
namespace App\Model;

use App\Service\Database;

class LicenseRequest
{
    public static function create(string $email, string $domain, string $type, ?string $period, ?string $payProofPath = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO license_requests (email, domain, request_type, period, status, pay_proof_path, created_at) VALUES (:email, :domain, :type, :period, :status, :pay_proof_path, NOW())');
        $status = 'pending';
        $stmt->execute([
            ':email' => $email,
            ':domain' => $domain,
            ':type' => $type,
            ':period' => $period,
            ':status' => $status,
            ':pay_proof_path' => $payProofPath,
        ]);
    }

    public static function listLatest(int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $limit = max(1, min($limit, 200));
        $stmt = $pdo->query('SELECT * FROM license_requests ORDER BY id DESC LIMIT ' . $limit);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function listAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM license_requests ORDER BY id DESC');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function updateNote(int $id, string $note): void
    {
        if ($id <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE license_requests SET note = :note WHERE id = :id');
        $stmt->execute([
            ':note' => $note,
            ':id' => $id,
        ]);
    }

    public static function deleteById(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM license_requests WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
