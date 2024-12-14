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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 30px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
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
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        a {
            display: block;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        a:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Detail Hasil Ujian</h1>

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

    <h2>Detail Jawaban</h2>
    <table>
        <tr>
            <th>No Soal</th>
            <th>Soal</th>
            <th>Pilihan Jawaban</th>
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
            questions.question,
            questions.option_a,
            questions.option_b,
            questions.option_c,
            questions.option_d
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
                    <td>
                        <ul>
                            <li>A. <?php echo htmlspecialchars($answer_row['option_a']); ?></li>
                            <li>B. <?php echo htmlspecialchars($answer_row['option_b']); ?></li>
                            <li>C. <?php echo htmlspecialchars($answer_row['option_c']); ?></li>
                            <li>D. <?php echo htmlspecialchars($answer_row['option_d']); ?></li>
                        </ul>
                    </td>
                    <td><?php echo htmlspecialchars($answer_row['user_answer']); ?></td>
                    <td><?php echo htmlspecialchars($answer_row['correct_answer']); ?></td>
                    <td><?php echo htmlspecialchars($answer_row['answer_status']); ?></td>
                </tr>
                <?php
            }
        } else {
            echo "<tr><td colspan='6'>Tidak ada jawaban untuk hasil ujian ini.</td></tr>";
        }
        ?>
    </table>

    <a href="view_results.php">Kembali ke daftar hasil</a>
</div>

</body>
</html>
<?php
} else {
    echo "Data tidak ditemukan untuk hasil ujian ID $result_id.";
}

$conn->close();
?>
