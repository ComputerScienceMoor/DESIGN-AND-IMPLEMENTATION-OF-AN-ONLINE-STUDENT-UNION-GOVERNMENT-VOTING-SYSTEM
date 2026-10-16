<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

function sendVoteConfirmation($recipientEmail, $recipientName, $receiptId) {
    $mail = new PHPMailer(true);

    try {
        // --- SMTP Server Settings ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';               // Your SMTP provider
        $mail->SMTPAuth   = true;
        $mail->Username   = 'oluwajuwonloadedayo69@gmail.com';         // Your email
        $mail->Password   = 'chsu klbv jyuk yylv';            // Your 16-digit App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- Recipients ---
        $mail->setFrom('election@yourschool.edu', 'Election Commission');
        $mail->addAddress($recipientEmail, $recipientName);

        // --- Content ---
        $mail->isHTML(true);
        $mail->Subject = 'Vote Confirmation - Election 2026';
        
        $mail->Body = "
        <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px; border-top: 5px solid #27ae60;'>
            <h2 style='color: #27ae60;'>Ballot Successfully Cast</h2>
            <p>Hello <strong>$recipientName</strong>,</p>
            <p>This is a formal confirmation that your vote has been recorded in the system.</p>
            <div style='background: #f9f9f9; padding: 15px; border-radius: 5px; text-align: center;'>
                <span style='color: #7f8c8d; font-size: 12px; display: block;'>DIGITAL RECEIPT ID</span>
                <strong style='font-size: 1.4em; color: #2c3e50;'>$receiptId</strong>
            </div>
            <p style='font-size: 12px; color: #95a5a6; margin-top: 20px;'>
                This is an automated message. Please do not reply. If you did not authorize this, contact the IT department immediately.
            </p>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}