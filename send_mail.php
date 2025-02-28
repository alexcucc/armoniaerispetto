<?php 
    if (isset($_POST['submit'])) {

        $to = "alex.cucc@hotmail.it";
        $from = $_POST['email'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $message = $_POST['message'];
        $subject = "Richiesta informazioni";
        $subject2 = "Copy of your form submission";
        $message = $name . " wrote the following:" . "\n\n" . $_POST['message'];
        $message2 = "Here is a copy of your message " . $name . "\n\n" . $_POST['message'];
        $headers = "From:" . $from;
        $headers2 = "From:" . $to;

        mail(
            $to,
            $subject,
            $message,
            $headers);
        mail(
            $from,
            $subject2,
            $message2,
            $headers2);
    }
?>