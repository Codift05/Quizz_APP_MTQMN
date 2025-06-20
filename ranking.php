<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Tentukan zona waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

// Hitung tanggal untuk minggu ini (Senin-Minggu)
$today = new DateTime();
$dayOfWeek = $today->format('N'); // 1 (Senin) sampai 7 (Minggu)
$daysToLastMonday = $dayOfWeek - 1;

$lastMonday = clone $today;
$lastMonday->sub(new DateInterval("P{$daysToLastMonday}D"));
$lastMonday->setTime(0, 0, 0);

$nextMonday = clone $lastMonday;
$nextMonday->add(new DateInterval("P7D"));

$weekStart = $lastMonday->format('Y-m-d H:i:s');
$weekEnd = $nextMonday->format('Y-m-d H:i:s');

// Ambil data semua user untuk ranking minggu ini
try {
    // Query untuk ranking berdasarkan jumlah quiz dan persentase benar
    $rankingQuery = "
        SELECT 
            u.id, 
            u.username, 
            u.profile_image,
            COUNT(r.id) as quiz_count,
            ROUND(AVG(r.percentage), 1) as avg_percentage,
            SUM(r.score) as total_correct,
            SUM(r.total_questions) as total_questions
        FROM 
            users u
        LEFT JOIN 
            results r ON u.id = r.user_id AND r.completed_at BETWEEN :week_start AND :week_end
        WHERE 
            u.role = 'user'
        GROUP BY 
            u.id, u.username
        HAVING 
            quiz_count > 0
        ORDER BY 
            quiz_count DESC, avg_percentage DESC
    ";

    $stmt = $db->prepare($rankingQuery);
    $stmt->bindValue(':week_start', $weekStart);
    $stmt->bindValue(':week_end', $weekEnd);
    $stmt->execute();
    $rankings = $stmt->fetchAll();

    // Tambahkan ranking ke hasil query
    $rank = 1;
    $userRank = 0;
    $userInTopTen = false;
    $currentUserData = null;

    foreach ($rankings as $key => $user) {
        $rankings[$key]['rank'] = $rank++;

        // Cek apakah ini user yang sedang login
        if (isLoggedIn() && $user['id'] == $_SESSION['user_id']) {
            $userRank = $rankings[$key]['rank'];
            $userInTopTen = $userRank <= 10;
            $currentUserData = $rankings[$key];
        }
    }

    // Ambil hanya 10 teratas untuk ditampilkan
    $topTen = array_slice($rankings, 0, 10);
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peringkat - Kuis Al-Qur'an</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    <style>
        .ranking-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            max-width: 800px;
            margin: 5rem auto 2rem;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }

        .ranking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .ranking-title {
            font-size: 1.8rem;
            color: #2c3e50;
            font-weight: 700;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .week-info {
            margin-bottom: 1rem;
            text-align: center;
        }

        .week-info p {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .reset-countdown {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 20px;
            border: 1px dashed #e9ecef;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #e74c3c;
        }

        .ranking-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .ranking-table th {
            background: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
        }

        .ranking-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .ranking-table tr:hover {
            background: #f8f9fa;
        }

        .ranking-table tr.current-user {
            background: #ebf8ff;
        }

        .rank {
            font-weight: bold;
            font-size: 1.1rem;
            color: #2c3e50;
            width: 50px;
            text-align: center;
        }

        .rank-1 {
            color: #f39c12;
        }

        .rank-2 {
            color: #7f8c8d;
        }

        .rank-3 {
            color: #e67e22;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
        }

        .quiz-count {
            font-weight: 600;
            color: #3498db;
        }

        .accuracy {
            font-weight: 600;
        }

        .accuracy-high {
            color: #2ecc71;
        }

        .accuracy-medium {
            color: #3498db;
        }

        .accuracy-low {
            color: #e74c3c;
        }

        .current-user-rank {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin: 1.5rem 0;
            border-left: 4px solid #3498db;
        }

        .not-logged-in {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 1.5rem 0;
            color: #7f8c8d;
        }

        .empty-rankings {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 10px;
            color: #7f8c8d;
        }

        /* Pastikan menu navigasi berada di atas */
        .user-menu {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            z-index: 10;
        }

        .user-menu a {
            color: white;
            text-decoration: none;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        /* Responsif untuk mobile */
        @media (max-width: 768px) {
            .ranking-container {
                margin-top: 6rem;
                padding: 1.5rem;
            }

            .ranking-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .ranking-table {
                font-size: 0.9rem;
            }

            .rank {
                width: 40px;
                font-size: 1rem;
            }

            .user-avatar,
            .user-avatar-placeholder {
                width: 35px;
                height: 35px;
            }

            .user-menu {
                flex-direction: column;
                align-items: flex-end;
            }

            .user-info {
                gap: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="background-pattern"></div>

    <!-- User Menu -->
    <div class="user-menu">
        <?php if (isLoggedIn()): ?>
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
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
            <a href="index.php">Beranda</a>
        <?php endif; ?>
    </div>

    <div class="ranking-container">
        <div class="ranking-header">
            <h1 class="ranking-title">🏆 Peringkat Mingguan</h1>
        </div>

        <div class="week-info">
            <p>Peringkat untuk minggu <?php echo $lastMonday->format('d M Y'); ?> hingga <?php echo (clone $nextMonday)->sub(new DateInterval('P1D'))->format('d M Y'); ?></p>

            <?php
            // Hitung sisa waktu hingga reset
            $now = new DateTime();
            $interval = $now->diff($nextMonday);
            $daysLeft = $interval->days;
            $hoursLeft = $interval->h;
            $minutesLeft = $interval->i;
            ?>

            <div class="reset-countdown">
                Peringkat akan direset dalam:
                <?php
                if ($daysLeft > 0) {
                    echo $daysLeft . ' hari ' . $hoursLeft . ' jam';
                } elseif ($hoursLeft > 0) {
                    echo $hoursLeft . ' jam ' . $minutesLeft . ' menit';
                } else {
                    echo $minutesLeft . ' menit';
                }
                ?>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div style="color: red; margin-bottom: 1rem;"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($rankings)): ?>
            <div class="empty-rankings">
                <p>Belum ada data peringkat untuk minggu ini.</p>
                <a href="index.php" class="btn-success" style="margin-top: 1rem; display: inline-block;">Mulai Quiz Sekarang</a>
            </div>
        <?php else: ?>
            <?php if (isLoggedIn() && !$userInTopTen && $userRank > 0): ?>
                <div class="current-user-rank">
                    <p>Peringkat Anda saat ini:</p>
                    <table class="ranking-table">
                        <tr class="current-user">
                            <td class="rank"><?php echo $userRank; ?></td>
                            <td>
                                <div class="user-info">
                                    <?php if (!empty($currentUser['profile_image']) && file_exists($currentUser['profile_image'])): ?>
                                        <img src="<?php echo $currentUser['profile_image']; ?>" alt="Profile" class="user-avatar">
                                    <?php else: ?>
                                        <div class="user-avatar-placeholder"><?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?></div>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($currentUser['username']); ?></span>
                                </div>
                            </td>
                            <td class="quiz-count"><?php echo $currentUserData['quiz_count']; ?> Quiz</td>
                            <td class="accuracy <?php
                                                echo $currentUserData['avg_percentage'] >= 80 ? 'accuracy-high' : ($currentUserData['avg_percentage'] >= 60 ? 'accuracy-medium' : 'accuracy-low');
                                                ?>">
                                <?php echo $currentUserData['avg_percentage']; ?>%
                            </td>
                        </tr>
                    </table>
                </div>
            <?php elseif (!isLoggedIn()): ?>
                <div class="not-logged-in">
                    <p>Login untuk melihat peringkat Anda dalam leaderboard mingguan.</p>
                    <a href="login.php" class="btn-primary" style="margin-top: 0.5rem; display: inline-block;">Login Sekarang</a>
                </div>
            <?php endif; ?>

            <h2 style="margin-top: 1.5rem;">Top 10 Pemain</h2>
            <table class="ranking-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Rank</th>
                        <th>Pemain</th>
                        <th>Quiz</th>
                        <th>Akurasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topTen as $user): ?>
                        <tr <?php echo (isLoggedIn() && $user['id'] == $_SESSION['user_id']) ? 'class="current-user"' : ''; ?>>
                            <td class="rank rank-<?php echo $user['rank'] <= 3 ? $user['rank'] : ''; ?>">
                                <?php echo $user['rank']; ?>
                            </td>
                            <td>
                                <div class="user-info">
                                    <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                                        <img src="<?php echo $user['profile_image']; ?>" alt="Profile" class="user-avatar">
                                    <?php else: ?>
                                        <div class="user-avatar-placeholder"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                            </td>
                            <td class="quiz-count"><?php echo $user['quiz_count']; ?> Quiz</td>
                            <td class="accuracy <?php
                                                echo $user['avg_percentage'] >= 80 ? 'accuracy-high' : ($user['avg_percentage'] >= 60 ? 'accuracy-medium' : 'accuracy-low');
                                                ?>">
                                <?php echo $user['avg_percentage']; ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div style="margin-top: 2rem; text-align: center;">
            <a href="index.php" class="btn-success">Mulai Quiz Sekarang</a>
        </div>
    </div>

    <script>
        // Untuk update countdown secara real-time (opsional)
        function updateCountdown() {
            const nextReset = new Date('<?php echo $nextMonday->format('Y-m-d\TH:i:s'); ?>');
            const now = new Date();
            const diff = nextReset - now;

            if (diff <= 0) {
                location.reload(); // Reload halaman jika sudah waktunya reset
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            let countdownText = '';
            if (days > 0) {
                countdownText = `${days} hari ${hours} jam`;
            } else if (hours > 0) {
                countdownText = `${hours} jam ${minutes} menit`;
            } else {
                countdownText = `${minutes} menit`;
            }

            document.querySelector('.reset-countdown').innerHTML = `Peringkat akan direset dalam: ${countdownText}`;
        }

        // Update countdown setiap menit
        setInterval(updateCountdown, 60000);
    </script>
</body>

</html>