<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Config/RecaptchaConfig.php';

$siteKey = \Usuario\ProyectoCasalaiCa\Config\RecaptchaConfig::getSiteKey();
if ($siteKey === '') {
    errorResponse('reCAPTCHA no está configurado en el servidor.', 503);
}

jsonResponse(['site_key' => $siteKey]);
