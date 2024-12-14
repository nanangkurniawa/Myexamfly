<?php
include('../session.php');
include('../db.php');
// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}


// Simpan pengaturan batas soal per kategori ke database


// Ambil batas soal dari database untuk ditampilkan di form
$category_limit = [];
$query = "SELECT category_id, question_limit FROM category_limits";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $category_limit[$row['category_id']] = $row['question_limit'];
    }
}

// Ambil pengaturan batas soal jika ada di sesi
$category_limit += [
    1 => 3,
    2 => 3,
    3 => 3,
    4 => 3,
    5 => 3,
    6 => 3,
];
// Fungsi untuk menambahkan soal
if (isset($_POST['add_question'])) {
    $question_text = $_POST['question'];
    $option_a = $_POST['option_a'];
    $option_b = $_POST['option_b'];
    $option_c = $_POST['option_c'];
    $option_d = $_POST['option_d'];
    $correct_option = $_POST['correct_option'];
    $kategori=$_POST['kategori'];

    $stmt = $conn->prepare("INSERT INTO questions (question, option_a, option_b, option_c, option_d, correct_option,kategori) VALUES (?, ?, ?, ?, ?, ?,?)");
    $stmt->bind_param("sssssss", $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option,$kategori);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Soal berhasil ditambahkan!";
        $_SESSION['message_type'] = "success"; // Untuk tipe pesan (opsional)
    } else {
        $_SESSION['message'] = "Gagal menambahkan soal. Silakan coba lagi.";
        $_SESSION['message_type'] = "danger"; // Untuk tipe pesan error
    }
    header("Location: kelola_soal.php");
    exit;
}

// Fungsi untuk menghapus soal
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Soal berhasil dihapus!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal menghapus soal. Silakan coba lagi.";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: kelola_soal.php");
    exit;
}

// Fungsi untuk mengedit soal
if (isset($_POST['edit_question'])) {
    $id = $_POST['id'];
    $question_text = $_POST['question'];
    $option_a = $_POST['option_a'];
    $option_b = $_POST['option_b'];
    $option_c = $_POST['option_c'];
    $option_d = $_POST['option_d'];
    $correct_option = $_POST['correct_option'];
    $kategori=$_POST['kategori'];

    $stmt = $conn->prepare("UPDATE questions SET question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?, kategori=? WHERE id = ?");
    $stmt->bind_param("sssssssi", $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option,$kategori, $id);
    $stmt->execute();
    header("Location: kelola_soal.php");
    exit;
}

// Ambil semua soal
$query = "SELECT * FROM questions";
$result = $conn->query($query);

if (isset($_POST['set_exam_limit'])) {
    // Ambil pengaturan soal per kategori
    $category_limit = [
        1 => intval($_POST['category_1_limit']),
        2 => intval($_POST['category_2_limit']),
        3 => intval($_POST['category_3_limit']),
        4 => intval($_POST['category_4_limit']),
        5 => intval($_POST['category_5_limit']),
        6 => intval($_POST['category_6_limit']),
    ];

    // Simpan pengaturan soal ke database
    foreach ($category_limit as $category => $limit) {
        $stmt = $conn->prepare("INSERT INTO category_limits (category_id, question_limit) 
                                VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE question_limit = ?");
        $stmt->bind_param("iii", $category, $limit, $limit);
        $stmt->execute();
    }

    // Simpan pengaturan timer ke database
    $timer = intval($_POST['timer']);
    $stmt = $conn->prepare("INSERT INTO settings (`key`, `value`) 
                            VALUES ('exam_duration', ?) 
                            ON DUPLICATE KEY UPDATE `value` = ?");
    $stmt->bind_param("ii", $timer, $timer);
    $stmt->execute();

    // Simpan ke sesi (opsional)
    $_SESSION['category_limit'] = $category_limit;
    $_SESSION['exam_duration'] = $timer;

    // Redirect dengan pesan sukses
    $_SESSION['message'] = "Pengaturan berhasil diterapkan!";
    $_SESSION['message_type'] = "success";
    header("Location: kelola_soal.php");
    exit();
}


// Mengambil nilai pencarian dan filter kategori dari query string (URL)
$search = isset($_GET['search']) ? $_GET['search'] : '';
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Menyiapkan parameter pencarian
$search_param = $search ? "%" . $search . "%" : null;

// Hitung total jumlah data
$count_query = "SELECT COUNT(*) FROM questions WHERE 1";
if ($search) {
    $count_query .= " AND question LIKE ?";
}
if ($kategori_filter) {
    $count_query .= " AND kategori = ?";
}

$count_stmt = $conn->prepare($count_query);

// Bind parameter untuk count
if ($search && $kategori_filter) {
    $count_stmt->bind_param("ss", $search_param, $kategori_filter);
} elseif ($search) {
    $count_stmt->bind_param("s", $search_param);
} elseif ($kategori_filter) {
    $count_stmt->bind_param("s", $kategori_filter);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$row = $count_result->fetch_row();
$total_records = $row[0]; // Total jumlah soal

// Menyiapkan pagination
$limit = 10; // Jumlah soal per halaman
$total_pages = ceil($total_records / $limit); // Total halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Halaman saat ini
$page = ($page > $total_pages) ? $total_pages : $page; // Pastikan halaman tidak lebih besar dari total halaman
$offset = ($page - 1) * $limit; // Offset berdasarkan halaman

// Query untuk mengambil soal dengan pencarian dan kategori
$query = "SELECT * FROM questions WHERE 1";
if ($search) {
    $query .= " AND question LIKE ?";
}
if ($kategori_filter) {
    $query .= " AND kategori = ?";
}
$query .= " LIMIT ?, ?"; // Menambahkan paginasi

$stmt = $conn->prepare($query);

// Bind parameter dengan benar
if ($search && $kategori_filter) {
    $stmt->bind_param("ssii", $search_param, $kategori_filter, $offset, $limit);
} elseif ($search) {
    $stmt->bind_param("sii", $search_param, $offset, $limit);
} elseif ($kategori_filter) {
    $stmt->bind_param("sii", $kategori_filter, $offset, $limit);
} else {
    $stmt->bind_param("ii", $offset, $limit);
}

$stmt->execute();
$result = $stmt->get_result();
// Ambil data soal ke dalam array $questions
$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Soal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script>
    // Auto close alert setelah 5 detik
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 500); // Hapus elemen setelah fade
        }
    }, 5000);

    function setDeleteLink(url) {
        const confirmButton = document.getElementById('confirmDeleteButton');
        confirmButton.href = url;
    }
    
</script>


    <style>


        body {
            background-color: #f8f9fa;
        }
        .page-title {
            font-size: 2rem;
            font-weight: bold;
        }
        .card-header {
            font-size: 1.2rem;
            font-weight: 600;
        }
        .container {
            margin-top: 50px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
        }
        .btn-success {
            background-color: #28a745;
            border: none;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }

        .navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    background-color: #34495e;
    color: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    position: sticky;  /* Membuat navbar sticky */
    top: 0;  /* Pastikan navbar tetap di atas */
    z-index: 1000;  /* Memastikan navbar tetap di atas konten lainnya */
}

.navbar .brand {
    font-size: 1.8rem;
    font-weight: bold;
    color: #ecf0f1;
}

.navbar .menu a {
    color: #ecf0f1;
    text-decoration: none;
    margin: 0 15px;
    font-size: 1rem;
    font-weight: 600;
    transition: color 0.3s ease;
}

.navbar .menu a:hover {
    color: #1abc9c; /* Warna saat hover */
}

/* Styling dasar pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 20px;
}

/* Desain untuk pagination link */
.pagination .page-link {
    background-color: #fff;
    color: #007bff;
    border: 1px solid #dee2e6;
    padding: 10px 15px;
    margin: 0 5px;
    border-radius: 5px;
    font-size: 1rem;
    transition: background-color 0.3s, color 0.3s;
}

/* Styling untuk link yang aktif */
.pagination .page-item.active .page-link {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

/* Styling untuk link hover */
.pagination .page-link:hover {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

/* Styling untuk previous dan next link */
.pagination .page-item:first-child .page-link {
    border-radius: 5px 0 0 5px;
}

.pagination .page-item:last-child .page-link {
    border-radius: 0 5px 5px 0;
}

/* Desain ketika ukuran layar kecil */
@media (max-width: 768px) {
    .pagination {
        font-size: 0.9rem;
    }
}
/* Style untuk tombol Reset */
button[type="submit"] {
    background-color: #007bff; /* Warna latar belakang biru */
    color: white; /* Teks putih */
    font-size: 16px; /* Ukuran font */
    padding: 10px 20px; /* Padding agar tombol lebih besar */
    border: none; /* Menghapus border */
    border-radius: 5px; /* Membuat sudut tombol melengkung */
    cursor: pointer; /* Mengubah kursor saat hover */
    transition: background-color 0.3s ease; /* Efek transisi pada perubahan warna */
}

button[type="submit"]:hover {
    background-color: #0056b3; /* Warna latar belakang saat hover */
}

button[type="submit"]:focus {
    outline: none; /* Menghilangkan outline saat tombol fokus */
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); /* Menambahkan bayangan pada tombol saat fokus */
}

/* Tambahkan margin bawah untuk memberi jarak antara tombol dan elemen lainnya */
form {
    margin-top: 20px;
}

        </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="brand">MyExam</div>
        <div class="menu">
            <a href="index.php">Dashboard</a>
            <a href="../logout.php">Log Out</a>
        </div>
    </div>

    <!-- Notifikasi -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Tombol Atur Soal, Timer, dan Tambah Soal -->
    <section id="action-buttons" class="mt-4">
        <div class="card">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6 d-flex justify-content-start">
                        <button type="button" data-bs-toggle="modal" data-bs-target="#pengaturanModal" class="btn btn-primary w-100 text-white">
                            <i class="bi bi-gear-fill"></i> Atur Soal dan Timer
                        </button>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end">
                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                            <i class="bi bi-plus-circle"></i> Tambah Soal Baru
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Pengaturan Soal dan Timer -->
    <div class="modal fade" id="pengaturanModal" tabindex="-1" aria-labelledby="pengaturanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="pengaturanModalLabel">Pengaturan Soal dan Timer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="kelola_soal.php">
                        <div class="row g-3">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <div class="col-md-4">
                                    <label for="category_<?= $i ?>_limit" class="form-label">Jumlah Soal Kategori <?= $i ?>:</label>
                                    <input type="number" id="category_<?= $i ?>_limit" name="category_<?= $i ?>_limit" 
                                           min="0" max="100" class="form-control" 
                                           value="<?= $category_limit[$i] ?? 0 ?>" required>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label for="timer" class="form-label">Durasi Ujian (dalam menit):</label>
                                <input type="number" id="timer" name="timer" 
                                       min="1" max="300" class="form-control" 
                                       value="<?= $exam_duration ?? 60; ?>" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" name="set_exam_limit" class="btn btn-primary w-100">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Soal -->
    <div class="modal fade" id="addQuestionModal" tabindex="-1" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addQuestionModalLabel">Tambah Soal Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="kelola_soal.php" method="POST">
                        <div class="mb-3">
                            <label for="question" class="form-label">Pertanyaan:</label>
                            <textarea id="question" name="question" rows="3" class="form-control" placeholder="Tulis soal di sini..." required></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="option_a" class="form-control" placeholder="Opsi A" required>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="option_b" class="form-control" placeholder="Opsi B" required>
                            </div>
                            <div class="col-md-6 mt-2">
                                <input type="text" name="option_c" class="form-control" placeholder="Opsi C" required>
                            </div>
                            <div class="col-md-6 mt-2">
                                <input type="text" name="option_d" class="form-control" placeholder="Opsi D" required>
                            </div>
                        </div>
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label for="correct_option" class="form-label">Jawaban Benar:</label>
                                <select id="correct_option" name="correct_option" class="form-select" required>
                                    <option value="a">A</option>
                                    <option value="b">B</option>
                                    <option value="c">C</option>
                                    <option value="d">D</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="kategori" class="form-label">Kategori:</label>
                                <select id="kategori" name="kategori" class="form-select" required>
                                    <option value="1">C1</option>
                                    <option value="2">C2</option>
                                    <option value="3">C3</option>
                                    <option value="4">C4</option>
                                    <option value="5">C5</option>
                                    <option value="6">C6</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_question" class="btn btn-success mt-3 w-100">Tambah Soal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
 <!-- Form Pencarian dan Filter -->
 <form method="GET" action="kelola_soal.php">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Cari soal...">
                        </div>
                        <div class="col-md-4">
                            <select name="kategori" class="form-select">
                                <option value="">Pilih Kategori</option>
                                <option value="1" <?= $kategori_filter == '1' ? 'selected' : '' ?>>C1</option>
                                <option value="2" <?= $kategori_filter == '2' ? 'selected' : '' ?>>C2</option>
                                <option value="3" <?= $kategori_filter == '3' ? 'selected' : '' ?>>C3</option>
                                <option value="4" <?= $kategori_filter == '4' ? 'selected' : '' ?>>C4</option>
                                <option value="5" <?= $kategori_filter == '5' ? 'selected' : '' ?>>C5</option>
                                <option value="6" <?= $kategori_filter == '6' ? 'selected' : '' ?>>C6</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="kelola_soal.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </div>
                </form>
    <!-- Daftar Soal -->
    <section id="daftar-soal" class="mt-5">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Daftar Soal</h5>
            </div>
            <div class="card-body">
               

                 <!-- Tabel Soal -->
        <table class="table table-bordered table-hover">
            <thead class="table-light text-center">
                <tr>
                    <th>ID</th>
                    <th>Soal</th>
                    <th>A</th>
                    <th>B</th>
                    <th>C</th>
                    <th>D</th>
                    <th>Jawaban Benar</th>
                    <th>Kategori</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($questions)): ?>
                    <?php foreach ($questions as $index => $row): ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($row['question']) ?></td>
                            <td><?= htmlspecialchars($row['option_a']) ?></td>
                            <td><?= htmlspecialchars($row['option_b']) ?></td>
                            <td><?= htmlspecialchars($row['option_c']) ?></td>
                            <td><?= htmlspecialchars($row['option_d']) ?></td>
                            <td class="text-center"><?= strtoupper($row['correct_option']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['kategori']) ?></td>
                            <td class="text-center">
                                <a href="edit_soal.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="#" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmModal" onclick="setDeleteLink('kelola_soal.php?delete=<?= $row['id'] ?>')">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">Tidak ada soal yang ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="kelola_soal.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori_filter) ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="kelola_soal.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori_filter) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="kelola_soal.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori_filter) ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </section>

    <!-- Footer -->
    <footer class="mt-5 p-4 bg-light text-center">
        &copy; 2024 Sistem Kelola Soal. All rights reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>