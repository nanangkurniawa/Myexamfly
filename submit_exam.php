<?php
session_start();
include 'db.php';

// Pastikan pengguna telah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil jawaban pengguna dari form
$userAnswers = isset($_POST['answer']) ? $_POST['answer'] : [];

if (!isset($_SESSION['shuffled_questions']) || empty($_SESSION['shuffled_questions'])) {
    // Jika tidak ada soal, alihkan pengguna ke halaman lain atau tampilkan pesan error
    header("Location: login.php");  // Ganti dengan halaman yang sesuai
    exit;
}

// Ambil daftar soal dari sesi
$questions = $_SESSION['shuffled_questions'];

// Variabel untuk menghitung skor
$totalQuestions = count($questions);
$correctAnswers = 0;

// Periksa semua soal dan hitung jawaban benar
foreach ($questions as $question) {
    $questionId = $question['id'];
    $correctOption = $question['correct_option'];

    // Ambil jawaban pengguna untuk soal ini
    $userAnswer = isset($userAnswers[$questionId]) ? $userAnswers[$questionId] : null;

    // Tentukan apakah jawaban benar atau salah
    $isCorrect = ($userAnswer === $correctOption) ? 1 : 0;
     // Jika jawaban kosong, anggap salah
     $isCorrect = ($userAnswer !== null && $userAnswer === $correctOption) ? 1 : 0;

    // Hitung jawaban benar
    if ($isCorrect) {
        $correctAnswers++;
    }

    // Masukkan jawaban pengguna ke tabel answers sementara menunggu result_id
    // Namun, kita hanya memasukkan jawaban setelah result_id tersedia
}

// Hitung skor akhir
$score = ($correctAnswers / $totalQuestions) * 100;

// Simpan hasil ke database menggunakan prepared statement untuk mencegah SQL injection
$stmt = $conn->prepare("INSERT INTO results (user_id, score, total_questions, correct_answers, created_at) 
                        VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("iiii", $_SESSION['user_id'], $score, $totalQuestions, $correctAnswers);
if (!$stmt->execute()) {
    die("Error saving results: " . $stmt->error);
}

// Ambil ID hasil yang baru dimasukkan
$resultId = $stmt->insert_id;

// Sekarang masukkan jawaban pengguna ke dalam tabel answers dengan result_id yang valid
$answerStmt = $conn->prepare("INSERT INTO answers (result_id, question_id, user_answer, answer, is_correct) 
                              VALUES (?, ?, ?, ?, ?)");

foreach ($questions as $question) {
    $questionId = $question['id'];
    $correctOption = $question['correct_option'];
    $userAnswer = isset($userAnswers[$questionId]) ? $userAnswers[$questionId] : null;

    // Tentukan apakah jawaban benar atau salah
    $isCorrect = ($userAnswer === $correctOption) ? 1 : 0;
    $isCorrect = ($userAnswer !== null && $userAnswer === $correctOption) ? 1 : 0;
    // Masukkan data jawaban ke dalam tabel answers
    $answerStmt->bind_param("iissi", $resultId, $questionId, $userAnswer, $correctOption, $isCorrect);
    if (!$answerStmt->execute()) {
        die("Error saving answer: " . $answerStmt->error);
    }
}

// Bersihkan sesi terkait ujian
unset($_SESSION['user_answers']);
unset($_SESSION['time_remaining']);
unset($_SESSION['shuffled_questions']);
unset($_SESSION['exam_start_time']);

// Tutup koneksi
$stmt->close();
$answerStmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Ujian</title>
    <!-- Tambahkan Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tambahkan Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script>
    // Mencegah tombol back browser
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.pushState(null, null, location.href);
    };
</script>
    <style>
        body {
            background-color: #f4f4f9;
            color: #333;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            padding: 20px;
            text-align: center;
        }

        .result {
            font-size: 1.2em;
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .score {
            font-weight: bold;
            font-size: 2em;
            color: #4CAF50;
        }

        .button-container a {
            display: inline-block;
            padding: 10px 20px;
            background: #d9534f;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            transition: background 0.3s ease;
            text-decoration: none;
        }

        .button-container a:hover {
            background: #c9302c;
        }

        .details {
            font-size: 0.9em;
            margin-top: 10px;
            color: #777;
        }

        .icon {
            font-size: 2em;
            color: #4CAF50;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <i class="fas fa-check-circle icon"></i>
        <h1 class="text-success">Terima Kasih!</h1>
        <p>Ujian Anda telah selesai. Berikut adalah hasil Anda:</p>
        <div class="result">
            <p>Jumlah Soal: <strong><?= $totalQuestions ?></strong></p>
            <p>Jawaban Benar: <strong><?= $correctAnswers ?></strong></p>
            <p class="score">Skor Anda: <strong><?= $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0 ?>%</strong></p>
        </div>
        <div class="button-container mt-4">
            <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>
        <div class="details mt-3">
            <p>Jika Anda memiliki pertanyaan atau memerlukan bantuan, silakan hubungi tim pengajar.</p>
        </div>
    </div>
    <!-- Tambahkan Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
