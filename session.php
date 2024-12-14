<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function logout() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>