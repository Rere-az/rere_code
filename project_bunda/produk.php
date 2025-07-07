<?php
// Tampilkan error
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Koneksi
$conn = new mysqli("localhost", "root", "", "parfum2");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Data dropdown parfum dari tabel produk_parfum (mengambil nama parfum dan volume)
$parfum_result = $conn->query("
    SELECT pp.kdParfum, pp.nmParfum, jv.nmVolumeParfum 
    FROM produk_parfum pp
    JOIN jenis_volume jv ON pp.kdVolumeParfum = jv.kdVolumeParfum
");

// Tambah stok
if (isset($_POST['submit_stok'])) {
    $kdParfum = $_POST['kdParfum'];
    $tgl_stok = $_POST['tgl_stok'];
    $jmlh_stok = $_POST['jmlh_stok'];

    $stmt = $conn->prepare("INSERT INTO tbl_tambahstok (kdParfum, tgl_stok, jmlh_stok) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $kdParfum, $tgl_stok, $jmlh_stok);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Total data
$total_result = $conn->query("SELECT COUNT(*) as total FROM tbl_tambahstok");
$total_data = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data stok gabung ke produk_parfum dan jenis_volume
$stok_result = $conn->query("
    SELECT ts.kdParfum, pp.nmParfum, jv.nmVolumeParfum, ts.tgl_stok, ts.jmlh_stok
    FROM tbl_tambahstok ts
    JOIN produk_parfum pp ON ts.kdParfum = pp.kdParfum
    JOIN jenis_volume jv ON pp.kdVolumeParfum = jv.kdVolumeParfum
    ORDER BY ts.tgl_stok DESC
    LIMIT $start, $limit
");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require "navbar1.php";
    ?>
    <meta charset="UTF-8">
    <title>Data Tambah Stok Parfum</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap & Tailwind -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 text-gray-800 p-6">
    <div class="container">
        <h1 class="text-3xl font-bold mb-6 text-center">📦 Data Tambah Stok Parfum</h1>

        <!-- Tombol Tambah -->
        <div class="mb-3 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formStokModal">+ Tambah</button>
        </div>

        <!-- Modal Form -->
        <div class="modal fade" id="formStokModal" tabindex="-1" aria-labelledby="formStokModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="post">
                        <div class="modal-header">
                            <h5 class="modal-title" id="formStokModalLabel">Tambah Data Stok Parfum</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pilih Parfum</label>
                                <select name="kdParfum" class="form-select" required>
                                    <option value="">Pilih Parfum</option>
                                    <?php
                                    $parfum_result->data_seek(0);
                                    while ($p = $parfum_result->fetch_assoc()):
                                    ?>
                                        <option value="<?= $p['kdParfum']; ?>">
                                            <?= $p['nmParfum'] . " - " . $p['nmVolumeParfum']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Stok</label>
                                <input type="date" name="tgl_stok" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Stok</label>
                                <input type="number" name="jmlh_stok" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="submit_stok" class="btn btn-success">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="card shadow mt-5">
            <div class="card-body">
                <table class="table table-striped table-bordered text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Nama Parfum</th>
                            <th>Kode Parfum</th>
                            <th>Volume</th>
                            <th>Tanggal</th>
                            <th>Jumlah Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stok_result->num_rows > 0): ?>
                            <?php while ($row = $stok_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['nmParfum']; ?></td>
                                    <td><?= $row['kdParfum']; ?></td>
                                    <td><?= $row['nmVolumeParfum']; ?></td>
                                    <td><?= $row['tgl_stok']; ?></td>
                                    <td><?= $row['jmlh_stok']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Data stok belum tersedia.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Navigasi Pagination -->
        <nav aria-label="Navigasi halaman stok" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1; ?>">Sebelumnya</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1; ?>">Berikutnya</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>