<?php 
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $to = "alex.cucc@hotmail.it";
        $email = $_POST['email'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $message = $_POST['message'];
        $sender = "postmaster@armoniaerispetto.it";
        $returnPath = "-f$sender";
        $subjectOperator = "Richiesta informazioni";
        $subjectCustomer = "Grazie per la tua email, ti ricontatteremo a breve";
        $messageOperator = "Nome: $name \nE-mail: $email\nTelefono: $phone\nMessaggio: $message";
        $messageCustomer = "$name grazie per la tua email, ti ricontatteremo a breve";
        $headersOperator = "From: $email";
        $headersCustomer = "From: $to";

        sendMailOrThrowError(
            $to,
            $subjectOperator,
            $messageOperator,
            $headersOperator,
            $returnPath);
        sendMailOrThrowError(
            $email,
            $subjectCustomer,
            $messageCustomer,
            $headersCustomer,
            $returnPath);
    }

    function sendMailOrThrowError(
        $to,
        $subject,
        $message,
        $headers,
        $returnPath) {

            if (!mail(
                $to,
                $subject,
                $message,
                $headers,
                $returnPath)) {
                    error_log("Error while sending email to: " . $to);
                    http_response_code(500);
                }
    }
?>