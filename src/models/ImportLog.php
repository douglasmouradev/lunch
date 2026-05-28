<?php
declare(strict_types=1);

class ImportLog
{
    public static function add(?int $adminId, string $sourceType, string $summary, array $details = []): void
    {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare(
                'INSERT INTO import_logs (admin_id, source_type, summary, details_json) VALUES (?, ?, ?, ?)'
            );
            $json = $details !== [] ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
            $stmt->execute([$adminId, $sourceType, $summary, $json]);
        } catch (Throwable $e) {
            Logger::warning('import_logs indisponível', ['message' => $e->getMessage()]);
        }
    }

    public static function recent(int $limit = 30): array
    {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare(
                'SELECT il.id, il.source_type, il.summary, il.created_at, au.username AS admin_username
                 FROM import_logs il
                 LEFT JOIN admin_users au ON au.id = il.admin_id
                 ORDER BY il.created_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }
}
