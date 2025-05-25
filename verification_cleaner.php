<?php

include_once 'db/common-db.php';

try {
    $stmt = $pdo->prepare("DELETE user FROM user
                          INNER JOIN email_verification_tokens ON user.id = email_verification_tokens.user_id
                          WHERE email_verification_tokens.expires_at < NOW() 
                          AND email_verification_tokens.used_at IS NULL");
    $stmt->execute();
    echo "Users cleanup completed successfully\n";
} catch (PDOException $e) {
    error_log('User cleanup error: ' . $e->getMessage());
    echo "Error during cleanup\n";
}
?>