<?php
 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
 
require 'vendor/autoload.php';
 
class Mail {
    public function transactionalEmail($customMessage){
        $mail = new PHPMailer(true);
        
        try {
            $smtp_host = 'mail.jxs2011.uta.cloud';
            $smtp_username = 'resicomm@jxs2011.uta.cloud';
            $smtp_password = 'Resicomm@123';

            $mail->SMTPDebug = 2;                                      
            $mail->isSMTP();                                           
            $mail->Host       = $smtp_host;                   
            $mail->SMTPAuth   = true;                            
            $mail->Username   = $smtp_username;                
            $mail->Password   = $smtp_password;                       
            // $mail->SMTPSecure = 'tls';                             
            $mail->SMTPSecure = 'ssl';                             
            $mail->Port       = 465; 
        
            $mail->setFrom($customMessage->from, 'Resicomm');          
            $mail->addAddress($customMessage->to);
            // $mail->addAddress('receiver2@gfg.com', 'Name');
            
            $mail->isHTML(true);                                 
            $mail->Subject = $customMessage->subject;
            $mail->Body    = $customMessage->message;
            $mail->AltBody = 'Body in plain text for non-HTML mail clients';
            $mail->send();
            echo "Mail has been sent successfully!";
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }


    }
}

 
?>