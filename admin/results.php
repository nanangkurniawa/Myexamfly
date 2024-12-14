<?php
include('../session.php');
include('../db.php');

// Pastikan hanya admin yang bisa mengakses
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Generate CSRF Token jika belum ada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate token baru
}

// Ambil parameter pencarian dan filter
$cari = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Tentukan jumlah hasil per halaman
$hasilPerHalaman = 10;

// Query untuk menghitung total hasil
$queryHitung = "SELECT COUNT(*) AS total FROM results INNER JOIN users ON results.user_id = users.id WHERE 1=1";

// Tambahkan filter pencarian
if ($cari) {
    $cari = $conn->real_escape_string($cari);
    $queryHitung .= " AND users.username LIKE '%$cari%'";
}

// Tambahkan filter skor
if ($filter) {
    $filter = $conn->real_escape_string($filter);
    if ($filter === '>=90') {
        $queryHitung .= " AND results.score >= 90";
    } elseif ($filter === '>=70') {
        $queryHitung .= " AND results.score >= 70";
    } elseif ($filter === '<70') {
        $queryHitung .= " AND results.score < 70";
    }
}

// Eksekusi query untuk menghitung total
$hasilHitung = $conn->query($queryHitung);
$totalHasil = $hasilHitung->fetch_assoc()['total'];

// Hitung total halaman
$totalHalaman = ceil($totalHasil / $hasilPerHalaman);

// Ambil halaman saat ini dari parameter GET
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$halaman = max(1, min($halaman, $totalHalaman)); // Pastikan halaman dalam batas
$mulai = ($halaman - 1) * $hasilPerHalaman;

// Query untuk mengambil hasil ujian dengan paginasi
$query = "SELECT 
            results.id, 
            users.username AS user_name, 
            results.score, 
            results.total_questions, 
            results.correct_answers, 
            results.created_at
          FROM 
            results
          INNER JOIN 
            users 
          ON 
            results.user_id = users.id
          WHERE 1=1";

// Tambahkan filter pencarian
if ($cari) {
    $query .= " AND users.username LIKE '%$cari%'";
}

// Tambahkan filter skor
if ($filter) {
    if ($filter === '>=90') {
        $query .= " AND results.score >= 90";
    } elseif ($filter === '>=70') {
        $query .= " AND results.score >= 70";
    } elseif ($filter === '<70') {
        $query .= " AND results.score < 70";
    }
}

// Tambahkan pengurutan dan limit untuk paginasi
$query .= " ORDER BY results.score DESC, results.created_at ASC LIMIT $mulai, $hasilPerHalaman";

// Eksekusi query untuk mengambil data
$hasil = $conn->query($query);

// Cek apakah tombol export diklik
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    exportToCSV($cari, $filter);
    exit();
}

// Fungsi untuk mengekspor data ke CSV
function exportToCSV($cari = '', $filter = '') {
    global $conn;

    // Query untuk mengambil data sesuai filter
    $query = "SELECT 
                results.id, 
                users.username AS user_name, 
                results.score, 
                results.total_questions, 
                results.correct_answers, 
                results.created_at
              FROM 
                results
              INNER JOIN 
                users 
              ON 
                results.user_id = users.id
              WHERE 1=1";

    // Filter pencarian username
    if ($cari) {
        $cari = $conn->real_escape_string($cari);
        $query .= " AND users.username LIKE '%$cari%'";
    }

    // Filter skor
    if ($filter) {
        if ($filter === '>=90') {
            $query .= " AND results.score >= 90";
        } elseif ($filter === '>=70') {
            $query .= " AND results.score >= 70";
        } elseif ($filter === '<70') {
            $query .= " AND results.score < 70";
        }
    }

    // Eksekusi query
    $hasil = $conn->query($query);

    // Set header untuk download CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="hasil_ujian.csv"');

    // Membuka output stream
    $output = fopen('php://output', 'w');

    // Menulis header CSV
    fputcsv($output, ['Username', 'Skor', 'Total Soal', 'Jawaban Benar', 'Tanggal']);

    // Menulis data ke CSV
    if ($hasil->num_rows > 0) {
        while ($row = $hasil->fetch_assoc()) {
            fputcsv($output, [
                $row['user_name'],
                $row['score'],
                $row['total_questions'],
                $row['correct_answers'],
                date("d-m-Y H:i", strtotime($row['created_at']))
            ]);
        }
    }

    // Menutup output stream
    fclose($output);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Ujian - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.1/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.1/dist/sweetalert2.min.js"></script>
    <style>
        /* Reset styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }


        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background-color: #34495e;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
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
            color: #1abc9c;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

  h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #4CAF50;
        }

/* Wrapper untuk tabel yang bisa digulir secara horizontal */
.table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-top: 10px;
    padding-bottom: 10px;
}

/* Tabel dengan lebar 100% agar bisa menyesuaikan ukuran layar */
table {
    width: 100%;
    max-width: 100%;
    border-collapse: collapse;
    table-layout: fixed; /* Kolom memiliki lebar tetap */
}

/* Gaya umum untuk header dan cell */
th, td {
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
    text-overflow: ellipsis;
    white-space: nowrap; /* Hindari teks meluas terlalu banyak */
    overflow: hidden; /* Potong teks jika terlalu panjang */
}

th {
    background-color: #4CAF50;
    color: white;
}

td {
    max-width: 150px; /* Batasi lebar kolom agar tidak terlalu lebar */
}

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a {
            display: inline-block;
            padding: 8px 15px;
            font-size: 14px;
            text-decoration: none;
            color: #4CAF50;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .pagination a.active {
            background-color: #4CAF50;
            color: white;
            pointer-events: none;
        }

        .pagination a:hover {
            background-color: #45a049;
            color: white;
        }

        .alert {
            max-width: 800px;
            margin: 20px auto;
            padding: 10px 15px;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
        }

        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .alert-error {
            background-color: #f2dede;
            color: #a94442;
        }

        .action-buttons {
    display: flex;
    justify-content: start;
    align-items: center;
    gap: 5px;
}

.detail-button,
.delete-button {
    display: inline-block;
    padding: 8px 10px;
    font-size: 12px;
    text-align: center;
    text-decoration: none;
    border-radius: 5px;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.detail-button {
    color: white;
    background-color: #3498db;
}

.detail-button:hover {
    background-color: #2980b9;
    transform: scale(1.05);
}

.delete-button {
    color: white;
    background-color: #d9534f;
    border: none;
    cursor: pointer;
}

.delete-button:hover {
    background-color: #c9302c;
    transform: scale(1.05);
}

        .filter-search {
            max-width: 1200px;
            margin: 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form input[type="text"],
        .filter-form select {
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .filter-form button {
            padding: 8px 15px;
            font-size: 14px;
            color: white;
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .filter-form button:hover {
            background-color: #45a049;
        }

        .reset-button {
            padding: 8px 15px;
            font-size: 14px;
            color: #333;
            background-color: #ddd;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .reset-button:hover {
            background-color: #bbb;
        }

        .export-container {
            margin-left: auto;
        }

        .export-button {
            padding: 8px 15px;
            font-size: 14px;
            color: white;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .export-button:hover {
            background-color: #0056b3;
        }

        /* Untuk layar kecil (ponsel) */
@media (max-width: 768px) {
    .navbar {
        flex-direction: column;
        text-align: center;
    }

    .navbar .menu {
        margin-top: 10px;
    }

    .navbar .menu a {
        display: block;
        margin: 5px 0;
    }

    .container {
        padding: 15px;
    }

    table {
        font-size: 12px;
    }

    th, td {
        padding: 5px;
    }

    .filter-search {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-form {
        flex-direction: column;
        gap: 5px;
    }

    .filter-form input[type="text"],
    .filter-form select,
    .filter-form button {
        width: 100%;
    }

    .reset-button {
        width: 100%;
    }

    .export-container {
        margin-top: 10px;
    }

    .export-button {
        width: 100%;
        text-align: center;
    }

    .pagination {
        flex-wrap: wrap;
        gap: 5px;
    }

    .pagination a {
        padding: 6px 10px;
        font-size: 12px;
    }

    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .detail-button,
    .delete-button {
        font-size: 12px;
        padding: 5px;
        text-align: center;
    }
}

/* Untuk layar sedang (tablet) */
@media (max-width: 992px) {
    .filter-search {
        flex-wrap: wrap;
    }

    .filter-form input[type="text"],
    .filter-form select,
    .filter-form button {
        width: auto;
        flex: 1;
    }

    .export-container {
        width: 100%;
        text-align: right;
    }
}

    </style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="brand">MyExam</div>
    <div class="menu">
        <a href="index.php">Dashboard</a> <!-- Dashboard Button -->
        <a href="../logout.php">Logout</a>
    </div>
</div>

<!-- Main Container -->
<div class="container">

    <!-- Page Title -->
    <h1>Hasil Ujian</h1>

    <!-- Filter/Search Form -->
    <div class="filter-search">
        <form class="filter-form" method="GET" action="">
            <input type="text" name="search" placeholder="Cari Username..." value="<?= htmlspecialchars($cari) ?>">
            <select name="filter">
                <option value="">Filter Skor</option>
                <option value=">=90" <?= $filter === '>=90' ? 'selected' : '' ?>>>= 90</option>
                <option value=">=70" <?= $filter === '>=70' ? 'selected' : '' ?>>>= 70</option>
                <option value="<70" <?= $filter === '<70' ? 'selected' : '' ?>>< 70</option>
            </select>
            <button type="submit">Cari</button>
            <a href="results.php" class="reset-button">Reset</a> <!-- Reset button -->
        </form>

        <div class="export-container">
            <a href="results.php?export=csv&search=<?= urlencode($cari) ?>&filter=<?= urlencode($filter) ?>" class="export-button">Export CSV</a>
        </div>
    </div>

    <?php
// PHP untuk mengambil data dari database
// (Pastikan Anda sudah mengambil data dengan benar)
?>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Skor</th>
                <th>Total Soal</th>
                <th>Jawaban Benar</th>
                <th>Tanggal</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($hasil->num_rows > 0): ?>
                <?php while ($row = $hasil->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['score']) ?></td>
                        <td><?= htmlspecialchars($row['total_questions']) ?></td>
                        <td><?= htmlspecialchars($row['correct_answers']) ?></td>
                        <td><?= date("d-m-Y H:i", strtotime($row['created_at'])) ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="view_results_detail.php?result_id=<?= $row['id'] ?>" class="detail-button">Lihat Detail</a>
                                <form method="POST" action="delete_result.php" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="result_id" value="<?= htmlspecialchars($row['id']) ?>">
                                    <button type="submit" class="delete-button">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Tidak ada hasil untuk ditampilkan</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


    <script>
         // Cek URL parameter untuk pesan notifikasi
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');

    if (message === 'success') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Data berhasil dihapus.',
            showConfirmButton: false,
            timer: 1500
        });
    } else if (message === 'error') {
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'Terjadi kesalahan saat menghapus data.',
            showConfirmButton: false,
            timer: 1500
        });
    }

    // SweetAlert untuk Konfirmasi Penghapusan
    document.addEventListener('DOMContentLoaded', () => {
        const deleteButtons = document.querySelectorAll('.delete-button');

        deleteButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                event.preventDefault(); // Mencegah pengiriman form langsung

                const form = button.closest('form'); // Ambil form terkait
                const username = button.getAttribute('username'); // Ambil username untuk pesan

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: `Data akan dihapus secara permanen!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit(); // Kirim form jika konfirmasi diterima
                    }
                });
            });
        });
    });
</script>

    <!-- Pagination -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($cari) ?>&filter=<?= urlencode($filter) ?>" class="<?= $halaman === $i ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>

</div>

</body>
</html>
