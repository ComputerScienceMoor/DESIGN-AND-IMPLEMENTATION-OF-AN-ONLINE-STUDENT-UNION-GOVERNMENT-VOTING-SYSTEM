<?php
/**
 * inc/mail_config.php
 *
 * Keeps SMTP credentials OUT of your page code.
 * - Never commit this file to GitHub (add it to .gitignore).
 * - Block web access to the /inc folder (see the .htaccess note in the setup steps).
 */
return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,                       // 587 = STARTTLS, 465 = SMTPS
    'encryption' => 'tls',                     // 'tls' for 587, 'ssl' for 465
    'username'   => 'oluwajuwonloadedayo69@gmail.com',
    'password'   => 'chsuklbvjyukyylv',   // NEW Gmail App Password, no spaces
    'from_email' => 'your-gmail-address@gmail.com',
    'from_name'  => 'SUG Voting System Center',

    // Set to true ONLY while debugging: shows the real error on the login page.
    // Set back to false before going live.
    'debug'      => true,
];