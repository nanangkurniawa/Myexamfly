<?php
include('../session.php');
include('../db.php');

// Pastikan hanya admin yang bisa mengakses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Mendapatkan id hasil ujian dari URL
if (isset($_GET['result_id'])) {
    $result_id = (int)$_GET['result_id'];  // Sanitasi input
} else {
    die("Result ID is required.");
}

// Query untuk mendapatkan detail hasil ujian
$query = "
    SELECT 
        results.id,
        users.username AS user_name,
        results.score,
        results.total_questions,
        results.correct_answers,
        results.created_at
    FROM 
        results
    INNER JOIN 
        users ON results.user_id = users.id
    WHERE 
        results.id = $result_id
";

// Eksekusi query untuk hasil ujian
$result = $conn->query($query);

// Cek apakah data ditemukan
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Hasil Ujian</title>
    <!-- Menambahkan Google Font untuk tipografi yang lebih baik -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Pengaturan Umum */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            font-size: 2em;
            margin-bottom: 20px;
        }

        /* Desain untuk kartu informasi */
        .card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .card h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        .card p {
            font-size: 1em;
            color: #555;
        }

        /* Layout untuk informasi hasil ujian */
        .result-info {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .result-info div {
            flex: 1 1 45%; /* Setiap div mengambil 45% lebar untuk 2 kolom */
            margin-bottom: 20px;
        }

        .result-info div th {
            width: 200px;
            text-align: left;
            font-weight: 600;
            padding: 8px 0;
        }

        .result-info div td {
            font-weight: 400;
            padding: 8px 0;
        }

        /* Pengaturan untuk Tabel */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }

        .button-back {
            display: inline-block;
            padding: 12px 25px;
            margin-top: 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        .button-back:hover {
            background-color: #45a049;
        }

        /* Responsif untuk tabel detail jawaban */
        @media (max-width: 768px) {
            /* Tabel jawaban tetap responsif */
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            table {
                width: 100%;
                min-width: 600px; /* Pastikan tabel tidak terlalu kecil */
            }

            th, td {
                white-space: nowrap; /* Menghindari pemotongan teks */
                padding: 8px;
                font-size: 14px;
            }

            /* Untuk kolom informasi hasil ujian, tampilkan satu kolom pada perangkat kecil */
            .result-info div {
                flex: 1 1 100%; /* Menjadi satu kolom di layar kecil */
                margin-bottom: 15px;
            }

            .card h3 {
                font-size: 1.3em;
            }

            h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Detail Hasil Ujian</h1>

    <div class="card">
        <h3>Informasi Hasil Ujian</h3>
        <div class="result-info">
            <div>
                <table>
                    <tr>
                        <th>ID Hasil</th>
                        <td><?php echo $row['id']; ?></td>
                    </tr>
                    <tr>
                        <th>Nama Pengguna</th>
                        <td><?php echo $row['user_name']; ?></td>
                    </tr>
                    <tr>
                        <th>Skor</th>
                        <td><?php echo $row['score']; ?>%</td>
                    </tr>
                </table>
            </div>
            <div>
                <table>
                    <tr>
                        <th>Total Soal</th>
                        <td><?php echo $row['total_questions']; ?></td>
                    </tr>
                    <tr>
                        <th>Jawaban Benar</th>
                        <td><?php echo $row['correct_answers']; ?></td>
                    </tr>
                    <tr>
                        <th>Waktu Ujian</th>
                        <td><?php echo date("d-m-Y H:i:s", strtotime($row['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Detail Jawaban</h3>
        <div class="table-container"> <!-- Pembungkus tabel responsif -->
            <table>
                <tr>
                    <th>No Soal</th>
                    <th>Soal</th>
                    <th>Jawaban Pengguna</th>
                    <th>Jawaban Benar</th>
                    <th>Status Jawaban</th>
                </tr>

                <?php
                // Query untuk mendapatkan jawaban detail dari answers table
                $answer_query = "
                SELECT 
                    answers.question_id,
                    answers.user_answer,
                    answers.answer AS correct_answer,
                    IF(answers.is_correct = 1, 'Benar', 'Salah') AS answer_status,
                    questions.question  -- Ambil teks soal dari tabel questions
                FROM 
                    answers
                INNER JOIN
                    questions ON answers.question_id = questions.id
                WHERE 
                    answers.result_id = $result_id
                ORDER BY 
                    answers.question_id
                ";

                // Eksekusi query untuk jawaban detail
                $answer_result = $conn->query($answer_query);

                // Cek apakah data jawaban ditemukan
                if ($answer_result->num_rows > 0) {
                    while ($answer_row = $answer_result->fetch_assoc()) {
                        ?>
                        <tr>
                            <td><?php echo $answer_row['question_id']; ?></td>
                            <td><?php echo htmlspecialchars($answer_row['question']); ?></td>
                            <td><?php echo htmlspecialchars($answer_row['user_answer']); ?></td>
                            <td><?php echo htmlspecialchars($answer_row['correct_answer']); ?></td>
                            <td><?php echo htmlspecialchars($answer_row['answer_status']); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='5'>Tidak ada jawaban untuk hasil ujian ini.</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>

    <a href="results.php" class="button-back">Kembali ke daftar hasil</a>
</div>

</body>
</html>
<?php
} else {
    echo "Data tidak ditemukan untuk hasil ujian ID $result_id.";
}

$conn->close();
?>
