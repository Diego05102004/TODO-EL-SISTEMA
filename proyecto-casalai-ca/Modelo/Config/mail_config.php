<?php

// Configuración para PHPMailer usando Gmail SMTP
// IMPORTANTE: Rellena estos campos con tus propios datos.

return [
    'host' => 'smtp.gmail.com',
    'smtp_auth' => true,
    'username' => 'darckortgame@gmail.com', // Tu dirección de correo de Gmail
    'password' => 'lrmg reiz vxlu rzza', // Contraseña de aplicación de Google
    'smtp_secure' => 'ssl', // Se cambia a SSL
    'port' => 587, // Se cambia al puerto para SSL
    'from_email' => 'darckortgame@gmail.com', // El correo que aparecerá como remitente
    'from_name' => 'Soporte CasaLai' // El nombre que aparecerá como remitente
];
