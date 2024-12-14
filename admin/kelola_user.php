<?php
include('../session.php');
include('../db.php');
// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$modal_open = false; // Default: modal tertutup

// Menambah pengguna baru

if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    $role = $_POST['role'];

    // Periksa apakah username sudah digunakan
    $sql_check = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "Username sudah digunakan. Silakan pilih username lain.";
        $modal_open = true;
    } else {
        // Tambahkan pengguna baru ke database
        $sql_insert = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("sss", $username, $password, $role);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Pengguna berhasil ditambahkan.";
            $modal_open = false;
        } else {
            $_SESSION['error_message']= "Terjadi kesalahan saat menambahkan pengguna.";
            $modal_open = true;
        }
    }
}

if (isset($success_message)) {
    // Reset nilai form hanya jika sukses
    $_POST['username'] = '';
    $_POST['password'] = '';
    $_POST['role'] = 'user';
}

// Menghapus pengguna
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $sql = "DELETE FROM users WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        $_SESSION['success_message'] = "Pengguna berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus pengguna. Pastikan tidak ada data terkait.";
    }
}

// Konfigurasi pagination
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Halaman saat ini
$offset = ($page - 1) * $limit; // Hitung offset untuk query

// Hitung total data
$sql_count = "SELECT COUNT(*) AS total FROM users";
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_data = $row_count['total'];

// Hitung jumlah halaman
$total_pages = ceil($total_data / $limit);


$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%" . $search_query . "%";

// Ambil data untuk halaman saat ini
if ($search_query) {
    $sql_users = "SELECT * FROM users WHERE username LIKE ? ORDER BY role ASC, username ASC LIMIT $offset, $limit";
    $stmt = $conn->prepare($sql_users);
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result_users = $stmt->get_result();
} else {
    $sql_users = "SELECT * FROM users ORDER BY role ASC, username ASC LIMIT $offset, $limit";
    $result_users = $conn->query($sql_users);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/sweetalert.js"></script>
    <title>Kelola Pengguna</title>

    <script>
         // Fungsi untuk membuka modal
function openModal() {
    const modal = document.getElementById('userModal');
    modal.classList.add('show'); // Menambahkan kelas 'show' untuk menampilkan modal
}

// Fungsi untuk menutup modal
function closeModal() {
    const modal = document.getElementById('userModal');
    modal.classList.remove('show'); // Menghapus kelas 'show' untuk menyembunyikan modal
}

window.onload = function() {
    // Pastikan SweetAlert hanya dipanggil sekali
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            title: 'Sukses!',
            text: '<?php echo $_SESSION['success_message']; ?>',
            icon: 'success',
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['success_message']); ?> // Menghapus pesan sukses setelah ditampilkan
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?php echo $_SESSION['error_message']; ?>',
            icon: 'error',
            confirmButtonText: 'Coba Lagi'
        });
        <?php unset($_SESSION['error_message']); ?> // Menghapus pesan error setelah ditampilkan
    <?php endif; ?>

    // Pastikan modal tetap terbuka saat ada kesalahan atau sukses
    <?php if (isset($modal_open) && $modal_open): ?>
        openModal();
    <?php endif; ?>
}


    function confirmDelete(userId) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Anda akan menghapus pengguna ini!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'kelola_user.php?delete_id=' + userId;
            }
        });
    }
    </script>

<style>
  /* Gaya Umum */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f7fa;
    margin: 0;
    padding: 0;
}

h1 {
    text-align: center;
    color: #333;
}

/* Styling untuk Kontainer */
.container {
    width: 90%;
    max-width: 1100px;
    margin: 30px auto;
    background-color: #ffffff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Styling untuk Tabel Pengguna */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #4CAF50;
    color: white;
}

tr:hover {
    background-color: #f1f1f1;
}

/* Styling untuk Tombol */
.btn {
    background-color: #4CAF50;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
}

.btn:hover {
    background-color: #45a049;
}

/* Gaya Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
    z-index: 1000;
    opacity: 0;
    transition: opacity 0.3s ease-in-out; /* Smooth transition for opacity */
}

.modal.show {
    display: flex;
    opacity: 1; /* Show modal when class 'show' is added */
}

.modal-content {
    background-color: #ffffff;
    padding: 30px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    text-align: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    animation: modal-fade-in 0.3s ease-in-out;
}

@keyframes modal-fade-in {
    from {
        transform: scale(0.9);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.modal-content h2 {
    margin-bottom: 20px;
    font-size: 1.5rem;
    color: #333;
}

.modal-content label {
    display: block;
    text-align: left;
    margin: 10px 0 5px;
    font-weight: bold;
    font-size: 1rem;
    color: #555;
}

.modal-content input,
.modal-content select {
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    color: #555;
}

.modal-content input[type="submit"] {
    background-color: #4CAF50;
    color: white;
    font-size: 1.1rem;
    padding: 12px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.3s ease, transform 0.2s ease; /* Add transition */
}

.modal-content input[type="submit"]:hover {
    background-color: #45a049;
    transform: scale(1.05); /* Add slight scale effect on hover */
}

.close-btn {
    background-color: #f44336;
    color: white;
    font-size: 1rem;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 15px;
    width: 100%;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.close-btn:hover {
    background-color: #e53935;
    transform: scale(1.05); /* Add slight scale effect on hover */
}

/* Navbar Styling */
.navbar {
    position: sticky;
    top: 0;
    z-index: 1000;
    background-color: #343a40;
    color: white;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.navbar a {
    color: white;
    text-decoration: none;
    margin: 0 10px;
    font-weight: bold;
}

.navbar a:hover {
    color: #00bcd4;
}

.navbar .brand {
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
}

.navbar .center {
    flex-grow: 1;
    text-align: center;
}

.navbar .right {
    display: flex;
    justify-content: flex-end;
}

/* Responsif */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }

    th, td {
        font-size: 12px;
    }

    .btn {
        font-size: 12px;
        padding: 8px 10px;
    }

  
}
/* Tata Letak Form Pencarian */
form {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap; /* Form akan menyesuaikan jika tidak cukup ruang */
}

form input[type="text"] {
    flex-grow: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    min-width: 200px; /* Batas minimum untuk input */
}

form button,
form a {
    padding: 10px 20px;
    font-size: 1rem;
    text-align: center;
    text-decoration: none;
    border-radius: 5px;
    color: white;
    cursor: pointer;
    white-space: nowrap; /* Mencegah tombol terpotong */
}

form button {
    background-color: #4CAF50;
    border: none;
}

form a {
    background-color: #f44336;
}

form button:hover {
    background-color: #45a049;
}

form a:hover {
    background-color: #e53935;
}

/* Responsif untuk layar kecil */
@media (max-width: 768px) {
    form {
        flex-direction: column; /* Tombol dan input ditampilkan vertikal */
        align-items: stretch; /* Tombol memenuhi lebar form */
    }

    form input[type="text"] {
        width: 100%; /* Input memenuhi lebar */
    }

    form button,
    form a {
        width: 100%; /* Tombol memenuhi lebar */
    }
}
.pagination {
    margin: 20px 0;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
}

.pagination a {
    padding: 10px 15px;
    text-decoration: none;
    border: 1px solid #ddd;
    border-radius: 5px;
    color: #4CAF50;
    background-color: #f8f9fa;
    transition: background-color 0.3s;
}

.pagination a:hover {
    background-color: #4CAF50;
    color: white;
}

.pagination a.active {
    font-weight: bold;
    background-color: #4CAF50;
    color: white;
    border-color: #4CAF50;
}
</style>
</head>
<body>


<!-- Navbar -->
<div class="navbar">
    <div class="brand">MyExam</div>
    <div class="menu">
        <a href="index.php">Dashboard</a>
        <a href="../logout.php">Log Out</a>
    </div>
</div>

<div class="container">
<div class="centered-text">
    <h1>Kelola Pengguna</h1>
</div>
    
    <!-- Tombol Tambah Pengguna -->
<button class="btn" onclick="openModal()">Tambah Pengguna Baru</button>


<!-- Form Pencarian -->
<form 
    method="GET" 
    action="kelola_user.php" 
    style="
        display: flex; 
        flex-wrap: wrap; 
        align-items: center; 
        gap: 10px; 
        margin-bottom: 20px;"
>
    <input 
        type="text" 
        name="search" 
        placeholder="Cari pengguna..." 
        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
        style="
            flex-grow: 1; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 1rem;"
    >
    <button 
        type="submit" 
        class="btn" 
        style="
            padding: 10px 20px; 
            font-size: 1rem;"
    >
        Cari
    </button>
    <a 
        href="kelola_user.php" 
        class="btn" 
        style="
            padding: 10px 20px; 
            font-size: 1rem; 
            background-color: #f44336; 
            text-align: center;"
    >
        Reset
    </a>
</form>

    <!-- Tabel Pengguna -->
    <table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result_users->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['username']; ?></td>
                <td><?php echo ucfirst($row['role']); ?></td>
                
                <td><a href="# " class="btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">Hapus</a></td>
                
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>


<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>">&#171; Previous</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a 
            href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>" 
            class="<?php echo $i == $page ? 'active' : ''; ?>"
        >
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>">Next &#187;</a>
    <?php endif; ?>
</div>

<div id="userModal" class="modal" >
    <div class="modal-content">
        <h2>Tambah Pengguna Baru</h2>
        <!-- Pesan error atau sukses -->
        <?php if (isset($error_message)): ?>
        <?php endif; ?>
        <?php if (isset($success_message)): ?>
        <?php endif; ?>
        <form method="POST" action="kelola_user.php">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>

            <label for="role">Role:</label>
            <select name="role" id="role">
                <option value="user" <?php echo (isset($_POST['role']) && $_POST['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
            </select>

            <input type="submit" name="add_user" value="Tambah Pengguna">
        </form>
        <button class="close-btn" onclick="closeModal()">Tutup</button>
    </div>
</div>

<?php
// Menutup koneksi
$conn->close();
?>

</body>
</html>