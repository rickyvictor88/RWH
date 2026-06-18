<?php
/**
 * ==========================================
 * INDEX: DASHBOARD MONITORING UTAMA
 * ==========================================
 * Halaman utama dashboard yang menampilkan data secara real-time.
 * Menggunakan Chart.js untuk grafik dan JavaScript Fetch untuk polling data tanpa reload.
 */

// Panggil file konfigurasi database
require_once __DIR__ . '/api/config.php';

// Inisialisasi variabel status database
$db_connected = isset($pdo) && !isset($db_connection_error);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rainwater Harvesting - Sistem Monitoring Kualitas Air Real-Time</title>
  <meta name="description" content="Dashboard monitoring kualitas air real-time untuk sistem pemanenan air hujan (Rainwater Harvesting) berbasis IoT Raspberry Pi dan database MySQL.">
  
  <!-- Custom CSS Premium -->
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime('css/style.css') ?>">
  
  <!-- Font Awesome untuk Icon (Aman & Ringan) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Chart.js dari CDN Resmi -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <style>
    .nav-menu { display: inline-flex; background-color: #1a1d23; padding: 8px; border-radius: 12px; gap: 8px; }
    .nav-item { padding: 10px 20px; text-decoration: none; color: #a0a0a0; border-radius: 8px; transition: all 0.3s ease; }
    .nav-item.active { background-color: #1e3a3a; color: #ffffff; border: 1px solid #2d6a6a; }
  </style>
</head>
<body>

  <div class="app-container">
    
    <!-- HEADER -->
    <header>
      <div class="brand-container">
        <h1><i class="fa-solid fa-droplet"></i> Rainwater Harvesting</h1>
        <p>Sistem Monitoring Kualitas Air Hujan Real-Time</p>
      </div>
      
      <div class="system-status" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <div id="connection-indicator" class="live-indicator">
          <div id="status-dot" class="pulse-dot <?= $db_connected ? '' : 'disconnected' ?>"></div>
          <span id="status-text"><?= $db_connected ? 'Sistem Aktif' : 'Database Terputus' ?></span>
        </div>

        <!-- Wadah Logo (Logo 1 & Logo 2) -->
        <div class="logo-container" title="Wadah Logo Instansi / Sponsor">
          <div class="logo-placeholder" title="Logo 1">
            <img src="FOTO%20PROFIL/logo%20ocl.png" alt="Logo OCL" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <span class="logo-icon-fallback" style="display: none;"><i class="fa-solid fa-image"></i></span>
          </div>
          <div class="logo-placeholder" title="Logo 2">
            <img src="FOTO%20PROFIL/logo%20telkom.png" alt="Logo Telkom" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <span class="logo-icon-fallback" style="display: none;"><i class="fa-solid fa-image"></i></span>
          </div>
        </div>
        
        <nav class="nav-menu" style="margin: 0; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border);">
          <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
          <a href="index.php" class="nav-item <?= ($current_page == 'index.php') ? 'active' : '' ?>" style="padding: 6px 14px; font-size: 0.85rem;"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
          <a href="about.php" class="nav-item <?= ($current_page == 'about.php') ? 'active' : '' ?>" style="padding: 6px 14px; font-size: 0.85rem;"><i class="fa-solid fa-circle-info"></i> About</a>
        </nav>
      </div>
    </header>

    <!-- MAIN DASHBOARD CONTENT -->
    <main>
      
      <?php if (!$db_connected): ?>
        <!-- BANNER PETUNJUK SETUP DATABASE LARAGON -->
        <section class="error-banner glass-card" id="db-error-section">
          <h3><i class="fa-solid fa-triangle-exclamation"></i> KONEKSI DATABASE MYSQL GAGAL</h3>
          <p>Sistem tidak dapat terhubung ke database MySQL. Jika Anda menguji di <strong>Laragon</strong>, silakan ikuti langkah-langkah mudah di bawah ini:</p>
          <ol style="margin-left: 1.5rem; margin-top: 0.5rem; font-size: 0.9rem; display: flex; flex-direction: column; gap: 0.4rem;">
            <li>Pastikan server <strong>Apache</strong> dan <strong>MySQL</strong> di Laragon Anda sudah aktif (Klik tombol <em>"Start All"</em> di aplikasi Laragon).</li>
            <li>Buka <strong>phpMyAdmin</strong> atau <strong>HeidiSQL</strong> (Klik tombol <em>"Database"</em> di Laragon).</li>
            <li>Buat database baru bernama <code>rwh_db</code>.</li>
            <li>Import file schema SQL yang ada di direktori proyek ini: <br><code>[FOLDER_PROYEK]/schema.sql</code></li>
            <li>Refresh halaman ini setelah database berhasil dibuat dan di-import!</li>
          </ol>
          <p style="margin-top: 0.5rem; font-size: 0.8rem; font-style: italic; color: var(--text-muted);">
            Pesan Kesalahan: <code><?= htmlspecialchars($db_connection_error ?? 'PDO tidak terinisialisasi') ?></code>
          </p>
        </section>
      <?php endif; ?>

      <div class="dashboard-grid">
        
        <!-- BAGIAN KIRI: KARTU METRIK & GRAFIK -->
        <div class="main-content">
          
          <!-- Grid Kartu Parameter Kualitas Air -->
          <div class="metric-cards-container">
            
            <!-- 1. KELAYAKAN AIR SECARA KESELURUHAN -->
            <div class="glass-card status-overall-box" style="width: 100%;">
              <span class="status-label">Status Kualitas Air</span>
              <div id="overall-status-badge" class="status-badge status-sangat-baik">
                <i class="fa-solid fa-circle-check"></i> <span id="status-badge-text">Memuat...</span>
              </div>
            </div>

            <!-- 2. PH CARD -->
            <div class="glass-card metric-card ph" id="card-ph">
              <div class="card-header">
                <span>Derajat Keasaman (pH)</span>
                <span class="card-icon"><i class="fa-solid fa-flask-vial" style="color: var(--color-accent);"></i></span>
              </div>
              <div class="card-value" id="val-ph">--</div>
              <div class="card-footer-info">
                <span class="text-muted">Target: 6.5 - 8.5 pH</span>
                <span id="status-ph-label" class="badge-small sangat-baik">Normal</span>
              </div>
            </div>

            <!-- 3. TDS CARD -->
            <div class="glass-card metric-card tds" id="card-tds">
              <div class="card-header">
                <span>Padatan Terlarut (TDS)</span>
                <span class="card-icon"><i class="fa-solid fa-cubes-stacked" style="color: var(--color-warning);"></i></span>
              </div>
              <div class="card-value" id="val-tds">-- <span class="card-unit">ppm</span></div>
              <div class="card-footer-info">
                <span class="text-muted">Batas: &lt; 550 ppm</span>
                <span id="status-tds-label" class="badge-small sangat-baik">Baik</span>
              </div>
            </div>

            <!-- 4. KEKERUHAN CARD -->
            <div class="glass-card metric-card temp" id="card-turb" style="--color-accent: #38ef7d;">
              <div class="card-header">
                <span>Kekeruhan Air</span>
                <span class="card-icon"><i class="fa-solid fa-eye-dropper" style="color: #38ef7d;"></i></span>
              </div>
              <div class="card-value" id="val-turb">-- <span class="card-unit">NTU</span></div>
              <div class="card-footer-info">
                <span class="text-muted">Indikator</span>
                <span id="status-turb-label" class="badge-small sangat-baik">Jernih</span>
              </div>
            </div>

            <!-- 5. EC CARD -->
            <div class="glass-card metric-card ph" id="card-ec" style="--color-accent: #0072ff;">
              <div class="card-header">
                <span>Daya Hantar (EC)</span>
                <span class="card-icon"><i class="fa-solid fa-bolt" style="color: #0072ff;"></i></span>
              </div>
              <div class="card-value" id="val-ec">-- <span class="card-unit">ms/cm</span></div>
              <div class="card-footer-info">
                <span class="text-muted">Konduktivitas</span>
                <span id="status-ec-label" class="badge-small sangat-baik">Normal</span>
              </div>
            </div>

          </div>

          <!-- Grafik Riwayat Sensor -->
          <div class="glass-card chart-card">
            <div class="chart-header">
              <h3 class="chart-title"><i class="fa-solid fa-chart-line"></i> Grafik Tren pH & TDS (10 Log Terakhir)</h3>
              <span class="text-muted" style="font-size: 0.8rem;"><i class="fa-solid fa-rotate"></i> Terupdate otomatis</span>
            </div>
            <div class="chart-wrapper">
              <canvas id="qualityTrendChart"></canvas>
            </div>
          </div>

        </div>

        <!-- BAGIAN KANAN: TANGKI AIR DIGITAL -->
        <div class="right-sidebar">
          
          <div class="glass-card tank-container-card">
            <h3 class="tank-title"><i class="fa-solid fa-glass-water"></i> Volume Air Tangki Utama</h3>
            
            <!-- Silinder Tangki Air dengan Wave Animation -->
            <div class="water-tank-wrapper">
              <div class="tank-measurements">
                <div class="measurement-line"><span>100%</span></div>
                <div class="measurement-line"><span>75%</span></div>
                <div class="measurement-line"><span>50%</span></div>
                <div class="measurement-line"><span>25%</span></div>
                <div class="measurement-line"><span>0%</span></div>
              </div>
              
              <!-- Tinggi div ini akan dimanipulasi dengan JS -->
              <div id="tank-liquid" class="tank-liquid" style="height: 0%;"></div>
            </div>

            <div class="tank-percentage-display">
              <span id="val-water-level">--</span><span> %</span>
              <p class="text-muted" style="font-size: 0.8rem; margin-top: 0.25rem;">Tinggi Air: <span id="val-water-level-cm">--</span> cm</p>
            </div>
          </div>

          <!-- 6. KARTU INDIKATOR ULTRAVIOLET (UV) -->
          <div class="glass-card uv-card" id="card-uv" style="flex: 1;">
            <h3 class="tank-title" style="text-align: left;"><i class="fa-solid fa-circle-radiation" style="color: #f72585;"></i> Sterilisator UltraViolet (UV)</h3>
            <div class="uv-indicator-container">
              <div id="uv-light" class="uv-light-bulb">
                <i class="fa-solid fa-lightbulb"></i>
              </div>
              <div id="uv-status-badge" class="uv-status-badge uv-status-inactive">
                <span id="uv-status-text">NONAKTIF</span>
              </div>
            </div>
            <div style="text-align: center; width: 100%;">
              <p class="text-muted" style="font-size: 0.8rem; margin-top: 0.25rem;" id="uv-desc">Memuat status kualitas air...</p>
            </div>
          </div>

        </div>

      </div>

      <!-- Rangkuman Statistik Hari Ini (Full-width, di bawah grid) -->
      <div class="glass-card" style="padding: 1.2rem; margin-top: 0;">
        <h3 class="chart-title" style="font-size: 1rem; margin-bottom: 0.8rem;"><i class="fa-solid fa-calculator"></i> Rangkuman Hari Ini</h3>
        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; font-size: 0.85rem;">
          <div style="display: flex; justify-content: space-between; gap: 0.5rem; flex: 1; min-width: 150px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.4rem;">
            <span class="text-secondary">Rata-rata pH:</span>
            <strong id="stat-avg-ph">--</strong>
          </div>
          <div style="display: flex; justify-content: space-between; gap: 0.5rem; flex: 1; min-width: 150px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.4rem;">
            <span class="text-secondary">Rata-rata TDS:</span>
            <strong id="stat-avg-tds">-- ppm</strong>
          </div>
          <div style="display: flex; justify-content: space-between; gap: 0.5rem; flex: 1; min-width: 150px; padding-bottom: 0.2rem;">
            <span class="text-secondary">Tinggi Air Maksimal:</span>
            <strong id="stat-max-level">-- cm</strong>
          </div>
        </div>
      </div>

      <!-- TABS / TABEL LOGS RIWAYAT -->
      <section class="logs-section glass-card">
        <div class="table-header">
          <h3 class="table-title"><i class="fa-solid fa-list-check"></i> Riwayat Pengiriman Data Sensor</h3>
          <span class="live-indicator" style="font-size: 0.75rem;"><i class="fa-solid fa-clock-rotate-left"></i> Real-time Logs</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>No</th>
                <th>Waktu Pengiriman</th>
                <th>Tinggi Air</th>
                <th>pH Level</th>
                <th>TDS (ppm)</th>
                <th>Kekeruhan</th>
                <th>Status Kualitas</th>
              </tr>
            </thead>
            <tbody id="logs-table-body">
              <tr>
                <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                  <i class="fa-solid fa-circle-notch fa-spin"></i> Menghubungkan ke API, memuat data...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- EDUCATION/TIPS CORNER -->
      <section class="tips-container">
        <div class="glass-card tip-card">
          <div class="tip-icon"><i class="fa-solid fa-circle-info"></i></div>
          <div>
            <h4>Info Derajat pH Air</h4>
            <p>Air hujan alami cenderung agak asam (~6.0 pH). Rentang 6.5 - 8.5 pH adalah standar kelayakan air bersih dan minum dari WHO.</p>
          </div>
        </div>
        <div class="glass-card tip-card">
          <div class="tip-icon"><i class="fa-solid fa-filter"></i></div>
          <div>
            <h4>Kapan Butuh Filtrasi?</h4>
            <p>Jika nilai TDS melebihi 550 ppm atau pH di bawah 6.0, air perlu disaring menggunakan karbon aktif dan filter sedimen sebelum digunakan.</p>
          </div>
        </div>
        <div class="glass-card tip-card">
          <div class="tip-icon"><i class="fa-solid fa-circle-question"></i></div>
          <div>
            <h4>Cara Kerja Alat IoT</h4>
            <p>Alat Raspberry Pi Anda mengumpulkan data dari sensor, mengemasnya menjadi format JSON, lalu mempostingnya ke server web ini secara terus menerus.</p>
          </div>
        </div>
      </section>

    </main>

    <!-- FOOTER -->
    <footer style="margin-top: 3rem; text-align: center; padding: 1.5rem 0; border-top: 1px solid var(--glass-border); color: var(--text-muted); font-size: 0.8rem;">
      <p>&copy; 2026 Rainwater Harvesting IoT Project. Developed with Passion.</p>
    </footer>

  </div>

  <!-- JAVASCRIPT REAL-TIME LOGIC & CHARTING -->
  <script>
    // Inisialisasi variabel Grafik Chart.js
    let trendChartObj = null;
    let dbConnected = <?= $db_connected ? 'true' : 'false' ?>;
    const MAX_TINGGI = 150; // Tinggi maksimum tangki air (150 cm)

    // Fungsi utama inisialisasi grafik Chart.js
    function initChart(timeLabels, phData, tdsData) {
      const ctx = document.getElementById('qualityTrendChart').getContext('2d');
      
      // Definisikan gradasi neon untuk grafik
      const phGradient = ctx.createLinearGradient(0, 0, 0, 300);
      phGradient.addColorStop(0, 'rgba(0, 242, 254, 0.4)');
      phGradient.addColorStop(1, 'rgba(0, 242, 254, 0.0)');
      
      const tdsGradient = ctx.createLinearGradient(0, 0, 0, 300);
      tdsGradient.addColorStop(0, 'rgba(255, 179, 0, 0.4)');
      tdsGradient.addColorStop(1, 'rgba(255, 179, 0, 0.0)');

      trendChartObj = new Chart(ctx, {
        type: 'line',
        data: {
          labels: timeLabels,
          datasets: [
            {
              label: 'Derajat Keasaman (pH)',
              data: phData,
              borderColor: '#00f2fe',
              borderWidth: 3,
              backgroundColor: phGradient,
              fill: true,
              tension: 0.35,
              yAxisID: 'y-ph',
              pointBackgroundColor: '#00f2fe',
              pointRadius: 3
            },
            {
              label: 'Kandungan Partikel (TDS ppm)',
              data: tdsData,
              borderColor: '#ffb300',
              borderWidth: 3,
              backgroundColor: tdsGradient,
              fill: true,
              tension: 0.35,
              yAxisID: 'y-tds',
              pointBackgroundColor: '#ffb300',
              pointRadius: 3
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              labels: {
                color: '#f3f4f6',
                font: { family: 'Inter', size: 11 }
              }
            }
          },
          scales: {
            x: {
              grid: { color: 'rgba(255, 255, 255, 0.03)' },
              ticks: { color: '#9ca3af', font: { family: 'Inter', size: 10 } }
            },
            'y-ph': {
              type: 'linear',
              position: 'left',
              min: 0,
              max: 14,
              grid: { color: 'rgba(255, 255, 255, 0.05)' },
              ticks: { color: '#00f2fe', font: { family: 'Space Grotesk', size: 10 } },
              title: { display: true, text: 'Skala pH', color: '#00f2fe' }
            },
            'y-tds': {
              type: 'linear',
              position: 'right',
              min: 0,
              grid: { drawOnChartArea: false }, // Jangan tumpuk grid y-axis kedua
              ticks: { color: '#ffb300', font: { family: 'Space Grotesk', size: 10 } },
              title: { display: true, text: 'TDS (ppm)', color: '#ffb300' }
            }
          }
        }
      });
    }

    // Fungsi untuk memperbarui grafik secara berkala
    function updateChart(timeLabels, phData, tdsData) {
      if (trendChartObj) {
        trendChartObj.data.labels = timeLabels;
        trendChartObj.data.datasets[0].data = phData;
        trendChartObj.data.datasets[1].data = tdsData;
        trendChartObj.update('none'); // Update tanpa animasi transisi berlebih agar mulus
      } else {
        initChart(timeLabels, phData, tdsData);
      }
    }

    // Fungsi utama mengambil data dari API PHP
    async function fetchWaterQualityData() {
      if (!dbConnected) return;

      try {
        const response = await fetch('api/get_data.php');
        
        if (!response.ok) {
          throw new Error('API Responded with error');
        }
        
        const res = await response.json();
        
        if (res.status === 'success') {
          // Sistem terhubung penuh
          document.getElementById('status-dot').className = 'pulse-dot';
          document.getElementById('status-text').textContent = 'Sistem Aktif';
          
          const latest = res.data;
          
          if (latest) {
            // 1. UPDATE KARTU pH
            const phVal = parseFloat(latest.ph);
            document.getElementById('val-ph').textContent = phVal.toFixed(2);
            const phLabel = document.getElementById('status-ph-label');
            phLabel.textContent = latest.status_mineral || 'Normal';
            if (phVal >= 6.5 && phVal <= 8.5) {
              phLabel.className = 'badge-small sangat-baik';
            } else if (phVal >= 6.0 && phVal <= 9.0) {
              phLabel.className = 'badge-small perlu-filtrasi';
            } else {
              phLabel.className = 'badge-small tercemar';
            }

            // 2. UPDATE KARTU TDS
            const tdsVal = parseInt(latest.tds);
            document.getElementById('val-tds').innerHTML = `${tdsVal} <span class="card-unit">ppm</span>`;
            const tdsLabel = document.getElementById('status-tds-label');
            tdsLabel.textContent = latest.status_hazard || 'Baik';
            if (tdsVal < 150) {
              tdsLabel.className = 'badge-small sangat-baik';
            } else if (tdsVal < 550) {
              tdsLabel.className = 'badge-small sangat-baik';
            } else if (tdsVal < 750) {
              tdsLabel.className = 'badge-small perlu-filtrasi';
            } else {
              tdsLabel.className = 'badge-small tercemar';
            }

            // 3. UPDATE KARTU KEKERUHAN
            const turbVal = parseFloat(latest.ntu);
            document.getElementById('val-turb').innerHTML = `${turbVal.toFixed(1)} <span class="card-unit">NTU</span>`;
            const turbLabel = document.getElementById('status-turb-label');
            turbLabel.textContent = latest.ket_turbidity || 'Jernih';
            if (latest.ket_turbidity && latest.ket_turbidity.toLowerCase().includes('jernih')) {
              turbLabel.className = 'badge-small sangat-baik';
            } else if (latest.ket_turbidity && latest.ket_turbidity.toLowerCase().includes('agak keruh')) {
              turbLabel.className = 'badge-small perlu-filtrasi';
            } else {
              turbLabel.className = 'badge-small tercemar';
            }

            // 4. UPDATE KARTU EC
            const ecVal = parseFloat(latest.ec);
            document.getElementById('val-ec').innerHTML = `${ecVal.toFixed(2)} <span class="card-unit">ms/cm</span>`;
            const ecLabel = document.getElementById('status-ec-label');
            if (ecVal < 1.0) {
              ecLabel.className = 'badge-small sangat-baik';
              ecLabel.textContent = 'Rendah';
            } else if (ecVal < 2.0) {
              ecLabel.className = 'badge-small sangat-baik';
              ecLabel.textContent = 'Normal';
            } else {
              ecLabel.className = 'badge-small perlu-filtrasi';
              ecLabel.textContent = 'Tinggi';
            }
 
             // 5. UPDATE LEVEL AIR TANGKI (KANAN)
             const tinggi = parseFloat(latest.tinggi_air);
             let persentase = (tinggi / MAX_TINGGI) * 100;
             if (persentase > 100) persentase = 100;
             if (persentase < 0) persentase = 0;
             
             // Format persentase secara spesifik: tampilkan desimal hanya jika ada (misal: 89.3% atau 89% jika bulat)
             let displayPersen = persentase.toFixed(1);
             if (displayPersen.endsWith('.0')) {
               displayPersen = displayPersen.substring(0, displayPersen.length - 2);
             }
             
             document.getElementById('val-water-level').textContent = displayPersen;
             document.getElementById('val-water-level-cm').textContent = tinggi.toFixed(1);
             document.getElementById('tank-liquid').style.height = `${persentase}%`;
 
             // 6. UPDATE BADGE KUALITAS KESELURUHAN (STATUS BADGE)
             const badge = document.getElementById('overall-status-badge');
             let overallStatus = 'Sangat Baik';
             
             // Cek kondisi tercemar terlebih dahulu karena tingkat keparahan lebih tinggi
             if (phVal < 6.0 || phVal > 9.0 || tdsVal >= 600 || (latest.ket_turbidity && latest.ket_turbidity.toLowerCase().includes('keruh') && !latest.ket_turbidity.toLowerCase().includes('agak'))) {
               overallStatus = 'Tercemar';
             }
             else if (phVal < 6.5 || phVal > 8.5 || tdsVal >= 550 || (latest.ket_turbidity && latest.ket_turbidity.toLowerCase().includes('agak keruh'))) {
               overallStatus = 'Perlu Filtrasi';
             }

             if (overallStatus === 'Sangat Baik') {
               badge.className = 'status-badge status-sangat-baik';
               badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span id="status-badge-text">Kualitas: Sangat Baik</span>';
             } else if (overallStatus === 'Perlu Filtrasi') {
               badge.className = 'status-badge status-perlu-filtrasi';
               badge.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> <span id="status-badge-text">Kualitas: Perlu Filtrasi</span>';
             } else {
               badge.className = 'status-badge status-tercemar';
               badge.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> <span id="status-badge-text">Kualitas: Tercemar / Bahaya</span>';
             }

             // 6.1. UPDATE INDIKATOR ULTRAVIOLET (UV)
             const uvLight = document.getElementById('uv-light');
             const uvBadge = document.getElementById('uv-status-badge');
             const uvText = document.getElementById('uv-status-text');
             const uvDesc = document.getElementById('uv-desc');
             
             if (overallStatus === 'Perlu Filtrasi' || overallStatus === 'Tercemar') {
               // UV Aktif
               uvLight.className = 'uv-light-bulb active';
               uvBadge.className = 'uv-status-badge uv-status-active';
               uvText.textContent = 'AKTIF';
               if (overallStatus === 'Tercemar') {
                 uvDesc.textContent = 'Air Kotor / Tercemar! Sinar UV aktif membunuh kuman & patogen.';
               } else {
                 uvDesc.textContent = 'Air Perlu Filtrasi. Sinar UV aktif mensterilkan air.';
               }
             } else {
               // UV Nonaktif (Air bersih)
               uvLight.className = 'uv-light-bulb';
               uvBadge.className = 'uv-status-badge uv-status-inactive';
               uvText.textContent = 'NONAKTIF';
               uvDesc.textContent = 'Air Bersih — Sinar UV tidak aktif.';
             }
 
             // Periksa jika data IoT mati / tidak mengirim data baru
             const lastDataTime = new Date(latest.created_at).getTime();
             const nowTime = new Date().getTime();
             const diffSeconds = (nowTime - lastDataTime) / 1000;
             
             // Jika data terakhir dikirim > 30 detik yang lalu, tandai status IoT terputus
             if (diffSeconds > 30) {
               document.getElementById('status-dot').className = 'pulse-dot disconnected';
               document.getElementById('status-text').textContent = 'IoT Pi Terputus';
             }
           }
 
           // 7. UPDATE RINGKASAN STATISTIK HARI INI & GRAFIK DARI DATA LOG
           const history = res.data_log;
           if (history && history.length > 0) {
             // Hitung statistik hari ini dari data log
             const validPhs = history.map(item => parseFloat(item.ph)).filter(val => !isNaN(val));
             const validTdss = history.map(item => parseFloat(item.tds)).filter(val => !isNaN(val));
             const validTinggi = history.map(item => parseFloat(item.tinggi_air)).filter(val => !isNaN(val));
             
             const avgPh = validPhs.length > 0 ? validPhs.reduce((sum, v) => sum + v, 0) / validPhs.length : 7.0;
             const avgTds = validTdss.length > 0 ? validTdss.reduce((sum, v) => sum + v, 0) / validTdss.length : 100;
             const maxLevel = validTinggi.length > 0 ? Math.max(...validTinggi) : 0;
             
             document.getElementById('stat-avg-ph').textContent = avgPh.toFixed(2);
             document.getElementById('stat-avg-tds').textContent = `${Math.round(avgTds)} ppm`;
             document.getElementById('stat-max-level').textContent = `${maxLevel.toFixed(1)} cm`;
 
             // Update Grafik Tren (10 Log Terakhir)
             const sortedHistory = [...history].reverse(); // Urutkan kronologis untuk grafik
             const labels = sortedHistory.map(item => item.created_at ? item.created_at.substring(11, 19) : '--');
             const phs = sortedHistory.map(item => parseFloat(item.ph));
             const tdss = sortedHistory.map(item => parseFloat(item.tds));
             
             updateChart(labels, phs, tdss);
 
             // 8. UPDATE TABEL RIWAYAT LOGS
             const tbody = document.getElementById('logs-table-body');
             let tableRowsHTML = '';
             
             // Tampilkan history dengan urutan terbaru di atas
             history.forEach((log, index) => {
               let badgeClass = 'sangat-baik';
               let logStatus = 'Sangat Baik';
               const logPh = parseFloat(log.ph);
               const logTds = parseFloat(log.tds);
               
               if (logPh < 6.0 || logPh > 9.0 || logTds >= 750 || (log.ket_turbidity && log.ket_turbidity.toLowerCase().includes('keruh'))) {
                 badgeClass = 'tercemar';
                 logStatus = 'Tercemar';
               } else if (logPh < 6.5 || logPh > 8.5 || logTds >= 550) {
                 badgeClass = 'perlu-filtrasi';
                 logStatus = 'Perlu Filter';
               }
               
               const formattedTime = log.created_at ? log.created_at.substring(11, 19) : '--';
               const formattedDate = log.created_at ? log.created_at.substring(0, 10) : '--';
 
               // Hitung persentase untuk progress bar tabel
               const logTinggi = parseFloat(log.tinggi_air);
               let logPersen = (logTinggi / MAX_TINGGI) * 100;
               if (logPersen > 100) logPersen = 100;
               if (logPersen < 0) logPersen = 0;
               
               let displayLogPersen = logPersen.toFixed(1);
               if (displayLogPersen.endsWith('.0')) {
                 displayLogPersen = displayLogPersen.substring(0, displayLogPersen.length - 2);
               }
 
               tableRowsHTML += `
                 <tr>
                   <td><strong>${index + 1}</strong></td>
                   <td>${formattedDate} ${formattedTime}</td>
                   <td>
                     <div style="display:flex; align-items:center; gap:8px;">
                       <div style="width:50px; background:rgba(255,255,255,0.05); border-radius:5px; height:8px; overflow:hidden;">
                         <div style="width:${logPersen}%; background:var(--color-accent-blue); height:100%;"></div>
                       </div>
                       <span>${displayLogPersen}% (${logTinggi.toFixed(1)} cm)</span>
                     </div>
                   </td>
                   <td><i class="fa-solid fa-flask-vial text-muted"></i> ${parseFloat(log.ph).toFixed(2)}</td>
                   <td><i class="fa-solid fa-cubes-stacked text-muted"></i> ${parseInt(log.tds)} ppm</td>
                   <td><i class="fa-solid fa-eye-dropper text-muted"></i> ${parseFloat(log.ntu).toFixed(1)} NTU (${log.ket_turbidity})</td>
                   <td><span class="badge-small ${badgeClass}">${logStatus}</span></td>
                 </tr>
               `;
             });
             
             tbody.innerHTML = tableRowsHTML;
          } else {
            // Data kosong dalam database
            document.getElementById('logs-table-body').innerHTML = `
              <tr>
                <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                  <i class="fa-solid fa-circle-info"></i> Database terhubung, tetapi belum ada data sensor yang masuk. Silakan jalankan script simulator Raspberry Pi Anda!
                </td>
              </tr>
            `;
          }
        }
      } catch (error) {
        console.error('Error fetching sensor data:', error);
        document.getElementById('status-dot').className = 'pulse-dot disconnected';
        document.getElementById('status-text').textContent = 'API Error';
      }
    }

    // Jalankan pertama kali saat halaman dimuat
    document.addEventListener("DOMContentLoaded", () => {
      // Inisialisasi awal chart dengan array kosong agar canvas siap
      if (dbConnected) {
        initChart([], [], []);
        // Ambil data pertama kali
        fetchWaterQualityData();
        // Set interval polling berkala setiap 3 detik
        setInterval(fetchWaterQualityData, 3000);
      } else {
        document.getElementById('logs-table-body').innerHTML = `
          <tr>
            <td colspan="7" style="text-align: center; color: #ff5252; padding: 2rem;">
              <i class="fa-solid fa-triangle-exclamation"></i> Gagal memuat data karena database belum terhubung. Ikuti petunjuk penyusunan database di atas!
            </td>
          </tr>
        `;
      }
    });
  </script>
</body>
</html>