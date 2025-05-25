<?php
if (in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) {
    
    // Configure PHP to use MailHog
    ini_set('SMTP', 'localhost');
    ini_set('smtp_port', '1025');
    
    $url_prefix = 'http://localhost:3000';
} else {
    $url_prefix = 'https://armoniaerispetto.it';
}
?>