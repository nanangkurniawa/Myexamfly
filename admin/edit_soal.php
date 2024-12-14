<?php
include('../session.php');
include('../db.php');

// Pastikan hanya admin yang dapat mengakses halaman ini
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Periksa apakah ID soal disediakan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: kelola_soal.php");
    exit();
}

$id = intval($_GET['id']);

// Ambil data soal berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Soal tidak ditemukan.";
    exit();
}

$row = $result->fetch_assoc();

// Proses pembaruan soal
if (isset($_POST['update_question'])) {
    $question_text = $_POST['question'];
    $option_a = $_POST['option_a'];
    $option_b = $_POST['option_b'];
    $option_c = $_POST['option_c'];
    $option_d = $_POST['option_d'];
    $correct_option = $_POST['correct_option'];
    $kategori = $_POST['kategori'];

    // Update soal ke database
    $update_stmt = $conn->prepare("UPDATE questions SET question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?, kategori = ? WHERE id = ?");
    $update_stmt->bind_param("sssssssi", $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option, $kategori, $id);
    $update_stmt->execute();

    header("Location: kelola_soal.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Soal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin-top: 50px;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Edit Soal</h2>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="question" class="form-label">Soal</label>
                <textarea id="question" name="question" rows="4" class="form-control" required><?= htmlspecialchars($row['question']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="option_a" class="form-label">Opsi A</label>
                <input type="text" id="option_a" name="option_a" class="form-control" value="<?= htmlspecialchars($row['option_a']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="option_b" class="form-label">Opsi B</label>
                <input type="text" id="option_b" name="option_b" class="form-control" value="<?= htmlspecialchars($row['option_b']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="option_c" class="form-label">Opsi C</label>
                <input type="text" id="option_c" name="option_c" class="form-control" value="<?= htmlspecialchars($row['option_c']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="option_d" class="form-label">Opsi D</label>
                <input type="text" id="option_d" name="option_d" class="form-control" value="<?= htmlspecialchars($row['option_d']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="correct_option" class="form-label">Jawaban Benar</label>
                <select id="correct_option" name="correct_option" class="form-select" required>
                    <option value="a" <?= $row['correct_option'] === 'a' ? 'selected' : '' ?>>A</option>
                    <option value="b" <?= $row['correct_option'] === 'b' ? 'selected' : '' ?>>B</option>
                    <option value="c" <?= $row['correct_option'] === 'c' ? 'selected' : '' ?>>C</option>
                    <option value="d" <?= $row['correct_option'] === 'd' ? 'selected' : '' ?>>D</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="kategori" class="form-label">Kategori</label>
                <select id="kategori" name="kategori" class="form-select" required>
                    <option value="1" <?= $row['kategori'] == '1' ? 'selected' : '' ?>>C1</option>
                    <option value="2" <?= $row['kategori'] == '2' ? 'selected' : '' ?>>C2</option>
                    <option value="3" <?= $row['kategori'] == '3' ? 'selected' : '' ?>>C3</option>
                    <option value="4" <?= $row['kategori'] == '4' ? 'selected' : '' ?>>C4</option>
                    <option value="5" <?= $row['kategori'] == '5' ? 'selected' : '' ?>>C5</option>
                    <option value="6" <?= $row['kategori'] == '6' ? 'selected' : '' ?>>C6</option>
                </select>
            </div>
            <button type="submit" name="update_question" class="btn btn-primary w-100">Simpan Perubahan</button>
        </form>
    </div>
</body>
</html>
