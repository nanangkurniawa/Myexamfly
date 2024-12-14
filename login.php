<?php 
session_start(); 
include "db.php";
include('session.php');

// Mengecek apakah form dikirimkan menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Mengambil data dari form dan menghapus spasi yang tidak diperlukan
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Mengecek apakah username dan password sudah diisi
    if (empty($username)) {
        header("Location: index.php?error=Harap Masukkan Username");
        exit();
    } elseif (empty($password)) {
        header("Location: index.php?error=Harap Masukkan Password");
        exit();
    }

    // Menyiapkan query untuk mencari user berdasarkan username
    $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();

    // Mengecek apakah user dengan username tersebut ditemukan
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Memverifikasi password dengan menggunakan password_verify (untuk keamanan password yang disimpan)
        if (password_verify($password, $user['password'])) {
            // Menyimpan data user ke dalam session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Mengarahkan user ke halaman yang sesuai berdasarkan role
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            // Password salah
            header("Location: index.php?error=Username atau Password Salah");
            exit();
        }
    } else {
        // Username tidak ditemukan
        header("Location: index.php?error=Username atau Password Salah");
        exit();
    }

} else {
    // Jika form tidak dikirim dengan metode POST, mengarahkan kembali ke halaman login
    header("Location: index.php");
    exit();
}
?>