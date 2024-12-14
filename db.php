<?php
$host = 'localhost';
$user = 'root';
$password = ''; // Ganti jika diperlukan
$database = 'ujian';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
