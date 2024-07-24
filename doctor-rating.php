<?php
session_start();
include('db_connection.php'); 

// Check if the user is logged in as a doctor
if (!isset($_SESSION['login_doctor'])) {
    header("location: pages-login-doctor.php");
    exit;
}

// Get the logged-in doctor's ID
$id_dokter = $_SESSION['login_iddoc'];

// SQL Queries to get counts
$sql_medicine = "SELECT COUNT(*) AS total_medicine FROM obat";
$result_medicine = mysqli_query($conn, $sql_medicine);
$row_medicine = mysqli_fetch_assoc($result_medicine);
$total_medicine = $row_medicine['total_medicine'];

$sql_pharmachy = "SELECT COUNT(*) AS total_pharmachy FROM farmasi";
$result_pharmachy = mysqli_query($conn, $sql_pharmachy);
$row_pharmachy = mysqli_fetch_assoc($result_pharmachy);
$total_pharmachy = $row_pharmachy['total_pharmachy'];

$sql_doctor = "SELECT COUNT(*) AS total_doctor FROM dokter";
$result_doctor = mysqli_query($conn, $sql_doctor);
$row_doctor = mysqli_fetch_assoc($result_doctor);
$total_doctor = $row_doctor['total_doctor'];

$sql_patient = "SELECT COUNT(*) AS total_patient FROM pasien";
$result_patient = mysqli_query($conn, $sql_patient);
$row_patient = mysqli_fetch_assoc($result_patient);
$total_patient = $row_patient['total_patient'];

// Calculate average rating
$stmt_avg_rating = $conn->prepare("SELECT AVG(r.rate_dokter) as avg_rating 
                                   FROM rating r
                                   JOIN rekam_medis rm ON r.id_rekam_medis = rm.id_rekam_medis
                                   JOIN antrian a ON rm.id_antrian = a.id_antrian
                                   JOIN jadwal_dokter jd ON a.id_jadwal = jd.id_jadwal
                                   JOIN dokter d ON jd.id_dokter = d.id_dokter
                                   WHERE d.id_dokter = ?");
                                   
// Bind the parameter (assuming id_dokter is an integer)
$stmt_avg_rating->bind_param("i", $id_dokter);

// Execute the query
$stmt_avg_rating->execute();

// Fetch the result
$result_avg_rating = $stmt_avg_rating->get_result();
$row_avg_rating = $result_avg_rating->fetch_assoc();
$avg_rating = round($row_avg_rating['avg_rating'], 1);

// Close the statement and connection
$stmt_avg_rating->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Dashboard Dokter</title>
  <meta content="" name="description">
  <meta content="" name="keywords">
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="index.html" class="logo d-flex align-items-center">
        <img src="assets/img/logo.png" alt="">
        <span class="d-none d-lg-block">Pratama</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->


    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">
        <li class="nav-item dropdown pe-3">

          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <!-- <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle"> -->
            <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $_SESSION['login_doctor']; ?></span>
          </a><!-- End Profile Iamge Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
            <h6><?php echo $_SESSION['login_doctor']; ?></h6>
              <span>Admin</span>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="logout-doctor.php">
                  <i class="bi bi-box-arrow-right"></i>
                  <span>Sign Out</span>
              </a>
            </li>
          
          </ul><!-- End Profile Dropdown Items -->
        </li><!-- End Profile Nav -->

      </ul>
    </nav><!-- End Icons Navigation -->

  </header><!-- End Header -->

  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item">
        <a class="nav-link collapsed" href="doctor-dashboard.php">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="doctor-praktik.php">
          <i class="bi bi-person"></i>
          <span>Praktik</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="doctor-patient.php">
          <i class="bi bi-person"></i>
          <span>Pasien</span>
        </a>
      </li>
      
      <li class="nav-item">
        <a class="nav-link collapsed" href="doctor-history.php">
          <i class="bi bi-journal-text"></i>
          <span>Riwayat Praktik</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link " href="doctor-rating.php">
          <i class="bi bi-bar-chart"></i>
          <span>Rating</span>
        </a>
      </li>
    </ul>
  </aside>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Rating</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.html">Beranda</a></li>
          <li class="breadcrumb-item active">Rating</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="row">

      <section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Daftar Rating & Ulasan Dokter</h5>

                    <div class="average-rating">
                    <h1><?php echo $avg_rating; ?></h1>
                    <p>
                      <?php
                      for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $avg_rating) {
                          echo '<i class="bi bi-star-fill text-warning"></i>';
                        } elseif ($i - 0.5 <= $avg_rating) {
                          echo '<i class="bi bi-star-half text-warning"></i>';
                        } else {
                          echo '<i class="bi bi-star-fill text-secondary"></i>';
                        }
                      }
                      ?>
                    </p>
                  </div>

                  <table class="table table-bordered">
    <thead>
        <tr>
            <th scope="col">Rekam Medis</th>
            <th scope="col">Rating</th>
            <th scope="col">Ulasan</th>
        </tr>
    </thead>
    <tbody>
        <?php
        include('db_connection.php');

        // Check if the user is logged in as a doctor
        if (!isset($_SESSION['login_doctor'])) {
            header("location: pages-login-doctor.php");
            exit;
        }

        // Get the logged-in doctor's ID
        $id_dokter = $_SESSION['login_iddoc'];

        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT r.id_rating, r.id_rekam_medis, r.rate_dokter, r.ulasan 
                                FROM rating r
                                JOIN rekam_medis rm ON r.id_rekam_medis = rm.id_rekam_medis
                                JOIN antrian a ON rm.id_antrian = a.id_antrian
                                JOIN jadwal_dokter jd ON a.id_jadwal = jd.id_jadwal
                                JOIN dokter d ON jd.id_dokter = d.id_dokter
                                WHERE d.id_dokter = ?");
                                
        // Bind the parameter (assuming id_dokter is an integer)
        $stmt->bind_param("i", $id_dokter);

        // Execute the statement
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($rating = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $rating['id_rekam_medis'] . "</td>";
                echo "<td>" . $rating['rate_dokter'] . " Bintang</td>";
                echo "<td>" . $rating['ulasan'] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3' class='text-center'>Tidak ada data rating</td></tr>";
        }

        // Close the statement and connection
        $stmt->close();
        $conn->close();
        ?>
    </tbody>
</table>

                </div>
            </div>
        </div>
    </div>
</section>


      </div>
    </section>

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->

  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>Klinik Pratama Anugrah Hexa</span></strong>. All Rights Reserved
    </div>
    <div class="credits">
      Designed by <a href="#">Artadevnymous</a>
    </div>
  </footer>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>