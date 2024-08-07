<?php
session_start();
include('db_connection.php');

$status_obat = '';
$message = '';

if (isset($_GET['id_rekam_medis'])) {
    $id = $_GET['id_rekam_medis'];

    $sql_rekam_medis = "SELECT rm.*, p.nama_pasien, p.email_pasien, p.nomorhp_pasien, dp.jenis_kelamin, dp.tanggal_lahir, dp.alamat_pasien
                        FROM rekam_medis rm
                        INNER JOIN antrian a ON rm.id_antrian = a.id_antrian
                        INNER JOIN pasien p ON a.id_pasien = p.id_pasien
                        INNER JOIN detail_pasien dp ON p.id_pasien = dp.id_pasien
                        WHERE rm.id_rekam_medis = ?";
    $stmt_rekam_medis = $conn->prepare($sql_rekam_medis);
    $stmt_rekam_medis->bind_param("i", $id);
    $stmt_rekam_medis->execute();
    $result_rekam_medis = $stmt_rekam_medis->get_result();
    $rekam_medis = $result_rekam_medis->fetch_assoc();

    if (!$rekam_medis) {
        header("Location: patient-checkup.php");
        exit;
    }

    $sql_resep = "SELECT ro.id_resep, ro.id_obat, o.nama_obat, ro.status, ro.aturan, ro.keterangan, o.jenis_obat
                  FROM resep_obat ro 
                  INNER JOIN obat o ON ro.id_obat = o.id_obat 
                  WHERE ro.id_rekammedis = ?";
    $stmt_resep = $conn->prepare($sql_resep);
    $stmt_resep->bind_param("i", $id);
    $stmt_resep->execute();
    $result_resep = $stmt_resep->get_result();
    $resep_obat = [];
    while ($row_resep = $result_resep->fetch_assoc()) {
        $resep_obat[] = $row_resep;
    }

    $tanggal_lahir = new DateTime($rekam_medis['tanggal_lahir']);
    $today = new DateTime();
    $umur = $tanggal_lahir->diff($today);
    $umur_string = $umur->format('%y tahun %m bulan %d hari');

    if (!empty($resep_obat)) {
        $status_obat = $resep_obat[0]['status'];
    }
} else {
    header("Location: patient-checkup.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = $_POST['rating'];
    $ulasan = $_POST['ulasan'];
    $id_rekam_medis = $_POST['id_rekam_medis'];
    $role = $_POST['role'];

    $sql_insert_rating = "";
    if ($role == 'admin') {
        $sql_insert_rating = "INSERT INTO rating (id_rekam_medis, rate_admin, ulasan) VALUES (?, ?, ?)";
    } elseif ($role == 'dokter') {
        $sql_insert_rating = "INSERT INTO rating (id_rekam_medis, rate_dokter, ulasan) VALUES (?, ?, ?)";
    } elseif ($role == 'farmasi') {
        $sql_insert_rating = "INSERT INTO rating (id_rekam_medis, rate_farmasi, ulasan) VALUES (?, ?, ?)";
    }

    $stmt_insert_rating = $conn->prepare($sql_insert_rating);
    $stmt_insert_rating->bind_param("iis", $id_rekam_medis, $rating, $ulasan);

    if ($stmt_insert_rating->execute()) {
        $message = "Rating and review submitted successfully.";
    } else {
        $message = "Failed to submit rating and review.";
    }
}

$sql_rating = "SELECT * FROM rating WHERE id_rekam_medis = ?";
$stmt_rating = $conn->prepare($sql_rating);
$stmt_rating->bind_param("i", $id);
$stmt_rating->execute();
$result_rating = $stmt_rating->get_result();
$ratings = [];
while ($row_rating = $result_rating->fetch_assoc()) {
    $ratings[] = $row_rating;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pemeriksaan</title>
    <style>
        /* CSS Anda di sini */
        .rating {
            display: flex;
            flex-direction: row; /* Urutan bintang dari kiri ke kanan */
        }

        .rating .star {
            display: inline-block;
            font-size: 24px;
            cursor: pointer;
            color: #ccc;
        }

        .rating .star:hover,
        .rating .star.active,
        .rating .star:hover ~ .star {
            color: #FFD700; /* warna bintang saat dihover dan yang dipilih */
        }
    </style>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4>Detail Pemeriksaan</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info" role="alert">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <form action="" method="POST" id="processForm">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="inputNamaPasien" class="form-label">Nama Pasien</label>
                            <input type="text" class="form-control" id="inputNamaPasien" name="nama_pasien" value="<?php echo htmlspecialchars($rekam_medis['nama_pasien']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="inputEmailPasien" class="form-label">Email Pasien</label>
                            <input type="email" class="form-control" id="inputEmailPasien" name="email_pasien" value="<?php echo htmlspecialchars($rekam_medis['email_pasien']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="inputNomorHpPasien" class="form-label">Nomor HP Pasien</label>
                            <input type="text" class="form-control" id="inputNomorHpPasien" name="nomorhp_pasien" value="<?php echo htmlspecialchars($rekam_medis['nomorhp_pasien']); ?>" readonly>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label for="inputJenisKelamin" class="form-label">Jenis Kelamin</label>
                            <input type="text" class="form-control" id="inputJenisKelamin" name="jenis_kelamin" value="<?php echo htmlspecialchars($rekam_medis['jenis_kelamin']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="inputTanggalLahir" class="form-label">Tanggal Lahir</label>
                            <input type="text" class="form-control" id="inputTanggalLahir" name="tanggal_lahir" value="<?php echo htmlspecialchars($rekam_medis['tanggal_lahir']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="inputUmur" class="form-label">Umur</label>
                            <input type="text" class="form-control" id="inputUmur" name="umur" value="<?php echo htmlspecialchars($umur_string); ?>" readonly>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="inputAlamatPasien" class="form-label">Alamat Pasien</label>
                            <textarea class="form-control" id="inputAlamatPasien" name="alamat_pasien" rows="3" readonly><?php echo htmlspecialchars($rekam_medis['alamat_pasien']); ?></textarea>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="inputResep" class="form-label"><strong>Resep Obat</strong></label>
                            <?php if (!empty($resep_obat)): ?>
                                <ol type="1" class="list-unstyled">
                                    <?php foreach ($resep_obat as $obat): ?>
                                        <li>
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label for="inputObat_<?php echo $obat['id_resep']; ?>" class="form-label">Nama Obat</label>
                                                    <input type="text" class="form-control" id="inputObat_<?php echo $obat['id_resep']; ?>" name="nama_obat_<?php echo $obat['id_resep']; ?>" value="<?php echo htmlspecialchars($obat['nama_obat']); ?>" readonly>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="inputJenisObat_<?php echo $obat['id_resep']; ?>" class="form-label">Jenis Obat</label>
                                                    <input type="text" class="form-control" id="inputJenisObat_<?php echo $obat['id_resep']; ?>" name="jenis_obat_<?php echo $obat['id_resep']; ?>" value="<?php echo htmlspecialchars($obat['jenis_obat']); ?>" readonly>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="inputAturan_<?php echo $obat['id_resep']; ?>" class="form-label">Aturan</label>
                                                    <input type="text" class="form-control" id="inputAturan_<?php echo $obat['id_resep']; ?>" name="aturan_<?php echo $obat['id_resep']; ?>" value="<?php echo htmlspecialchars($obat['aturan']); ?>" readonly>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="inputKeterangan_<?php echo $obat['id_resep']; ?>" class="form-label">Keterangan</label>
                                                    <input type="text" class="form-control" id="inputKeterangan_<?php echo $obat['id_resep']; ?>" name="keterangan_<?php echo $obat['id_resep']; ?>" value="<?php echo htmlspecialchars($obat['keterangan']); ?>" readonly>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php else: ?>
                                <p>Tidak ada resep obat.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <a href="patient-checkup.php" class="btn btn-primary">
                                Kembali
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card mt-5">
                        <div class="card-header">
                            <h5>Rating and Ulasan</h5>
                        </div>
                        <div class="card-body">
                        <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="rating" class="form-label"><strong>Beri Rating</strong></label>
                            <div class="rating">
                                <span class="star" data-value="1">&#9733;</span>
                                <span class="star" data-value="2">&#9733;</span>
                                <span class="star" data-value="3">&#9733;</span>
                                <span class="star" data-value="4">&#9733;</span>
                                <span class="star" data-value="5">&#9733;</span>
                                <input type="hidden" name="rating" id="rating" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="ulasan" class="form-label">Ulasan</label>
                            <textarea class="form-control" id="ulasan" name="ulasan" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label><strong>Role:</strong></label><br>
                            <input type="checkbox" name="role" value="admin"> Admin<br>
                            <input type="checkbox" name="role" value="dokter"> Dokter<br>
                            <input type="checkbox" name="role" value="farmasi"> Farmasi<br>
                        </div>
                    </div>

                    <input type="hidden" name="id_rekam_medis" value="<?php echo $rekam_medis['id_rekam_medis']; ?>">

                    <button type="submit" class="btn btn-primary mt-3">Submit</button>
                            </form>
                        </div>
                    </div>

                    <?php if (!empty($ratings)): ?>
    <div class="row mt-5">
        <div class="col-md-12">
            <h5>Rating dan Ulasan</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Rate Admin</th>
                        <th>Rate Dokter</th>
                        <th>Rate Farmasi</th>

                        <th>Ulasan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ratings as $rating): ?>
                        <tr>
                            <td><?php echo $rating['rate_admin']; ?> Bintang</td>
                            <td><?php echo $rating['rate_dokter']; ?> Bintang</td>

                            <td><?php echo $rating['rate_farmasi']; ?> Bintang</td>

                            <td><?php echo $rating['ulasan']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>


                </tbody>
            </table>
        </div>
                    </div>
    </div>
</body>
<script>
        document.addEventListener("DOMContentLoaded", function() {
            const stars = document.querySelectorAll(".star");

            stars.forEach(star => {
                star.addEventListener("click", function() {
                    const value = this.getAttribute("data-value");
                    document.getElementById("rating").value = value;

                    // Tambahkan class 'active' pada bintang yang dipilih dan bintang sebelumnya
                    stars.forEach(s => {
                        if (parseInt(s.getAttribute("data-value")) <= parseInt(value)) {
                            s.classList.add("active");
                        } else {
                            s.classList.remove("active");
                        }
                    });
                });
            });
        });
    </script>
</html>
