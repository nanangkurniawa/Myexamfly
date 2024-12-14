<?php
include('../session.php');
include('../db.php');
// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body Style */
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f8f9fa;
            color: #212529;
        }

        /* Header Section */
        .header {
            background: #007bff;
            color: white;
            text-align: center;
            padding: 40px 20px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.2rem;
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: center;
            background-color: #343a40;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .navbar a {
            color: #f8f9fa;
            text-decoration: none;
            padding: 10px 15px;
            margin: 0 10px;
            border-radius: 5px;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .navbar a:hover {
            background-color: #495057;
        }

        /* Content Wrapper */
        .content-wrapper {
            flex: 1;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            width: 100%;
            max-width: 1200px;
        }

        /* Card Styles */
        .card {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #007bff;
        }

        .card p {
            font-size: 1rem;
            margin-bottom: 15px;
            color: #6c757d;
        }

        .card a {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .card a:hover {
            background: #0056b3;
        }

        /* Footer Section */
        .footer {
            background-color: #343a40;
            color: #f8f9fa;
            text-align: center;
            padding: 20px 0;
            font-size: 0.9rem;
        }

        .footer a {
            color: #ffc107;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dashboard Admin</h1>
        <p>Kelola sistem ujian Anda dengan mudah dan efisien</p>
    </div>
    <div class="navbar">
        <a href="results.php">Hasil Ujian</a>
        <a href="kelola_soal.php">Kelola Soal</a>
        <a href="kelola_pengguna.php">Kelola Pengguna</a>
        <a href="../logout.php">Keluar</a>
    </div>

    <div class="content-wrapper">
        <div class="dashboard">
            <div class="card">
                <h2>Hasil Ujian</h2>
                <p>Analisis dan kelola performa pengguna.</p>
                <a href="results.php">Lihat Hasil</a>
            </div>
            <div class="card">
                <h2>Kelola Soal</h2>
                <p>Buat dan atur soal untuk ujian online.</p>
                <a href="kelola_soal.php">Kelola Soal</a>
            </div>
            <div class="card">
                <h2>Kelola Pengguna</h2>
                <p>Manajemen akun pengguna dan admin.</p>
                <a href="kelola_user.php">Kelola Pengguna</a>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 MyExam. Semua hak cipta dilindungi.</p>
    </footer>
</body>
</html>
