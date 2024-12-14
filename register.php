<?php
include "db.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Mengambil data dari form dan menghapus spasi yang tidak diperlukan
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    
    // Validasi input (cek apakah username dan password tidak kosong)
    if (empty($username)) {
        $em = "Mohon isikan username";
        header("Location: signup.php?error=" . urlencode($em));  // Pastikan pesan error aman
        exit;
    } elseif (empty($password)) {
        $em = "Mohon isikan password";
        header("Location: signup.php?error=" . urlencode($em));  // Pastikan pesan error aman
        exit;
    }

    // Hash password untuk menyimpan secara aman
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Cek apakah username sudah digunakan
    $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        // Jika username sudah ada, beri pesan error
        $em = "Username sudah digunakan. Silakan pilih username lain.";
        header("Location: signup.php?error=" . urlencode($em));  // Pastikan pesan error aman
        exit;
    } else {
        // Jika tidak ada masalah, simpan data user baru
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);

        if ($stmt->execute()) {
            // Jika berhasil, arahkan ke halaman login dengan pesan sukses
            $successMessage = "Akun berhasil dibuat, silahkan login";
            header("Location: signup.php?success=" . urlencode($successMessage));
            exit;
        } else {
            // Jika terjadi kesalahan saat eksekusi
            $em = "Terjadi kesalahan saat registrasi. Silakan coba lagi.";
            header("Location: signup.php?error=" . urlencode($em));  // Pastikan pesan error aman
            exit;
        }
    }
}
?>
