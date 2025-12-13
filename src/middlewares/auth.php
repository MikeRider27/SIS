<?php
function auth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user'])) {
        header("Location: /");
        exit;
    }
    
    // You can add more validations here (roles, permissions, etc.)
    return true;
}