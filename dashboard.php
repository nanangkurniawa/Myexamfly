<?php
include('session.php');
include "db.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Ambil durasi ujian dari database
$query = "SELECT value FROM settings WHERE `key` = 'exam_duration'";
$result = $conn->query($query);
$examDuration = $result->fetch_assoc()['value'] ?? 30;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Halaman Ujian Online</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
            body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 600px;
        }

        header h1 {
            font-size: 36px;
            color: #007bff;
        }

        header p {
            color: #6c757d;
        }

        .exam-info p {
            margin-bottom: 10px;
        }

        .start-button .btn {
            background-color: #28a745;
            border: none;
            color: white;
            padding: 12px 24px;
            font-size: 18px;
            border-radius: 8px;
            text-transform: uppercase;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .start-button .btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        footer {
            font-size: 14px;
            color: #6c757d;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="mb-4">
            <h1 class="display-4"><i class="fas fa-graduation-cap"></i> </h1>
            <h1 class="display-4"> Selamat Datang di Ujian Online</h1>
            <p class="lead">Silakan persiapkan diri Anda sebelum memulai ujian.</p>
        </header>

        <div class="exam-info mb-4">
            <p><strong><i class="fas fa-user"></i> Nama Siswa:</strong> <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong><i class="fas fa-book"></i> Mata Pelajaran:</strong> Matematika</p>
            <p><strong><i class="fas fa-clock"></i> Durasi Ujian:</strong> <?php echo $examDuration; ?> Menit</p>
           
        </div>

        <div class="start-button">
            <a href="exam.php" class="btn btn-success btn-lg">Mulai Ujian</a>
        </div>
    

    <footer class="text-center mt-5">
        <p>&copy; 2024 MyExam | Dibuat dengan <span style="color: red;">&#9829;</span> oleh Tim Ujian</p>
    </footer>
    </div>


    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
