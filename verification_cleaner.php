<?php

include_once 'db/common-db.php';

try {
    $stmt = $pdo->prepare("DELETE user FROM user
                          INNER JOIN email_verification_token ON user.id = email_verification_token.user_id
                          WHERE email_verification_token.expires_at < NOW() 
                          AND email_verification_token.used_at IS NULL");
    $stmt->execute();
    echo "Users cleanup completed successfully\n";
} catch (PDOException $e) {
    error_log('User cleanup error: ' . $e->getMessage());
    echo "Error during cleanup\n";
}
?>