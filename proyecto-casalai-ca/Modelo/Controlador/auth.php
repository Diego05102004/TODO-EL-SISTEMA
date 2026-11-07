<?php
// AuthController.php

class Auth{
    public function login() {
        require_once 'Vista/s/login.php';
    }

    public function register() {
        require_once 'Vista/s/register.php';
    }
}
?>