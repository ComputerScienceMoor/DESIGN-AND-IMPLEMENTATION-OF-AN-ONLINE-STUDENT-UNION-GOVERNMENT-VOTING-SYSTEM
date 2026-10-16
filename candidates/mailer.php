<?php
// candidates/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// ✅ FIXED: Using clean absolute path mappings so files load correctly from any subfolder context
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

/**
 * Sends login credentials to newly registered candidates.
 */
function sendCandidateCredentials($email, $name, $password) {
    // Passing true allows PHPMailer to throw readable exceptions on connection failure
    $mail = new PHPMailer(true);

    try {
        // --- SMTP Server Settings ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';             
        $mail->SMTPAuth   = true;
        
        // --- GMAIL CONFIGURATION ---
        $mail->Username   = 'oluwajuwonloadedayo69@gmail.com';       
        $mail->Password   = 'chsu klbv jyuk yylv'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ✅ FIXED: Added SSL options to prevent connection drops in local environments (XAMPP / Localhost)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // --- Recipients ---
        $mail->setFrom('oluwajuwonloadedayo69@gmail.com', 'FCAHPT Election Committee'); // Gmail requires the matching account address to verify sender authenticity
        $mail->addReplyTo('elections@fcahpt.edu.ng', 'FCAHPT Election Committee');
        $mail->addAddress($email, $name);

        // --- Content ---
        $mail->isHTML(true);
        $mail->Subject = 'Application Approved - Your Login Details';
        
        // The Body of the email
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
            <h2 style='color: #27ae60;'>Congratulations, $name!</h2>
            <p>Your application for the upcoming election has been reviewed and officially <strong>APPROVED</strong> by the screening board.</p>
            <p>You can now log in to your dashboard to manage your manifesto profile and review ballot configurations.</p>
            
            <div style='background: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 4px solid #27ae60;'>
                <p style='margin: 0 0 8px 0;'><strong>Login Email:</strong> $email</p>
                <p style='margin: 0;'><strong>Temporary Password:</strong> <span style='color: #e67e22; font-family: monospace; font-size: 18px; font-weight: bold;'>$password</span></p>
            </div>

            <p style='margin-top: 25px;'>
                <a href='http://localhost/FcahptVotes/candidates/candidate_login.php' 
                   style='background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>
                   Login to Candidate Dashboard
                </a>
            </p>
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            <p style='font-size: 11px; color: #888;'>Security Warning: Do not share these credentials with anyone. If you did not make this application request, please reach out directly to the IT administration help desk.</p>
        </div>";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log errors to local system records
        error_log("Mailer Error Exception Details: {$mail->ErrorInfo}");
        
        // ✅ CRITICAL: Rethrow exception upward so manage_candidates.php can capture and display the exact breakdown if it breaks again
        throw $e;
    }
}