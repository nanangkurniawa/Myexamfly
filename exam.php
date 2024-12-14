<?php
session_start();
include 'db.php';

// Pastikan pengguna telah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}



// Ambil jawaban yang disimpan dari session
$userAnswers = $_SESSION['user_answers'] ?? [];

// Ambil pengaturan batas soal dari database
$category_limit = [];
$query = "SELECT category_id, question_limit FROM category_limits";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $category_limit[$row['category_id']] = $row['question_limit'];
    }
}

// Default batas soal jika belum ada di database
$category_limit += [1 => 3, 2 => 3, 3 => 3, 4 => 3, 5 => 3, 6 => 3];

// Ambil soal berdasarkan kategori tanpa duplikasi
$questions = [];
$usedQuestionIds = $_SESSION['used_question_ids'] ?? []; // Ambil soal yang sudah dipilih sebelumnya

foreach ($category_limit as $category => $limit) {
    // Hitung soal yang tersedia
    $query = "SELECT id FROM questions WHERE kategori = $category AND id NOT IN (" . implode(',', $usedQuestionIds ?: [0]) . ")";
    $result = $conn->query($query);
    $availableQuestions = $result->num_rows;

    // Sesuaikan batas soal jika jumlah soal tidak mencukupi
    if ($availableQuestions < $limit) {
        $limit = $availableQuestions;
    }

    // Ambil soal
    $query = "SELECT id, question, option_a, option_b, option_c, option_d, correct_option 
              FROM questions 
              WHERE kategori = $category AND id NOT IN (" . implode(',', $usedQuestionIds ?: [0]) . ")
              ORDER BY RAND()
              LIMIT $limit";
    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $questions[] = $row;
            $usedQuestionIds[] = $row['id']; // Tambahkan ke daftar soal yang digunakan
        }
    }
}

// Simpan daftar soal yang digunakan dalam sesi
$_SESSION['used_question_ids'] = $usedQuestionIds;

// Acak soal menggunakan Fisher-Yates Shuffle
function fisherYatesShuffle($questions) {
    $n = count($questions);
    for ($i = $n - 1; $i > 0; $i--) {
        $j = rand(0, $i);
        $temp = $questions[$i];
        $questions[$i] = $questions[$j];
        $questions[$j] = $temp;
    }
    return $questions;
}

// Simpan soal yang sudah diacak dalam session
if (!isset($_SESSION['shuffled_questions'])) {
    $_SESSION['shuffled_questions'] = fisherYatesShuffle($questions);
}

$shuffledQuestions = $_SESSION['shuffled_questions'];

// Ambil durasi ujian dari database
$query = "SELECT value FROM settings WHERE `key` = 'exam_duration'";
$result = $conn->query($query);
$examDuration = $result->fetch_assoc()['value'] ?? 30; // Default ke 30 menit jika tidak ada pengaturan

// Set durasi ke dalam session
if (!isset($_SESSION['exam_start_time'])) {
    $_SESSION['exam_start_time'] = time();
}
if (!isset($_SESSION['time_remaining'])) {
    $_SESSION['time_remaining'] = $examDuration * 60; // Durasi dalam detik
}

$timeElapsed = time() - $_SESSION['exam_start_time'];
$timeRemaining = max($_SESSION['time_remaining'] - $timeElapsed, 0);

// Redirect jika waktu habis
if ($timeRemaining <= 0) {
    header("Location: submit_exam.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/sweetalert.js"></script>
    <title>Ujian Online</title>
    <script>
        let timeRemaining = <?= $timeRemaining ?>;

        function startTimer() {
            const timerElement = document.getElementById('timer');
            const interval = setInterval(() => {
                if (timeRemaining <= 0) {
                    clearInterval(interval);
                    Swal.fire({
                        title: 'Waktu Habis!',
                        text: 'Ujian akan dikirim secara otomatis.',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        document.getElementById('examForm').submit();
                    });
                    return;
                }
                if (timeRemaining % 60 === 0) updateServerTime(timeRemaining);
                timeRemaining--;
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                timerElement.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            }, 1000);
        }

        function updateProgress() {
    const answered = document.querySelectorAll('input[type="radio"]:checked').length;
    const total = document.querySelectorAll('.question').length;
    document.getElementById('answeredCount').textContent = answered;
}

        function updateServerTime(remainingTime) {
            const formData = new FormData();
            formData.append('time_remaining', remainingTime);
            fetch('save_timer.php', {
                method: 'POST',
                body: formData
            }).catch(error => console.error('Error:', error));
        }

        function saveAnswer(questionId, selectedOption) {
            const formData = new FormData();
            formData.append('question_id', questionId);
            formData.append('selected_option', selectedOption);

            fetch('save_answer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .catch(error => console.error('Error:', error));
        }

        window.onload = () => {
            startTimer();
updateProgress(); // Hitung progres saat halaman dimuat
            const form = document.getElementById('examForm');
            form.addEventListener('submit', validateForm);

            document.querySelectorAll('input[type="radio"]').forEach(input => {
                input.addEventListener('change', function () {
                    const questionId = this.name.match(/\d+/)[0]; 
                    const selectedOption = this.value;
                    saveAnswer(questionId, selectedOption);
                    updateProgress(); // Update progres
                });
            });
        };

        function validateForm(event) {
    event.preventDefault(); // Mencegah pengiriman form langsung

    let isValid = true;
    document.querySelectorAll('.question').forEach(question => {
        const isAnswered = Array.from(question.querySelectorAll('input[type="radio"]')).some(input => input.checked);
        if (!isAnswered) {
            isValid = false;
            question.classList.add('unanswered');
        } else {
            question.classList.remove('unanswered');
        }
    });

    if (!isValid) {
        Swal.fire({
            title: 'Jawaban Belum Lengkap!',
            text: 'Harap isi semua jawaban sebelum mengirim.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    // Jika semua pertanyaan terjawab, tampilkan konfirmasi
    Swal.fire({
        title: 'Kirim Jawaban?',
        text: 'Apakah Anda yakin ingin mengirim jawaban Anda? Jawaban tidak bisa diubah setelah dikirim.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Kirim!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Kirim form jika pengguna mengonfirmasi
            document.getElementById('examForm').submit();
        }
    });
}
    </script>
    <style>
        /* General Styles */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f9;
    color: #333;
    line-height: 1.6;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Kontainer untuk timer dan progress */
.timer-progress-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

/* Timer */
#timer {
    font-size: 1.8rem;
    font-weight: bold;
    color: white;
    background-color: #e74c3c;
    padding: 10px 20px;
    border-radius: 5px;
    text-align: center;
}

/* Progress */
#progress {
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
}

.unanswered {
    border-color: #e74c3c !important;
    background-color: #ffe6e6 !important;
}

/* Question Styles */
.question {
    margin-bottom: 30px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}

.question p {
    font-weight: bold;
    margin-bottom: 10px;
}

label {
    display: block;
    margin: 8px 0;
}

/* Button Styles */
button {
    display: inline-block;
    background: #3498db;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.3s ease;
}

button:hover {
    background: #2980b9;
}

/* Submit Button */
.submit-btn {
    display: block;
    width: 100%;
    text-align: center;
    margin-top: 20px;
}

/* Responsive Design: Mobile View */
@media (max-width: 767px) {
    body {
        padding: 0 10px;
    }

    .container {
        padding: 15px;
        margin: 10px;
    }


    .question {
        padding: 12px;
    }

    .question p {
        font-size: 1rem;
    }

    label {
        font-size: 0.9rem;
    }

    button {
        width: 100%;
        padding: 12px;
        font-size: 1.1rem;
    }
}

/* Tablet View */
@media (max-width: 1024px) and (min-width: 768px) {
    .container {
        padding: 20px;
        margin: 20px auto;
    }

    #timer {
        font-size: 1.4rem;
        text-align: center;
    }

    .question {
        padding: 15px;
    }

    button {
        width: 100%;
        padding: 12px;
        font-size: 1.1rem;
    }
}

/* Desktop View */
@media (min-width: 1025px) {
    .container {
        max-width: 900px;
        margin: 30px auto;
    }

    #timer {
        font-size: 1.6rem;
    }

    .question {
        padding: 20px;
    }

    button {
        width: auto;
        padding: 10px 20px;
    }
}

    </style>
</head>
<body>
<div class="container">
<div class="timer-progress-container">
    <div id="progress">
        Soal Terjawab: <span id="answeredCount">0</span> / <?= count($shuffledQuestions) ?>
    </div>
    <div id="timer"><?= floor($timeRemaining / 60) ?>:<?= str_pad($timeRemaining % 60, 2, '0', STR_PAD_LEFT) ?></div>
</div>

    <form id="examForm" action="submit_exam.php" method="POST">
        <?php foreach ($shuffledQuestions as $index => $question): ?>
            <div class="question">
                <p><?= ($index + 1) . ". " . htmlspecialchars($question['question']) ?></p>
                <?php foreach (['a', 'b', 'c', 'd'] as $option): ?>
                    <label>
                        <input type="radio" name="answer[<?= $question['id'] ?>]" value="<?= $option ?>" 
                            <?= isset($userAnswers[$question['id']]) && $userAnswers[$question['id']] === $option ? 'checked' : '' ?>>
                        <?= htmlspecialchars($question['option_' . $option]) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="submit-btn">
            <button type="submit">Kirim Jawaban</button>
        </div>
    </form>
</div>
</body>
</html>
