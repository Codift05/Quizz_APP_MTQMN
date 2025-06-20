<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Pastikan user sudah login
requireLogin();

// Ambil ID user yang login
$userId = $_SESSION['user_id'];

// Ambil data riwayat quiz
try {
    $stmt = $db->prepare("SELECT * FROM results WHERE user_id = :user_id ORDER BY completed_at DESC");
    $stmt->bindValue(':user_id', $userId);
    $stmt->execute();
    $quizHistory = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Quiz - Kuis Al-Qur'an</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .history-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            max-width: 800px;
            margin: 5rem auto 2rem;
            /* Ubah dari 2rem menjadi 5rem di bagian atas */
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            /* Tambahkan position relative */
            z-index: 1;
            /* Tambahkan z-index rendah */
        }

        /* Pastikan user-menu memiliki z-index lebih tinggi */
        .user-menu {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            z-index: 10;
            /* Tambahkan z-index lebih tinggi */
        }

        /* CSS lainnya tetap sama */
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .history-title {
            font-size: 1.8rem;
            color: #2c3e50;
            font-weight: 700;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .history-table th {
            background: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
        }

        .history-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e9ecef;
        }

        .history-table tr:hover {
            background: #f8f9fa;
        }

        .score-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .score-high {
            background: #2ecc71;
        }

        .score-medium {
            background: #3498db;
        }

        .score-low {
            background: #e74c3c;
        }

        .no-history {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 10px;
            color: #7f8c8d;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* User menu styles */
        .user-menu a {
            color: white;
            text-decoration: none;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }

        .user-menu a:hover {
            background: rgba(0, 0, 0, 0.5);
        }

        /* Styling untuk avatar user */
        .user-menu-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-menu-avatar-placeholder {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #27ae60;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .username {
            color: white;
            font-weight: bold;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Responsive styling */
        @media (max-width: 768px) {
            .history-container {
                margin-top: 6rem;
                /* Tambahkan lebih banyak ruang di atas pada layar kecil */
            }

            .user-menu {
                flex-direction: column;
                align-items: flex-end;
            }

            .history-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .stats-summary {
                grid-template-columns: 1fr;
            }

            .history-table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="background-pattern"></div>

    <!-- User Menu -->
    <div class="user-menu">
        <?php
        $currentUser = getCurrentUser();
        $profileImage = !empty($currentUser['profile_image']) && file_exists($currentUser['profile_image'])
            ? $currentUser['profile_image']
            : null;
        ?>
        <?php if ($profileImage): ?>
            <a href="profile.php" class="avatar-link"><img src="<?php echo $profileImage; ?>" alt="Profile" class="user-menu-avatar"></a>
        <?php else: ?>
            <a href="profile.php" class="avatar-link">
                <div class="user-menu-avatar-placeholder"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            </a>
        <?php endif; ?>
        <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <a href="index.php">Beranda</a>
        <?php if (isAdmin()): ?>
            <a href="admin/admin-panel.php">Admin Panel</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>

    <div class="history-container">
        <div class="history-header">
            <h1 class="history-title">Riwayat Quiz</h1>
            <a href="index.php" class="btn-primary">Kembali ke Beranda</a>
        </div>

        <!-- Konten riwayat tetap sama seperti sebelumnya -->
        <?php if (isset($error)): ?>
            <div style="color: red; margin-bottom: 1rem;"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($quizHistory)): ?>
            <div class="stats-summary">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($quizHistory); ?></div>
                    <div class="stat-label">Total Quiz</div>
                </div>

                <?php
                $totalScore = 0;
                $highestScore = 0;

                foreach ($quizHistory as $history) {
                    $totalScore += $history['percentage'];
                    $highestScore = max($highestScore, $history['percentage']);
                }

                $averageScore = count($quizHistory) > 0 ? round($totalScore / count($quizHistory), 1) : 0;
                ?>

                <div class="stat-card">
                    <div class="stat-number"><?php echo $highestScore; ?>%</div>
                    <div class="stat-label">Skor Tertinggi</div>
                </div>

                <div class="stat-card">
                    <div class="stat-number"><?php echo $averageScore; ?>%</div>
                    <div class="stat-label">Rata-rata Skor</div>
                </div>
            </div>

            <table class="history-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Skor</th>
                        <th>Persentase</th>
                        <th>Hasil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quizHistory as $history): ?>
                        <tr>
                            <td><?php echo date('d M Y H:i', strtotime($history['completed_at'])); ?></td>
                            <td><?php echo $history['score'] . '/' . $history['total_questions']; ?></td>
                            <td><?php echo $history['percentage']; ?>%</td>
                            <td>
                                <?php
                                if ($history['percentage'] >= 80) {
                                    echo '<span class="score-badge score-high">Sangat Baik</span>';
                                } elseif ($history['percentage'] >= 60) {
                                    echo '<span class="score-badge score-medium">Baik</span>';
                                } else {
                                    echo '<span class="score-badge score-low">Perlu Ditingkatkan</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-history">
                <p>Anda belum pernah menyelesaikan quiz apapun.</p>
                <a href="index.php" class="btn-success" style="margin-top: 1rem; display: inline-block;">Mulai Quiz Sekarang</a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>