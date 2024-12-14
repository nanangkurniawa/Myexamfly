<?php
include('../session.php');
include('../db.php');

// Pastikan hanya admin yang bisa mengakses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF token");
}

if (isset($_POST['result_id'])) {
    $result_id = $_POST['result_id'];

    // Validasi result_id
    if (!filter_var($result_id, FILTER_VALIDATE_INT)) {
        header("Location: results.php?message=error");
        exit();
    }

    // Query penghapusan
    $stmt = $conn->prepare("DELETE FROM results WHERE id = ?");
    $stmt->bind_param("i", $result_id);

    if ($stmt->execute()) {
        header("Location: results.php?message=success");
    } else {
        header("Location: results.php?message=error");
    }
    $stmt->close();
} else {
    header("Location: results.php?message=error");
}

$conn->close();
?>
