<?php 
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $to = "alex.cucc@hotmail.it";
        $from = $_POST['email'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $message = $_POST['message'];
        $subjectOperator = "Richiesta informazioni";
        $subjectCustomer = "Grazie per la tua email, ti ricontatteremo a breve";
        $messageOperator = "Nome: ". $name . "\nE-mail: " . $from . "\nTelefono: " . $phone . "\nMessaggio: " . $message;
        $messageCustomer = $name . " grazie per la tua email, ti ricontatteremo a breve";
        $headersOperator = "From:" . $from;
        $headersCustomer = "From:" . $to;

        mail(
            $to,
            $subjectOperator,
            $messageOperator,
            $headersOperator);
        mail(
            $from,
            $subjectCustomer,
            $messageCustomer,
            $headersCustomer);
    }
?>