<?php
// Set timezone ke Asia/Jakarta untuk konsistensi
require_once __DIR__ . '/config/bootstrap.php';
session_start();

// Cek session, jika belum login redirect ke login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Dashboard Absensi WhatsApp</title>
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-top">
                <h1>📊 Dashboard Absensi WhatsApp</h1>
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php 
                            $name = $_SESSION['name'] ?? $_SESSION['wa_name'] ?? 'User';
                            echo strtoupper(substr($name, 0, 1)); 
                            ?>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
                            <?php if ($_SESSION['isAdmin'] ?? false): ?>
                                <span class="user-badge">Admin</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="user-actions">
                        <?php if ($_SESSION['isAdmin'] ?? false): ?>
                            <a href="admin.php" class="btn-admin">
                                <span class="btn-icon">⚙️</span>
                                <span class="btn-text">Admin</span>
                            </a>
                        <?php endif; ?>
                        <button onclick="logout()" class="btn-logout">
                            <span class="btn-icon">🚪</span>
                            <span class="btn-text">Logout</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="header-controls">
                <input type="date" id="startDate" class="date-input" value="<?php echo date('Y-m-d'); ?>" data-server-date="<?php echo date('Y-m-d'); ?>">
                <span>s/d</span>
                <input type="date" id="endDate" class="date-input" value="<?php echo date('Y-m-d'); ?>" data-server-date="<?php echo date('Y-m-d'); ?>">
                <button onclick="loadData()" class="btn-primary">Filter</button>
                <button onclick="loadToday()" class="btn-secondary">Hari Ini</button>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <div class="stat-label">Total User</div>
                    <div class="stat-value" id="totalUser">-</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-label">Total Absensi</div>
                    <div class="stat-value" id="totalAbsen">-</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-info">
                    <div class="stat-label">WFO</div>
                    <div class="stat-value" id="totalWFO">-</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏠</div>
                <div class="stat-info">
                    <div class="stat-label">WFH</div>
                    <div class="stat-value" id="totalWFH">-</div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2>Data Absensi</h2>
                <button onclick="exportData()" class="btn-export">📥 Export</button>
            </div>
            <table id="absensiTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>No. HP</th>
                        <th>Tipe</th>
                        <th>Lantai</th>
                        <th>Lokasi</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody id="absensiTableBody">
                    <tr>
                        <td colspan="7" class="loading">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script src="assets/script.js?v=<?php echo time(); ?>"></script>
    <script>
        // Clear any cached date values
        if (typeof(Storage) !== "undefined") {
            try {
                localStorage.removeItem('startDate');
                localStorage.removeItem('endDate');
                sessionStorage.removeItem('startDate');
                sessionStorage.removeItem('endDate');
            } catch(e) {
                console.warn('Could not clear storage:', e);
            }
        }

        async function logout() {
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                });

                const data = await response.json();
                if (data.success) {
                    window.location.href = 'login.php';
                }
            } catch (error) {
                console.error('Logout error:', error);
                window.location.href = 'login.php';
            }
        }
    </script>
</body>
</html>

