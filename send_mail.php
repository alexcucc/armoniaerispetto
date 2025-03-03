<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim(filter_input(INPUT_POST, "name", FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL));
    $message = trim(filter_input(INPUT_POST, "message", FILTER_SANITIZE_STRING));

    if (empty($name) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        http_response_code(400);
        echo "Please fill in all fields and provide a valid email address.";
        exit;
    }

    $to = "alex.cucc@hotmail.it";
    
    $subject = "New contact from $name";

    $email_content = "Name: $name\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "Message:\n$message\n";

    $headers = "From: $name <$email>\r\n";
    $headers .= "Reply-To: $email\r\n";

    if (mail($to, $subject, $email_content, $headers)) {

        http_response_code(200);
    } else {

        http_response_code(500);
    }
} else {

    http_response_code(403);
}

?>