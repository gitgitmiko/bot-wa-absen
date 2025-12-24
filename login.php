<?php
require_once __DIR__ . '/config/bootstrap.php';
session_start();

// Jika sudah login, redirect ke index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bot Absensi WhatsApp</title>
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            margin: 0 0 10px 0;
            color: #1e3a5f;
            font-size: 28px;
            font-weight: 600;
        }
        .login-header p {
            color: #6b7280;
            margin: 0;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
            background: #fff;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1e3a5f;
            box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.1);
        }
        .btn-login, .btn-register, .btn-reset {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 10px;
        }
        .btn-login {
            background: #1e3a5f;
            color: white;
            border: 1px solid #0d2841;
        }
        .btn-login:hover {
            background: #0d2841;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .btn-register {
            background: #f5f7fa;
            color: #1e3a5f;
            border: 1px solid #e0e0e0;
        }
        .btn-register:hover {
            background: #e8edf3;
            border-color: #cbd5e0;
        }
        .btn-reset {
            background: #c53030;
            color: white;
            border: 1px solid #9b2c2c;
        }
        .btn-reset:hover {
            background: #9b2c2c;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .error-message, .success-message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        .error-message {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .success-message {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        .form-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .form-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
        }
        .form-tab.active {
            color: #1e3a5f;
            border-bottom-color: #1e3a5f;
            font-weight: 600;
        }
        .form-content {
            display: none;
        }
        .form-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Login</h1>
            <p>Bot Absensi WhatsApp</p>
        </div>

        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>

        <div class="form-tabs">
            <div class="form-tab active" onclick="switchTab('login')">Login</div>
            <div class="form-tab" onclick="switchTab('register')">Daftar</div>
            <div class="form-tab" onclick="switchTab('reset')">Reset Password</div>
        </div>

        <!-- Login Form -->
        <div id="loginForm" class="form-content active">
            <form onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label>Username (WA Name)</label>
                    <input type="text" id="loginWaName" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="loginPassword" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>

        <!-- Register Form -->
        <div id="registerForm" class="form-content">
            <form onsubmit="handleRegister(event)">
                <div class="form-group">
                    <label>Username (WA Name)</label>
                    <input type="text" id="registerWaName" required>
                </div>
                <div class="form-group">
                    <label>Password (min. 6 karakter)</label>
                    <input type="password" id="registerPassword" required minlength="6">
                </div>
                <div class="form-group">
                    <label>No. HP (Opsional)</label>
                    <input type="text" id="registerPhone">
                </div>
                <button type="submit" class="btn-register">Daftar</button>
            </form>
        </div>

        <!-- Reset Password Form -->
        <div id="resetForm" class="form-content">
            <form onsubmit="handleResetPassword(event)">
                <div class="form-group">
                    <label>Username (WA Name)</label>
                    <input type="text" id="resetWaName" required>
                </div>
                <div class="form-group">
                    <label>Password Baru (min. 6 karakter)</label>
                    <input type="password" id="resetPassword" required minlength="6">
                </div>
                <button type="submit" class="btn-reset">Reset Password</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Update tabs
            document.querySelectorAll('.form-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');

            // Update forms
            document.querySelectorAll('.form-content').forEach(f => f.classList.remove('active'));
            document.getElementById(tab + 'Form').classList.add('active');

            // Clear messages
            clearMessages();
        }

        function showError(message) {
            const errorEl = document.getElementById('errorMessage');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
            document.getElementById('successMessage').style.display = 'none';
        }

        function showSuccess(message) {
            const successEl = document.getElementById('successMessage');
            successEl.textContent = message;
            successEl.style.display = 'block';
            document.getElementById('errorMessage').style.display = 'none';
        }

        function clearMessages() {
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('successMessage').style.display = 'none';
        }

        async function handleLogin(e) {
            e.preventDefault();
            clearMessages();

            const waName = document.getElementById('loginWaName').value.trim();
            const password = document.getElementById('loginPassword').value;

            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'login',
                        wa_name: waName,
                        password: password
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess('Login berhasil! Mengalihkan...');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    if (data.needs_reset) {
                        showError(data.message + ' Silakan gunakan tab Reset Password.');
                        setTimeout(() => {
                            switchTab('reset');
                            document.getElementById('resetWaName').value = waName;
                        }, 2000);
                    } else {
                        showError(data.message || 'Login gagal');
                    }
                }
            } catch (error) {
                showError('Terjadi kesalahan: ' + error.message);
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            clearMessages();

            const waName = document.getElementById('registerWaName').value.trim();
            const password = document.getElementById('registerPassword').value;
            const phone = document.getElementById('registerPhone').value.trim();

            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'register',
                        wa_name: waName,
                        password: password,
                        phone_number: phone || null
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess('Registrasi berhasil! Mengalihkan...');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    showError(data.message || 'Registrasi gagal');
                }
            } catch (error) {
                showError('Terjadi kesalahan: ' + error.message);
            }
        }

        async function handleResetPassword(e) {
            e.preventDefault();
            clearMessages();

            const waName = document.getElementById('resetWaName').value.trim();
            const newPassword = document.getElementById('resetPassword').value;

            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'reset_password',
                        wa_name: waName,
                        new_password: newPassword
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess('Password berhasil direset! Mengalihkan...');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    showError(data.message || 'Reset password gagal');
                }
            } catch (error) {
                showError('Terjadi kesalahan: ' + error.message);
            }
        }
    </script>
</body>
</html>

