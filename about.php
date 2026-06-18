<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Project - RWH Portal</title>
  
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime('css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    /* CSS Tambahan Khusus Halaman About */
    header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
    .nav-menu { display: flex; gap: 1rem; background: rgba(255, 255, 255, 0.03); padding: 0.5rem; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); }
    .nav-link { color: var(--text-secondary); text-decoration: none; font-weight: 500; font-size: 0.9rem; padding: 0.6rem 1.2rem; border-radius: 8px; transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem; }
    .nav-link:hover { color: var(--text-primary); background: rgba(255, 255, 255, 0.08); }
    .nav-link.active { color: white; background: linear-gradient(135deg, rgba(0, 242, 254, 0.2) 0%, rgba(79, 172, 254, 0.2) 100%); border: 1px solid rgba(0, 242, 254, 0.3); }
    
    .about-main { display: flex; flex-direction: column; gap: 1.5rem; max-width: 850px; margin: 0 auto; padding-top: 1rem; }
    .about-section h2, .developer-section h2 { font-size: 1.3rem; margin-bottom: 1rem; color: var(--text-primary); border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 1rem; }
    .about-section p { color: var(--text-secondary); line-height: 1.8; margin-bottom: 1rem; font-size: 0.95rem; text-align: justify; }
    
    .dev-profile { display: flex; align-items: center; gap: 2rem; margin: 1.5rem 0; padding: 1.5rem; background: rgba(0, 0, 0, 0.2); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.03); }
    
    /* Styling untuk Foto Profil */
    .dev-avatar-img { width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 3px solid rgba(255, 255, 255, 0.2); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4); transition: transform 0.3s ease, border-color 0.3s ease; cursor: pointer; }
    .dev-avatar-img:hover { transform: scale(1.05); border-color: rgba(0, 242, 254, 0.6); }
    .dev-avatar-img img { width: 100%; height: 100%; object-fit: cover; }
    
    .dev-info h3 { margin: 0 0 0.5rem 0; font-size: 1.5rem; color: var(--text-primary); }
    .dev-info .text-muted { margin: 0 0 0.3rem 0; font-size: 0.9rem; }
    .team-badge { display: inline-flex; align-items: center; gap: 0.4rem; background: rgba(186, 82, 245, 0.15); color: #d187ff; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; margin-top: 0.6rem; font-weight: 600; border: 1px solid rgba(186, 82, 245, 0.3); }
  </style>
</head>
<body>

  <div class="app-container">
    <header>
      <div class="brand-container">
        <h1>RWH Portal</h1>
        <p>Smart Rainwater Harvesting Telemetry</p>
      </div>
      
      <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
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

        <nav class="nav-menu">
          <a href="index.php" class="nav-link"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
          <a href="about.php" class="nav-link active"><i class="fa-solid fa-circle-info"></i> About</a>
        </nav>
      </div>
    </header>

    <main class="about-main">
      
      <div class="glass-card about-section">
        <h2><i class="fa-solid fa-droplet" style="color: #00f2fe;"></i> Latar Belakang Proyek</h2>
        <p>Telkom University sangat peduli dengan penerapan Sustainable Development Goals (SDGs) nomor 6 yang berkaitan dengan Water and Sanitation. Air sangat penting dalam menunjang aktivitas kehidupan kampus dengan memanfaatkan berbagai sumber air, air tanah dan air hujan.</p>
        <p>Sistem Rainwater Harvesting (RWH) di Telkom University memanfaatkan air hujan dari atap gedung, area halaman dan permukaan terbuka (surface runoff). Air hujan yang jatuh di area paving, jalan internal, dan ruang terbuka hijau diarahkan melalui sistem drainase melalui proses filtrasi menuju ground tank untuk dimanfaatkan kembali.</p>
        <p>Telkom University memanfaatkan teknologi terkini Smart monitoring system yang terdiri dari sensor pH, volume, kejernihan, TDS dan pembunuh kuman cahaya Ultraviolet serta Internet of Things (IoT). Luaran groundtank yang sudah mendapat perlakuan ini dapat digunakan untuk mendukung kebutuhan air di lingkungan kampus.</p>
      </div>

      <div class="glass-card developer-section">
        <h2><i class="fa-solid fa-code" style="color: #ba52f5;"></i> Profil Pengembang</h2>
        
        <div class="dev-profile">
          <a href="index.php" title="Klik untuk kembali ke Dashboard">
            <div class="dev-avatar-img">
              <img src="FOTO PROFIL/ERNA.jpg" alt="Foto Erna">
            </div>
          </a>
          
          <div class="dev-info">
            <h3>Dr. Erna Sri Sugesti</h3>
            <p class="text-muted"><i class="fa-solid fa-laptop-code"></i> Lecturer</p>
            <p class="text-muted"><i class="fa-solid fa-graduation-cap"></i> Telkom University</p>
          </div>
        </div>

        <div class="dev-profile">
          <a href="index.php" title="Klik untuk kembali ke Dashboard">
            <div class="dev-avatar-img">
              <img src="FOTO PROFIL/yasyfa.jpeg" alt="Foto Yasyfa">
            </div>
          </a>
          
          <div class="dev-info">
            <h3>Sulthan Yasyfa Pusponegoro</h3>
            <p class="text-muted"><i class="fa-solid fa-laptop-code"></i> IoT & System Developer</p>
            <p class="text-muted"><i class="fa-solid fa-graduation-cap"></i> Assistant Optical Communication Laboratory</p>
          </div>
        </div>

        <div class="dev-profile">
          <a href="index.php" title="Klik untuk kembali ke Dashboard">
            <div class="dev-avatar-img">
              <img src="FOTO PROFIL/yosef.jpeg" alt="Foto Yosef">
            </div>
          </a>
          
          <div class="dev-info">
            <h3>Yosef Ivan Ramiro Purba</h3>
            <p class="text-muted"><i class="fa-solid fa-laptop-code"></i> IoT & System Developer</p>
            <p class="text-muted"><i class="fa-solid fa-graduation-cap"></i> Assistant Optical Communication Laboratory</p>
          </div>
        </div>

        <div class="dev-profile">
          <a href="index.php" title="Klik untuk kembali ke Dashboard">
            <div class="dev-avatar-img">
              <img src="FOTO PROFIL/Ricky Victor.jpeg" alt="Foto Ricky Victor">
            </div>
          </a>
          
          <div class="dev-info">
            <h3>Ricky Victor</h3>
            <p class="text-muted"><i class="fa-solid fa-laptop-code"></i> IoT & System Developer</p>
            <p class="text-muted"><i class="fa-solid fa-graduation-cap"></i> Partner of Assistant Optical Communication Laboratory</p>
          </div>
        </div>
      </div>

    </main>
  </div>

</body>
</html>