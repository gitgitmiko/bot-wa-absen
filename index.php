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
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Menu</h2>
                <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
                    <span class="toggle-icon">◄</span>
                </button>
            </div>
            <nav class="sidebar-nav">
                <ul class="sidebar-menu">
                    <li>
                        <a href="#" class="menu-item active" data-menu="absensi" onclick="switchMenu('absensi', event)">
                            <span class="menu-icon">📊</span>
                            <span class="menu-text">Absensi Bot WA</span>
                        </a>
                    </li>
                    <?php if ($_SESSION['isAdmin'] ?? false): ?>
                    <li>
                        <a href="#" class="menu-item" data-menu="admin" onclick="switchMenu('admin', event)">
                            <span class="menu-icon">⚙️</span>
                            <span class="menu-text">Admin Panel</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-top">
                    <h1>Dashboard Absensi WhatsApp</h1>
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
                            <button onclick="logout()" class="btn-logout">
                                <span class="btn-text">Logout</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Menu: Absensi Bot WA -->
                <div class="content-panel active" id="content-absensi">
                    <div class="panel-header">
                        <h2>Absensi Bot WA</h2>
                    </div>
                    
                    <div class="panel-controls">
                        <div class="date-filters">
                            <input type="date" id="startDate" class="date-input" value="<?php echo date('Y-m-d'); ?>" data-server-date="<?php echo date('Y-m-d'); ?>">
                            <span>s/d</span>
                            <input type="date" id="endDate" class="date-input" value="<?php echo date('Y-m-d'); ?>" data-server-date="<?php echo date('Y-m-d'); ?>">
                            <button onclick="loadData()" class="btn-primary">Filter</button>
                            <button onclick="loadToday()" class="btn-secondary">Hari Ini</button>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">U</div>
                            <div class="stat-info">
                                <div class="stat-label">Total User</div>
                                <div class="stat-value" id="totalUser">-</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">✓</div>
                            <div class="stat-info">
                                <div class="stat-label">Total Absensi</div>
                                <div class="stat-value" id="totalAbsen">-</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">O</div>
                            <div class="stat-info">
                                <div class="stat-label">WFO</div>
                                <div class="stat-value" id="totalWFO">-</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">H</div>
                            <div class="stat-info">
                                <div class="stat-label">WFH</div>
                                <div class="stat-value" id="totalWFH">-</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">A</div>
                            <div class="stat-info">
                                <div class="stat-label">WFA</div>
                                <div class="stat-value" id="totalWFA">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-header">
                            <h3>Data Absensi</h3>
                            <button onclick="exportData()" class="btn-export">Export Data</button>
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

                <!-- Menu: Admin Panel -->
                <?php if ($_SESSION['isAdmin'] ?? false): ?>
                <div class="content-panel" id="content-admin">
                    <div class="panel-header">
                        <h2>Admin Panel</h2>
                    </div>

                    <!-- Admin Tabs -->
                    <div class="admin-tabs">
                        <button class="admin-tab active" onclick="switchAdminTab('commands')" id="tab-commands">
                            Kelola Command
                        </button>
                        <button class="admin-tab" onclick="switchAdminTab('groups')" id="tab-groups">
                            Kelola Groups WA
                        </button>
                        <button class="admin-tab" onclick="switchAdminTab('users')" id="tab-users">
                            Kelola User
                        </button>
                    </div>

                    <!-- Tab: Commands -->
                    <div class="admin-tab-content active" id="admin-content-commands">
                        <div class="admin-actions-bar">
                            <button class="btn btn-primary" onclick="openAddCommandModal()">+ Tambah Command</button>
                        </div>

                        <div class="commands-container">
                            <div class="commands-header">
                                <h3>Daftar Command</h3>
                            </div>
                            <div class="error-message" id="commandErrorMessage"></div>
                            <table id="commandsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Command</th>
                                        <th>Deskripsi</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="commandsTableBody">
                                    <tr>
                                        <td colspan="5" class="loading">Memuat data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Modal Add/Edit Command -->
                        <div id="commandModal" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3 id="commandModalTitle">Tambah Command</h3>
                                    <button class="close-btn" onclick="closeCommandModal()">&times;</button>
                                </div>
                                <form id="commandForm" onsubmit="handleCommandSubmit(event)">
                                    <input type="hidden" id="commandId">
                                    <div class="form-group">
                                        <label>Command *</label>
                                        <input type="text" id="commandText" required placeholder="/command">
                                    </div>
                                    <div class="form-group">
                                        <label>Deskripsi *</label>
                                        <textarea id="commandDescription" required placeholder="Deskripsi command"></textarea>
                                    </div>
                                    <div class="form-group form-group-checkbox">
                                        <input type="checkbox" id="commandIsActive" checked>
                                        <label for="commandIsActive">Aktif</label>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan</button>
                                        <button type="button" class="btn btn-secondary" onclick="closeCommandModal()" style="flex: 1;">Batal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Groups -->
                    <div class="admin-tab-content" id="admin-content-groups">
                        <div class="admin-actions-bar">
                            <button class="btn btn-primary" onclick="openAddGroupModal()">+ Tambah Group WA</button>
                        </div>

                        <div class="commands-container">
                            <div class="commands-header">
                                <h3>Daftar WhatsApp Groups</h3>
                            </div>
                            <div class="error-message" id="groupErrorMessage"></div>
                            <table id="groupsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Group ID</th>
                                        <th>Nama Group</th>
                                        <th>Deskripsi</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="groupsTableBody">
                                    <tr>
                                        <td colspan="6" class="loading">Memuat data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Modal Add/Edit Group -->
                        <div id="groupModal" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3 id="groupModalTitle">Tambah Group WA</h3>
                                    <button class="close-btn" onclick="closeGroupModal()">&times;</button>
                                </div>
                                <form id="groupForm" onsubmit="handleGroupSubmit(event)">
                                    <input type="hidden" id="groupId">
                                    <div class="form-group">
                                        <label>Group ID *</label>
                                        <input type="text" id="groupGroupId" required placeholder="120363422758876589@g.us">
                                        <small style="color: #6b7280; font-size: 12px;">Format: 120363422758876589@g.us</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Nama Group</label>
                                        <input type="text" id="groupName" placeholder="Nama Group (opsional)">
                                    </div>
                                    <div class="form-group">
                                        <label>Deskripsi</label>
                                        <textarea id="groupDescription" placeholder="Deskripsi group (opsional)"></textarea>
                                    </div>
                                    <div class="form-group form-group-checkbox">
                                        <input type="checkbox" id="groupIsActive" checked>
                                        <label for="groupIsActive">Aktif</label>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan</button>
                                        <button type="button" class="btn btn-secondary" onclick="closeGroupModal()" style="flex: 1;">Batal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Users -->
                    <div class="admin-tab-content" id="admin-content-users">
                        <div class="admin-actions-bar">
                            <button class="btn btn-primary" onclick="openAddUserModal()">+ Tambah User</button>
                        </div>

                        <div class="commands-container">
                            <div class="commands-header">
                                <h3>Daftar User</h3>
                            </div>
                            <div class="error-message" id="userErrorMessage"></div>
                            <table id="usersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>WA Name</th>
                                        <th>Nama</th>
                                        <th>No. HP</th>
                                        <th>Admin</th>
                                        <th>Tanggal Daftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <tr>
                                        <td colspan="7" class="loading">Memuat data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Modal Add/Edit User -->
                        <div id="userModal" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3 id="userModalTitle">Tambah User</h3>
                                    <button class="close-btn" onclick="closeUserModal()">&times;</button>
                                </div>
                                <form id="userForm" onsubmit="handleUserSubmit(event)">
                                    <input type="hidden" id="userId">
                                    <div class="form-group">
                                        <label>WA Name *</label>
                                        <input type="text" id="userWaName" required placeholder="wa_name">
                                    </div>
                                    <div class="form-group">
                                        <label>Nama</label>
                                        <input type="text" id="userName" placeholder="Nama lengkap (opsional)">
                                    </div>
                                    <div class="form-group">
                                        <label>No. HP</label>
                                        <input type="text" id="userPhoneNumber" placeholder="Nomor HP (opsional)">
                                    </div>
                                    <div class="form-group" id="userPasswordGroup">
                                        <label>Password *</label>
                                        <input type="password" id="userPassword" placeholder="Minimal 6 karakter">
                                        <small style="color: #6b7280; font-size: 12px;">Hanya diperlukan saat menambah user baru</small>
                                    </div>
                                    <div class="form-group form-group-checkbox">
                                        <input type="checkbox" id="userIsAdmin">
                                        <label for="userIsAdmin">Admin</label>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan</button>
                                        <button type="button" class="btn btn-secondary" onclick="closeUserModal()" style="flex: 1;">Batal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
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

        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggleIcon = document.querySelector('.toggle-icon');
            sidebar.classList.toggle('collapsed');
            
            // Update toggle icon
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.textContent = '►';
            } else {
                toggleIcon.textContent = '◄';
            }
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        // Menu switching function
        function switchMenu(menuId, event) {
            if (event) {
                event.preventDefault();
            }
            
            // Remove active class from all menu items
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Remove active class from all content panels
            document.querySelectorAll('.content-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            // Add active class to selected menu item
            const menuItem = document.querySelector(`[data-menu="${menuId}"]`);
            if (menuItem) {
                menuItem.classList.add('active');
            }
            
            // Show selected content panel
            const contentPanel = document.getElementById(`content-${menuId}`);
            if (contentPanel) {
                contentPanel.classList.add('active');
                
                // Load data for specific menu
                <?php if ($_SESSION['isAdmin'] ?? false): ?>
                if (menuId === 'admin') {
                    // Load commands tab by default
                    switchAdminTab('commands');
                }
                <?php endif; ?>
            }
        }

        // Admin Tab Switching
        function switchAdminTab(tabName) {
            // Remove active from all tabs
            document.querySelectorAll('.admin-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all tab contents
            document.querySelectorAll('.admin-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Add active to selected tab
            const tab = document.getElementById(`tab-${tabName}`);
            if (tab) {
                tab.classList.add('active');
            }
            
            // Show selected content
            const content = document.getElementById(`admin-content-${tabName}`);
            if (content) {
                content.classList.add('active');
                
                // Load data when tab is shown
                if (tabName === 'commands' && typeof loadCommands === 'function') {
                    loadCommands();
                } else if (tabName === 'groups' && typeof loadGroups === 'function') {
                    loadGroups();
                } else if (tabName === 'users' && typeof loadUsers === 'function') {
                    loadUsers();
                }
            }
        }

        // Initialize sidebar state from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleIcon = document.querySelector('.toggle-icon');
            const savedState = localStorage.getItem('sidebarCollapsed');
            
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
                toggleIcon.textContent = '►';
            }
            
            // Set default menu to absensi
            switchMenu('absensi');
        });

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

        // Admin Panel Functions
        <?php if ($_SESSION['isAdmin'] ?? false): ?>
        let commands = [];

        async function loadCommands() {
            try {
                const response = await fetch('api/admin/commands.php');
                const data = await response.json();

                if (data.success) {
                    commands = data.data;
                    renderCommands();
                } else {
                    showError(data.error || 'Gagal memuat data');
                }
            } catch (error) {
                showError('Terjadi kesalahan: ' + error.message);
            }
        }

        function renderCommands() {
            const tbody = document.getElementById('commandsTableBody');
            
            if (!tbody) return;
            
            if (commands.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Tidak ada data</td></tr>';
                return;
            }

            tbody.innerHTML = commands.map(cmd => `
                <tr>
                    <td>${cmd.id}</td>
                    <td><code>${cmd.command}</code></td>
                    <td>${cmd.description || '-'}</td>
                    <td>
                        <span class="status-badge ${cmd.is_active ? 'status-active' : 'status-inactive'}">
                            ${cmd.is_active ? 'Aktif' : 'Tidak Aktif'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-primary" onclick="openEditCommandModal(${cmd.id})" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                        <button class="btn btn-danger" onclick="deleteCommand(${cmd.id})" style="padding: 6px 12px; font-size: 12px;">Hapus</button>
                    </td>
                </tr>
            `).join('');
        }

        function openAddCommandModal() {
            const modal = document.getElementById('commandModal');
            if (!modal) return;
            
            document.getElementById('commandModalTitle').textContent = 'Tambah Command';
            document.getElementById('commandForm').reset();
            document.getElementById('commandId').value = '';
            document.getElementById('commandIsActive').checked = true;
            modal.classList.add('active');
        }

        function openEditCommandModal(id) {
            const cmd = commands.find(c => c.id === id);
            if (!cmd) return;

            const modal = document.getElementById('commandModal');
            if (!modal) return;

            document.getElementById('commandModalTitle').textContent = 'Edit Command';
            document.getElementById('commandId').value = cmd.id;
            document.getElementById('commandText').value = cmd.command;
            document.getElementById('commandDescription').value = cmd.description || '';
            document.getElementById('commandIsActive').checked = cmd.is_active;
            modal.classList.add('active');
        }

        function closeCommandModal() {
            const modal = document.getElementById('commandModal');
            if (modal) {
                modal.classList.remove('active');
            }
            const errorMsg = document.getElementById('commandErrorMessage');
            if (errorMsg) {
                errorMsg.style.display = 'none';
            }
        }

        async function handleCommandSubmit(e) {
            e.preventDefault();
            clearCommandError();

            const id = document.getElementById('commandId').value;
            const command = document.getElementById('commandText').value.trim();
            const description = document.getElementById('commandDescription').value.trim();
            const isActive = document.getElementById('commandIsActive').checked;

            if (!command.startsWith('/')) {
                showCommandError('Command harus dimulai dengan /');
                return;
            }

            try {
                const url = 'api/admin/commands.php';
                const method = id ? 'PUT' : 'POST';
                const body = {
                    id: id || undefined,
                    command: command,
                    description: description,
                    is_active: isActive
                };

                if (id) {
                    body.id = parseInt(id);
                }

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });

                const data = await response.json();

                if (data.success) {
                    closeCommandModal();
                    loadCommands();
                } else {
                    showCommandError(data.error || 'Gagal menyimpan');
                }
            } catch (error) {
                showCommandError('Terjadi kesalahan: ' + error.message);
            }
        }

        async function deleteCommand(id) {
            if (!confirm('Yakin ingin menghapus command ini?')) return;

            try {
                const response = await fetch('api/admin/commands.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });

                const data = await response.json();

                if (data.success) {
                    loadCommands();
                } else {
                    showCommandError(data.error || 'Gagal menghapus');
                }
            } catch (error) {
                showCommandError('Terjadi kesalahan: ' + error.message);
            }
        }

        function showCommandError(message) {
            const errorEl = document.getElementById('commandErrorMessage');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.style.display = 'block';
            }
        }

        function clearCommandError() {
            const errorEl = document.getElementById('commandErrorMessage');
            if (errorEl) {
                errorEl.style.display = 'none';
            }
        }

        // Groups Management Functions
        let groups = [];

        async function loadGroups() {
            try {
                const response = await fetch('api/bot/groups.php');
                const data = await response.json();

                if (data.success) {
                    groups = data.data || [];
                    renderGroups();
                } else {
                    showGroupError(data.error || 'Gagal memuat data');
                }
            } catch (error) {
                showGroupError('Terjadi kesalahan: ' + error.message);
            }
        }

        function renderGroups() {
            const tbody = document.getElementById('groupsTableBody');
            
            if (!tbody) return;
            
            if (groups.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Tidak ada data</td></tr>';
                return;
            }

            tbody.innerHTML = groups.map(group => `
                <tr>
                    <td>${group.id}</td>
                    <td><code>${group.group_id}</code></td>
                    <td>${group.group_name || '-'}</td>
                    <td>${group.description || '-'}</td>
                    <td>
                        <span class="status-badge ${group.is_active ? 'status-active' : 'status-inactive'}">
                            ${group.is_active ? 'Aktif' : 'Tidak Aktif'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-primary" onclick="openEditGroupModal('${group.group_id}')" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                        <button class="btn btn-danger" onclick="deleteGroup('${group.group_id}')" style="padding: 6px 12px; font-size: 12px;">Hapus</button>
                    </td>
                </tr>
            `).join('');
        }

        function openAddGroupModal() {
            const modal = document.getElementById('groupModal');
            if (!modal) return;
            
            document.getElementById('groupModalTitle').textContent = 'Tambah Group WA';
            document.getElementById('groupForm').reset();
            document.getElementById('groupId').value = '';
            document.getElementById('groupIsActive').checked = true;
            modal.classList.add('active');
        }

        function openEditGroupModal(groupId) {
            const group = groups.find(g => g.group_id === groupId);
            if (!group) return;

            const modal = document.getElementById('groupModal');
            if (!modal) return;

            document.getElementById('groupModalTitle').textContent = 'Edit Group WA';
            document.getElementById('groupId').value = group.group_id;
            document.getElementById('groupGroupId').value = group.group_id;
            document.getElementById('groupGroupId').readOnly = true; // Group ID tidak bisa diubah
            document.getElementById('groupName').value = group.group_name || '';
            document.getElementById('groupDescription').value = group.description || '';
            document.getElementById('groupIsActive').checked = group.is_active;
            modal.classList.add('active');
        }

        function closeGroupModal() {
            const modal = document.getElementById('groupModal');
            if (modal) {
                modal.classList.remove('active');
                // Reset readonly
                const groupIdInput = document.getElementById('groupGroupId');
                if (groupIdInput) {
                    groupIdInput.readOnly = false;
                }
            }
            const errorMsg = document.getElementById('groupErrorMessage');
            if (errorMsg) {
                errorMsg.style.display = 'none';
            }
        }

        async function handleGroupSubmit(e) {
            e.preventDefault();
            clearGroupError();

            const existingGroupId = document.getElementById('groupId').value;
            const groupId = document.getElementById('groupGroupId').value.trim();
            const groupName = document.getElementById('groupName').value.trim();
            const description = document.getElementById('groupDescription').value.trim();
            const isActive = document.getElementById('groupIsActive').checked;

            // Validasi format group ID
            if (!groupId.endsWith('@g.us')) {
                showGroupError('Group ID harus diakhiri dengan @g.us');
                return;
            }

            try {
                const url = 'api/bot/groups.php';
                let method, body;

                if (existingGroupId) {
                    // Update
                    method = 'PUT';
                    body = {
                        group_id: existingGroupId,
                        group_name: groupName || null,
                        description: description || null,
                        is_active: isActive
                    };
                } else {
                    // Create
                    method = 'POST';
                    body = {
                        group_id: groupId,
                        group_name: groupName || null,
                        description: description || null
                    };
                }

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });

                const data = await response.json();

                if (data.success) {
                    closeGroupModal();
                    loadGroups();
                } else {
                    showGroupError(data.error || 'Gagal menyimpan');
                }
            } catch (error) {
                showGroupError('Terjadi kesalahan: ' + error.message);
            }
        }

        async function deleteGroup(groupId) {
            if (!confirm('Yakin ingin menghapus group ini?')) return;

            try {
                const response = await fetch('api/bot/groups.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ group_id: groupId })
                });

                const data = await response.json();

                if (data.success) {
                    loadGroups();
                } else {
                    showGroupError(data.error || 'Gagal menghapus');
                }
            } catch (error) {
                showGroupError('Terjadi kesalahan: ' + error.message);
            }
        }

        function showGroupError(message) {
            const errorEl = document.getElementById('groupErrorMessage');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.style.display = 'block';
            }
        }

        function clearGroupError() {
            const errorEl = document.getElementById('groupErrorMessage');
            if (errorEl) {
                errorEl.style.display = 'none';
            }
        }

        // Users Management Functions
        let users = [];

        async function loadUsers() {
            try {
                const response = await fetch('api/admin/users.php');
                const data = await response.json();

                if (data.success) {
                    users = data.data || [];
                    renderUsers();
                } else {
                    showUserError(data.error || 'Gagal memuat data');
                }
            } catch (error) {
                showUserError('Terjadi kesalahan: ' + error.message);
            }
        }

        function renderUsers() {
            const tbody = document.getElementById('usersTableBody');
            
            if (!tbody) return;
            
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Tidak ada data</td></tr>';
                return;
            }

            tbody.innerHTML = users.map(user => {
                const createdDate = user.created_at ? new Date(user.created_at).toLocaleDateString('id-ID') : '-';
                return `
                    <tr>
                        <td>${user.id}</td>
                        <td><strong>${user.wa_name || '-'}</strong></td>
                        <td>${user.name || '-'}</td>
                        <td>${user.phone_number || '-'}</td>
                        <td>
                            <span class="status-badge ${user.isadmin ? 'status-active' : 'status-inactive'}">
                                ${user.isadmin ? 'Admin' : 'User'}
                            </span>
                        </td>
                        <td>${createdDate}</td>
                        <td>
                            <button class="btn btn-primary" onclick="openEditUserModal(${user.id})" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                            <button class="btn btn-danger" onclick="deleteUser(${user.id})" style="padding: 6px 12px; font-size: 12px;">Hapus</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function openAddUserModal() {
            const modal = document.getElementById('userModal');
            if (!modal) return;
            
            document.getElementById('userModalTitle').textContent = 'Tambah User';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('userPasswordGroup').style.display = 'block';
            document.getElementById('userPassword').required = true;
            document.getElementById('userIsAdmin').checked = false;
            modal.classList.add('active');
        }

        function openEditUserModal(id) {
            const user = users.find(u => u.id === id);
            if (!user) return;

            const modal = document.getElementById('userModal');
            if (!modal) return;

            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('userWaName').value = user.wa_name || '';
            document.getElementById('userName').value = user.name || '';
            document.getElementById('userPhoneNumber').value = user.phone_number || '';
            document.getElementById('userIsAdmin').checked = user.isadmin || false;
            document.getElementById('userPasswordGroup').style.display = 'none';
            document.getElementById('userPassword').required = false;
            document.getElementById('userPassword').value = '';
            modal.classList.add('active');
        }

        function closeUserModal() {
            const modal = document.getElementById('userModal');
            if (modal) {
                modal.classList.remove('active');
            }
            const errorMsg = document.getElementById('userErrorMessage');
            if (errorMsg) {
                errorMsg.style.display = 'none';
            }
        }

        async function handleUserSubmit(e) {
            e.preventDefault();
            clearUserError();

            const userId = document.getElementById('userId').value;
            const waName = document.getElementById('userWaName').value.trim();
            const name = document.getElementById('userName').value.trim();
            const phoneNumber = document.getElementById('userPhoneNumber').value.trim();
            const password = document.getElementById('userPassword').value;
            const isAdmin = document.getElementById('userIsAdmin').checked;

            if (!waName) {
                showUserError('WA Name tidak boleh kosong');
                return;
            }

            try {
                const url = 'api/admin/users.php';
                let method, body;

                if (userId) {
                    // Update
                    method = 'PUT';
                    body = {
                        id: parseInt(userId),
                        wa_name: waName,
                        name: name || null,
                        phone_number: phoneNumber || null,
                        isAdmin: isAdmin
                    };
                } else {
                    // Create
                    if (!password || password.length < 6) {
                        showUserError('Password minimal 6 karakter');
                        return;
                    }
                    method = 'POST';
                    body = {
                        wa_name: waName,
                        name: name || waName,
                        phone_number: phoneNumber || null,
                        password: password,
                        isAdmin: isAdmin
                    };
                }

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });

                const data = await response.json();

                if (data.success) {
                    closeUserModal();
                    loadUsers();
                } else {
                    showUserError(data.error || data.message || 'Gagal menyimpan');
                }
            } catch (error) {
                showUserError('Terjadi kesalahan: ' + error.message);
            }
        }

        async function deleteUser(id) {
            // Prevent delete current user
            <?php if (isset($_SESSION['user_id'])): ?>
            if (id == <?php echo $_SESSION['user_id']; ?>) {
                alert('Tidak dapat menghapus user yang sedang login');
                return;
            }
            <?php endif; ?>

            if (!confirm('Yakin ingin menghapus user ini?')) return;

            try {
                const response = await fetch('api/admin/users.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });

                const data = await response.json();

                if (data.success) {
                    loadUsers();
                } else {
                    showUserError(data.error || 'Gagal menghapus');
                }
            } catch (error) {
                showUserError('Terjadi kesalahan: ' + error.message);
            }
        }

        function showUserError(message) {
            const errorEl = document.getElementById('userErrorMessage');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.style.display = 'block';
            }
        }

        function clearUserError() {
            const errorEl = document.getElementById('userErrorMessage');
            if (errorEl) {
                errorEl.style.display = 'none';
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>
