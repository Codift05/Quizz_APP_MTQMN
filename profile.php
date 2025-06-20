<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Pastikan user sudah login
requireLogin();

// Ambil data user
$user = getCurrentUser();
$success_message = '';
$error_message = '';

// Handle update username
if (isset($_POST['update_username'])) {
    $new_username = trim($_POST['username']);

    if (empty($new_username)) {
        $error_message = 'Username tidak boleh kosong';
    } else {
        try {
            // Cek apakah username sudah digunakan
            $stmt = $db->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
            $stmt->bindValue(':username', $new_username);
            $stmt->bindValue(':id', $_SESSION['user_id']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error_message = 'Username sudah digunakan oleh pengguna lain';
            } else {
                // Update username
                $stmt = $db->prepare("UPDATE users SET username = :username WHERE id = :id");
                $stmt->bindValue(':username', $new_username);
                $stmt->bindValue(':id', $_SESSION['user_id']);
                $stmt->execute();

                // Update session
                $_SESSION['username'] = $new_username;
                $user['username'] = $new_username;
                $success_message = 'Username berhasil diperbarui';
            }
        } catch (PDOException $e) {
            $error_message = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle update password
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Verifikasi password saat ini
        $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->bindValue(':id', $_SESSION['user_id']);
        $stmt->execute();
        $current_hash = $stmt->fetchColumn();

        if (!password_verify($current_password, $current_hash)) {
            $error_message = 'Password saat ini tidak valid';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'Password baru harus minimal 6 karakter';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Password baru dan konfirmasi password tidak cocok';
        } else {
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->bindValue(':password', $new_hash);
            $stmt->bindValue(':id', $_SESSION['user_id']);
            $stmt->execute();

            $success_message = 'Password berhasil diperbarui';
        }
    } catch (PDOException $e) {
        $error_message = 'Error: ' . $e->getMessage();
    }
}

// Handle update profile image
if (isset($_POST['update_profile_image'])) {
    // Validasi file
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'Terjadi kesalahan saat mengunggah file';
    } else {
        $file = $_FILES['profile_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed_types)) {
            $error_message = 'Jenis file tidak didukung. Gunakan JPG, PNG, atau GIF';
        } elseif ($file['size'] > $max_size) {
            $error_message = 'Ukuran file terlalu besar. Maksimum 2MB';
        } else {
            // Buat direktori jika belum ada
            $upload_dir = 'uploads/profile_images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $filename = uniqid('profile_') . '_' . time() . '_' . $_SESSION['user_id'];
            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filepath = $upload_dir . $filename . '.' . $file_ext;

            // Upload file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                try {
                    // Hapus foto lama jika ada
                    if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                        unlink($user['profile_image']);
                    }

                    // Update database
                    $stmt = $db->prepare("UPDATE users SET profile_image = :profile_image WHERE id = :id");
                    $stmt->bindValue(':profile_image', $filepath);
                    $stmt->bindValue(':id', $_SESSION['user_id']);
                    $stmt->execute();

                    $user['profile_image'] = $filepath;
                    $success_message = 'Foto profil berhasil diperbarui';
                } catch (PDOException $e) {
                    $error_message = 'Error: ' . $e->getMessage();
                }
            } else {
                $error_message = 'Gagal mengunggah file';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - Kuis Al-Qur'an</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-page {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            max-width: 900px;
            margin: 2rem auto;
            border-radius: 20px;
            overflow: visible;
            /* Ubah dari hidden ke visible agar konten tidak terpotong */
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.8s ease-out;
        }

        .profile-cover {
            height: 150px;
            background: linear-gradient(135deg, #3498db, #8e44ad);
            position: relative;
            overflow: visible;
            /* Memastikan konten di luar cover tetap terlihat */
        }

        .profile-main {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            padding: 2rem;
        }

        /* Perbaiki posisi sidebar dan avatar */
        .profile-sidebar {
            position: relative;
            padding-top: 70px;
            /* Beri ruang untuk avatar */
        }

        /* Tambahkan container khusus untuk avatar dan info user */
        .user-profile-section {
            position: relative;
            margin-bottom: 2rem;
        }

        /* Perbaiki posisi avatar untuk tidak terpotong */
        .profile-avatar-container {
            position: absolute;
            top: -130px;
            /* Naik ke atas agar tidak terpotong */
            left: 0;
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .profile-avatar-wrapper {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            padding: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            z-index: 10;
            /* Pastikan di atas elemen lain */
        }

        .profile-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #3498db;
        }

        .edit-avatar-btn {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background: #3498db;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: none;
            font-size: 0.85rem;
            width: auto;
            z-index: 10;
        }

        .edit-avatar-btn:hover {
            background: #2980b9;
            transform: translateX(-50%) translateY(-2px);
        }

        .profile-name {
            text-align: center;
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .user-role {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin: 0 auto 1rem;
            text-align: center;
        }

        .user-role.admin {
            background: #e74c3c;
        }

        .user-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #3498db;
        }

        .info-item {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-item i {
            margin-right: 10px;
            color: #3498db;
            width: 20px;
            text-align: center;
        }

        .info-label {
            font-weight: bold;
            color: #7f8c8d;
            margin-right: 10px;
            width: 60px;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }

        .nav-buttons {
            text-align: center;
            margin-top: 1rem;
        }

        /* Style untuk tab container */
        .tab-container {
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .tab-header {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .tab-btn {
            flex: 1;
            padding: 1rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #7f8c8d;
            transition: all 0.3s;
            position: relative;
        }

        .tab-btn.active {
            color: #3498db;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: #3498db;
        }

        .tab-content {
            padding: 2rem;
            background: white;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .btn-submit {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            text-decoration: none;
            text-align: center;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .file-input-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .file-input {
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        /* Style untuk preview foto saat ini */
        .current-avatar-preview {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #3498db;
        }

        .current-avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-placeholder {
            width: 100%;
            height: 100%;
            background-color: #f0f0f0;
            color: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
        }

        /* Style untuk nama file yang dipilih */
        .selected-file-name {
            margin-top: 10px;
            padding: 5px 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-size: 0.9rem;
            color: #2c3e50;
            display: none;
        }

        @media (max-width: 768px) {
            .profile-main {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                padding-top: 90px;
            }

            .profile-avatar-container {
                top: -110px;
            }

            .profile-name {
                text-align: center;
            }

            .user-role {
                display: block;
                margin: 0 auto 1rem;
                width: fit-content;
            }

            .info-item {
                flex-direction: row;
                align-items: center;
            }
        }
    </style>
</head>

<body>
    <div class="background-pattern"></div>

    <div class="profile-page">
        <div class="profile-cover"></div>

        <div class="profile-main">
            <div class="profile-sidebar">
                <div class="user-profile-section">
                    <div class="profile-avatar-container">
                        <div class="profile-avatar-wrapper">
                            <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                                <img src="<?php echo $user['profile_image']; ?>" alt="Profile" class="profile-avatar">
                            <?php else: ?>
                                <div class="profile-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                            <?php endif; ?>
                            <button type="button" class="edit-avatar-btn" onclick="document.querySelector('.tab-btn[data-tab=\'photo\']').click()">
                                <i class="fas fa-camera"></i> Edit
                            </button>
                        </div>
                    </div>

                    <div class="profile-name">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </div>
                </div>

                <div class="user-info">
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>

                    <div class="info-item">
                        <i class="fas fa-user-shield"></i>
                        <span class="info-label">Role:</span>
                        <span class="info-value"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                </div>

                <div class="nav-buttons">
                    <a href="index.php" class="btn-primary">Kembali ke Beranda</a>
                </div>
            </div>

            <div class="profile-content">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="tab-container">
                    <div class="tab-header">
                        <button type="button" class="tab-btn active" data-tab="username">Username</button>
                        <button type="button" class="tab-btn" data-tab="password">Password</button>
                        <button type="button" class="tab-btn" data-tab="photo">Foto Profil</button>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane active" id="username-tab">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="username" class="form-label">Username Baru</label>
                                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>

                                <button type="submit" name="update_username" class="btn-submit">Simpan Username</button>
                            </form>
                        </div>

                        <div class="tab-pane" id="password-tab">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="current_password" class="form-label">Password Saat Ini</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="new_password" class="form-label">Password Baru</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>

                                <button type="submit" name="update_password" class="btn-submit">Simpan Password</button>
                            </form>
                        </div>

                        <div class="tab-pane" id="photo-tab">
                            <form method="post" action="" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label class="form-label">Foto Profil Saat Ini</label>
                                    <div class="current-avatar-preview">
                                        <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                                            <img src="<?php echo $user['profile_image']; ?>" alt="Current Profile Photo">
                                        <?php else: ?>
                                            <div class="avatar-placeholder"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="profile_image_upload" class="form-label">Unggah Foto Baru</label>
                                    <div class="file-input-wrapper">
                                        <label for="profile_image_upload" class="file-input-label">
                                            <i class="fas fa-upload"></i> Pilih Foto
                                        </label>
                                        <input type="file" id="profile_image_upload" name="profile_image" class="file-input" accept="image/jpeg, image/png, image/gif" required>
                                    </div>
                                    <div id="file-selected-name" class="selected-file-name"></div>
                                    <small style="color: #6c757d; display: block; margin-top: 0.5rem;">Format yang didukung: JPG, PNG, GIF. Ukuran maksimum: 2MB.</small>
                                </div>

                                <button type="submit" name="update_profile_image" class="btn-submit">Unggah & Simpan Foto</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <script>
        // Tab functionality
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons and panes
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));

                // Add active class to clicked button
                button.classList.add('active');

                // Show corresponding tab content
                const tabId = button.getAttribute('data-tab');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });

        // Show file name when selected
        document.getElementById('profile_image_upload').addEventListener('change', function() {
            const fileNameDisplay = document.getElementById('file-selected-name');
            if (this.files.length > 0) {
                fileNameDisplay.textContent = `File dipilih: ${this.files[0].name}`;
                fileNameDisplay.style.display = 'block';
            } else {
                fileNameDisplay.textContent = '';
                fileNameDisplay.style.display = 'none';
            }
        });
    </script>
</body>

</html>