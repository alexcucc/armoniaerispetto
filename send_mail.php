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

    $name = trim(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $message = trim(filter_input(INPUT_POST, 'message', FILTER_UNSAFE_RAW));

    if (empty($name) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        http_response_code(400);
        exit;
    }

    $to = "info@armoniaerispetto.it";
    $sender = "postmaster@armoniaerispetto.it";
    $subjectOperator = "Richiesta informazioni";
    $subjectCustomer = "Richiesta informazioni";

    $contentOperator = "Nome: $name\n";
    $contentOperator .= "E-mail: $email\n";
    $contentOperator .= "Messaggio:\n$message\n";
    $contentCustomer = "$name grazie per l'e-mail, ti ricontatteremo a breve";

    $headers = "From: $sender";

    sendMailOrFail(
        $to,
        $subjectOperator,
        $contentOperator,
        $headers,
        $sender);
    sendMailOrFail(
        $email,
        $subjectCustomer,
        $contentCustomer,
        $headers,
        $sender);
?>