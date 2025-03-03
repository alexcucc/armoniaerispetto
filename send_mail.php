<?php

    function sendMailOrFail(
        $to,
        $subject,
        $email_content,
        $headers,
        $sender) {

        if (!mail(
            $to,
            $subject,
            $email_content,
            $headers,
            "-f$sender")) {

            http_response_code(500);
            exit;
        }
    }

    if ($_SERVER["REQUEST_METHOD"] != "POST") {

        http_response_code(403);
        exit;
    }

    $name = trim(filter_input(INPUT_POST, "name", FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL));
    $message = trim(filter_input(INPUT_POST, "message", FILTER_SANITIZE_STRING));

    if (empty($name) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        http_response_code(400);
        exit;
    }

    $to = "alex.cucc@hotmail.it";
    $sender = "postmaster@armoniaerispetto.it";
    $subject = "Testing e-mail";

    $email_content = "Name: $name\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "Message:\n$message\n";

    $headers = "From: $sender";

    sendMailOrFail(
        $to,
        $subject,
        $email_content,
        $headers,
        $sender);
?>