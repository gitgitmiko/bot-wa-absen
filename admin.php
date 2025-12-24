<?php
require_once __DIR__ . '/config/bootstrap.php';
session_start();

// Cek session dan admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!($_SESSION['isAdmin'] ?? false)) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Bot Absensi WhatsApp</title>
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .admin-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-header h1 {
            margin: 0;
            color: #333;
        }
        .admin-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .commands-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .commands-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .commands-header h2 {
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            margin: 0;
        }
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-group-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group-checkbox input {
            width: auto;
        }
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>⚙️ Admin Panel - Kelola Command</h1>
        <div class="admin-actions">
            <button class="btn btn-secondary" onclick="window.location.href='index.php'">Kembali ke Dashboard</button>
            <button class="btn btn-primary" onclick="openAddModal()">+ Tambah Command</button>
        </div>
    </div>

    <div class="commands-container">
        <div class="commands-header">
            <h2>Daftar Command</h2>
        </div>
        <div class="error-message" id="errorMessage"></div>
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
                <h3 id="modalTitle">Tambah Command</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="commandForm" onsubmit="handleSubmit(event)">
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
                    <button type="button" class="btn btn-secondary" onclick="closeModal()" style="flex: 1;">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
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
                        <button class="btn btn-primary" onclick="openEditModal(${cmd.id})" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                        <button class="btn btn-danger" onclick="deleteCommand(${cmd.id})" style="padding: 6px 12px; font-size: 12px;">Hapus</button>
                    </td>
                </tr>
            `).join('');
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Command';
            document.getElementById('commandForm').reset();
            document.getElementById('commandId').value = '';
            document.getElementById('commandIsActive').checked = true;
            document.getElementById('commandModal').classList.add('active');
        }

        function openEditModal(id) {
            const cmd = commands.find(c => c.id === id);
            if (!cmd) return;

            document.getElementById('modalTitle').textContent = 'Edit Command';
            document.getElementById('commandId').value = cmd.id;
            document.getElementById('commandText').value = cmd.command;
            document.getElementById('commandDescription').value = cmd.description || '';
            document.getElementById('commandIsActive').checked = cmd.is_active;
            document.getElementById('commandModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('commandModal').classList.remove('active');
            document.getElementById('errorMessage').style.display = 'none';
        }

        async function handleSubmit(e) {
            e.preventDefault();
            clearError();

            const id = document.getElementById('commandId').value;
            const command = document.getElementById('commandText').value.trim();
            const description = document.getElementById('commandDescription').value.trim();
            const isActive = document.getElementById('commandIsActive').checked;

            if (!command.startsWith('/')) {
                showError('Command harus dimulai dengan /');
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
                    closeModal();
                    loadCommands();
                } else {
                    showError(data.error || 'Gagal menyimpan');
                }
            } catch (error) {
                showError('Terjadi kesalahan: ' + error.message);
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
                    showError(data.error || 'Gagal menghapus');
                }
            } catch (error) {
                showError('Terjadi kesalahan: ' + error.message);
            }
        }

        function showError(message) {
            const errorEl = document.getElementById('errorMessage');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }

        function clearError() {
            document.getElementById('errorMessage').style.display = 'none';
        }

        // Load commands on page load
        loadCommands();
    </script>
</body>
</html>

