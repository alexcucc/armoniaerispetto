<?php

function getCallForProposalDirectory(int $callId): string
{
    return 'private/documents/call_for_proposals/' . $callId;
}

function getCallForProposalWinnersDirectory(int $callId): string
{
    return getCallForProposalDirectory($callId) . '/winners';
}

function getCallForProposalWinnerDirectory(int $callId, int $winnerId): string
{
    return getCallForProposalWinnersDirectory($callId) . '/' . $winnerId;
}

function ensureDirectoryExists(string $directory): bool
{
    if (is_dir($directory)) {
        return true;
    }

    return mkdir($directory, 0755, true);
}

function deleteDirectoryRecursively(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $items = scandir($directory);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectoryRecursively($path);
            continue;
        }

        if (is_file($path)) {
            @unlink($path);
        }
    }

    @rmdir($directory);
}

function detectImageContentType(string $path): string
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return 'application/octet-stream';
    }

    $mimeType = finfo_file($finfo, $path) ?: 'application/octet-stream';
    finfo_close($finfo);

    return $mimeType;
}

function callForProposalWinnersTableExists(PDO $pdo): bool
{
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'call_for_proposal_winner'");
        $exists = $stmt !== false && $stmt->fetchColumn() !== false;
    } catch (Throwable $exception) {
        $exists = false;
    }

    return $exists;
}

function tableColumnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    static $cache = [];

    $cacheKey = $tableName . '.' . $columnName;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
        );
        $stmt->execute([
            ':table_name' => $tableName,
            ':column_name' => $columnName,
        ]);
        $cache[$cacheKey] = (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $exception) {
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}

function callForProposalWinnerPublicationStatusColumnExists(PDO $pdo): bool
{
    return tableColumnExists($pdo, 'call_for_proposal', 'winner_publication_status');
}
?>
