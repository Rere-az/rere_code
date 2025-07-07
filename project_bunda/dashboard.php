<?php
include 'koneksi.php';
$bulanDipilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahunSekarang = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<?php require "navbar.php"; ?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard KPI Penjualan Parfum</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/wordcloud@1.2.2/src/wordcloud2.min.js"></script>
</head>

<body class="bg-gray-100 text-gray-800 p-6">
  <div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold mb-6 text-center">📊 Dashboard KPI Penjualan Parfum</h1>

    <!-- Dropdown Filter Bulan -->
    <form method="get" class="mb-6 text-left">
      <label for="bulan" class="mr-2 text-lg font-medium">Pilih Bulan:</label>
      <select name="bulan" id="bulan" class="border rounded px-3 py-2" onchange="this.form.submit()">
        <?php
        for ($i = 1; $i <= 12; $i++) {
          $val = str_pad($i, 2, '0', STR_PAD_LEFT);
          $namaBulan = date('F', mktime(0, 0, 0, $i, 10));
          $selected = $val == $bulanDipilih ? 'selected' : '';
          echo "<option value='$val' $selected>$namaBulan</option>";
        }
        ?>
      </select>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <!-- Word Cloud -->
      <div class="bg-white rounded-2xl shadow p-4">
        <h2 class="text-xl font-semibold mb-4">Daerah dengan Transaksi Parfum Terbanyak</h2>
        <canvas id="regionPieChart"></canvas>
      </div>

      <!-- Line & Bar Charts -->
      <div class="bg-white rounded-2xl shadow p-4">
        <h2 class="text-xl font-semibold mb-4">Tren Penjualan Bulanan</h2>
        <canvas id="lineChart"></canvas>
        <h2 class="text-xl font-semibold mb-4 mt-4">Produk Terlaris Bulan Ini</h2>
        <canvas id="barChart"></canvas>
      </div>

    </div>
    <div class="bg-white rounded-2xl shadow p-4">
      <h2 class="text-xl font-semibold mb-4">Kontribusi Penjualan per Parfum</h2>
      <div id="wordCloud" style="width: 100%; height: 550px;"></div>
    </div>

    <div class="bg-white rounded-2xl shadow p-4 my-6">
      <h2 class="text-xl font-semibold mb-4">Pelanggan dengan Transaksi Terbanyak</h2>
      <canvas id="topCustomersChart" height="150"></canvas>
    </div>

    <!-- KPI Meter -->
    <div class="bg-white rounded-2xl shadow p-4 mb-6">
      <h2 class="text-xl font-semibold mb-4">Total Penjualan Bulan Ini vs Target</h2>
      <?php
      $target = 3000000;
      $kpiSQL = "SELECT SUM(jv.harga * t.Qty) AS total FROM trans_parfum t
                 JOIN produk_parfum p ON t.kdParfum = p.kdParfum
                 JOIN jenis_volume jv ON p.kdVolumeParfum = jv.kdVolumeParfum
                 WHERE MONTH(t.tgl_Trans) = '$bulanDipilih' AND YEAR(t.tgl_Trans) = '$tahunSekarang'";
      $kpi = $conn->query($kpiSQL)->fetch_assoc();
      $actual = $kpi['total'] ?? 0;
      $percent = round(($actual / $target) * 100);
      $remainingPercent = max(0, 100 - $percent);
      ?>
      <div class="w-full bg-gray-200 rounded-full h-8 overflow-hidden flex text-sm font-semibold">
        <div class="h-full bg-green-500 text-white text-center" style="width:<?= $percent ?>%">
          <?= $percent ?>%
        </div>
        <div class="h-full bg-red-500 text-white text-center" style="width:<?= $remainingPercent ?>%">
          <?= $remainingPercent ?>%
        </div>
      </div>
      <p class="mt-2 text-sm text-gray-600">
        Target Bulanan: Rp <?= number_format($target) ?> |
        Tercapai: Rp <?= number_format($actual) ?> |
        Sisa: <span class="text-red-500 font-bold">Rp <?= number_format($target - $actual) ?></span>
      </p>
    </div>
  </div>
  <?php
  $topCustomersSQL = "SELECT plg.nama_plg, COUNT(*) AS jumlah_transaksi
                    FROM trans_parfum t
                    JOIN tbl_pelanggan plg ON t.id_Plg = plg.id_Plg
                    WHERE MONTH(t.tgl_Trans) = '$bulanDipilih' AND YEAR(t.tgl_Trans) = '$tahunSekarang'
                    GROUP BY plg.nama_plg
                    ORDER BY jumlah_transaksi DESC
                    LIMIT 10";
  $topRes = $conn->query($topCustomersSQL);
  ?>

  <script>
    const wordCloudData = [];
    <?php
    $wordSQL = "SELECT p.nmParfum, SUM(t.Qty * jv.harga) AS total 
                FROM trans_parfum t
                JOIN produk_parfum p ON t.kdParfum = p.kdParfum
                JOIN jenis_volume jv ON p.kdVolumeParfum = jv.kdVolumeParfum
                WHERE MONTH(t.tgl_Trans) = '$bulanDipilih' AND YEAR(t.tgl_Trans) = '$tahunSekarang'
                GROUP BY p.nmParfum";
    $res = $conn->query($wordSQL);
    while ($row = $res->fetch_assoc()) {
      $name = addslashes($row['nmParfum']);
      echo "wordCloudData.push(['{$name}', {$row['total']}]);\n";
    }
    ?>
    WordCloud(document.getElementById('wordCloud'), {
      list: wordCloudData,
      weightFactor: function(size) {
        return size * 0.005;
      },
      fontFamily: 'Arial',
      color: 'random-dark',
      backgroundColor: '#ffffff'
    });

    const lineLabels = [];
    const penjualanData = [];
    const transaksiData = [];
    <?php
    // Query total penjualan per bulan
    $queryPenjualan = $conn->query("
      SELECT DATE_FORMAT(t.tgl_Trans, '%Y-%m') AS bulan, 
             SUM(jv.harga * t.Qty) AS total_penjualan
      FROM trans_parfum t
      JOIN produk_parfum p ON t.kdParfum = p.kdParfum
      JOIN jenis_volume jv ON p.kdVolumeParfum = jv.kdVolumeParfum
      GROUP BY bulan ORDER BY bulan
    ");

    $dataPenjualan = [];
    while ($row = $queryPenjualan->fetch_assoc()) {
      $dataPenjualan[$row['bulan']] = $row['total_penjualan'];
    }

    // Query jumlah transaksi per bulan
    $queryTransaksi = $conn->query("
      SELECT DATE_FORMAT(tgl_Trans, '%Y-%m') AS bulan, COUNT(*) AS jumlah_transaksi
      FROM trans_parfum
      GROUP BY bulan ORDER BY bulan
    ");

    $dataTransaksi = [];
    while ($row = $queryTransaksi->fetch_assoc()) {
      $dataTransaksi[$row['bulan']] = $row['jumlah_transaksi'];
    }

    // Gabungkan semua bulan yang muncul di kedua dataset
    $allMonths = array_unique(array_merge(array_keys($dataPenjualan), array_keys($dataTransaksi)));
    sort($allMonths);

    foreach ($allMonths as $bulan) {
      echo "lineLabels.push('{$bulan}');\n";
      echo "penjualanData.push(" . ($dataPenjualan[$bulan] ?? 0) . ");\n";
      echo "transaksiData.push(" . ($dataTransaksi[$bulan] ?? 0) . ");\n";
    }
    ?>

    new Chart(document.getElementById("lineChart"), {
      type: 'line',
      data: {
        labels: lineLabels, // tetap gunakan data dari PHP
        datasets: [{
            label: 'Total Penjualan (Rp)',
            data: penjualanData,
            borderColor: 'blue',
            backgroundColor: 'blue',
            pointBackgroundColor: 'blue',
            pointBorderColor: 'blue',
            pointStyle: 'circle',
            pointRadius: 5,
            borderWidth: 2,
            tension: 0.3,
            yAxisID: 'yRp'
          },
          {
            label: 'Jumlah Transaksi',
            data: transaksiData,
            borderColor: 'red',
            backgroundColor: 'red',
            pointBackgroundColor: 'red',
            pointBorderColor: 'red',
            pointStyle: 'rectRot',
            pointRadius: 5,
            borderWidth: 2,
            tension: 0.3,
            yAxisID: 'yTransaksi'
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          title: {
            display: true,
            text: 'Tren Penjualan Bulanan',
            align: 'center',
            font: {
              size: 18,
              weight: 'bold'
            }
          },
          legend: {
            position: 'top'
          },
          tooltip: {
            mode: 'index',
            intersect: false
          }
        },
        interaction: {
          mode: 'index',
          intersect: false
        },
        scales: {
          x: {
            title: {
              display: true,
              text: 'Bulan',
              font: {
                style: 'italic'
              }
            }
          },
          yRp: {
            id: 'yRp',
            type: 'linear',
            position: 'left',
            title: {
              display: true,
              text: 'Total Penjualan (Rp)',
              font: {
                style: 'italic'
              }
            },
            ticks: {
              callback: function(value) {
                return 'Rp ' + value.toLocaleString();
              }
            }
          },
          yTransaksi: {
            id: 'yTransaksi',
            type: 'linear',
            position: 'right',
            grid: {
              drawOnChartArea: false
            },
            title: {
              display: true,
              text: 'Jumlah Transaksi',
              font: {
                style: 'italic'
              }
            }
          }
        }
      }
    });

    const barLabels = [],
      barData = [];
    <?php
    $barsql = "SELECT p.nmParfum, SUM(t.Qty) AS total_terjual
               FROM trans_parfum t
               JOIN produk_parfum p ON t.kdParfum = p.kdParfum
               WHERE MONTH(t.tgl_Trans) = '$bulanDipilih' AND YEAR(t.tgl_Trans) = '$tahunSekarang'
               GROUP BY p.nmParfum
               ORDER BY total_terjual DESC LIMIT 10";
    $res = $conn->query($barsql);
    while ($row = $res->fetch_assoc()) {
      echo "barLabels.push('{$row['nmParfum']}');\n";
      echo "barData.push({$row['total_terjual']});\n";
    }
    ?>
    new Chart(document.getElementById("barChart"), {
      type: 'bar',
      data: {
        labels: barLabels,
        datasets: [{
          label: 'Jumlah Terjual',
          data: barData,
          backgroundColor: '#10b981'
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    const regionLabels = [],
      regionData = [];
    <?php
    $regionSQL = "SELECT plg.alamat, COUNT(*) AS total_transaksi
                  FROM trans_parfum t
                  JOIN tbl_pelanggan plg ON t.id_Plg = plg.id_Plg
                  WHERE MONTH(t.tgl_Trans) = '$bulanDipilih' AND YEAR(t.tgl_Trans) = '$tahunSekarang'
                  GROUP BY plg.alamat";
    $resRegion = $conn->query($regionSQL);
    while ($row = $resRegion->fetch_assoc()) {
      echo "regionLabels.push('{$row['alamat']}');\n";
      echo "regionData.push({$row['total_transaksi']});\n";
    }
    ?>
    new Chart(document.getElementById("regionPieChart"), {
      type: 'pie',
      data: {
        labels: regionLabels,
        datasets: [{
          data: regionData,
          backgroundColor: [
            '#f87171', '#60a5fa', '#34d399', '#fbbf24',
            '#a78bfa', '#f472b6', '#38bdf8', '#4ade80',
            '#facc15', '#818cf8'
          ]
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'right'
          }
        }
      }
    });
    const topCustomerLabels = [];
    const topCustomerData = [];
    <?php
    while ($row = $topRes->fetch_assoc()) {
      echo "topCustomerLabels.push('{$row['nama_plg']}');\n";
      echo "topCustomerData.push({$row['jumlah_transaksi']});\n";
    }
    ?>
    new Chart(document.getElementById("topCustomersChart"), {
      type: 'bar',
      data: {
        labels: topCustomerLabels,
        datasets: [{
          label: 'Jumlah Transaksi',
          data: topCustomerData,
          backgroundColor: '#6366f1'
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>
</body>

</html>