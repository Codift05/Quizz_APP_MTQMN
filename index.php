<?php
require_once 'config/database.php';
require_once 'auth/auth.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kuis Al-Qur'an Interaktif</title>
    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    <style>
        .user-menu {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 1rem;
        }

        .user-menu a {
            color: white;
            text-decoration: none;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .user-menu .username {
            font-weight: bold;
        }

        .feature-card a {
            text-decoration: none;
            color: inherit;
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
            <?php if (isAdmin()): ?>
                <a href="admin/admin-panel.php">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>

    <!-- Landing Page -->
    <div id="landing-page" class="container">
        <div class="landing-header">
            <div class="logo"><img src="uploads/images/logo_qiza.png" alt=""></div>
            <h1 class="landing-title">Kuis Al-Qur'an Interaktif</h1>
            <p class="landing-subtitle">
                Uji pengetahuanmu tentang Al-Qur'an dengan kuis interaktif
                yang menarik. Jawab soal, dapatkan skor, dan pelajari
                ayat-ayat pilihan!
            </p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <a href="history.php">
                    <div class="feature-icon">📊</div>
                    <div class="feature-title">Riwayat Hasil Kuis</div>
                    <div class="feature-desc">
                        Lihat detail riwayat hasil kuis yang sudah kamu kerjakan
                    </div>
                </a>
            </div>
            <div class="feature-card">
                <a href="ranking.php">
                    <div class="feature-icon">🏆</div>
                    <div class="feature-title">Peringkat</div>
                    <div class="feature-desc">
                        Bandingkan skor dengan peserta lain
                    </div>
                </a>
            </div>
        </div>

        <div style="margin-top: 2rem">
            <button class="btn-success" onclick="startQuiz()">
                🚀 Mulai Kuis Sekarang
            </button>
        </div>
    </div>

    <!-- Quiz Page -->
    <div id="quiz-page" class="container hidden">
        <div class="quiz-header">
            <h2>Kuis Al-Qur'an</h2>
            <div class="quiz-info">
                <span id="question-counter">Soal 1 dari 10</span>
            </div>
        </div>

        <div class="quiz-progress">
            <div class="quiz-progress-bar" id="progress-bar"></div>
        </div>

        <div class="question-container">
            <div class="question-text" id="question-text"></div>
            <div class="arabic-text" id="arabic-text"></div>
        </div>

        <div class="options-container" id="options-container"></div>

        <input
            type="text"
            class="text-input hidden"
            id="text-input"
            placeholder="Masukkan jawaban Anda..." />

        <div class="feedback" id="feedback"></div>

        <div style="text-align: center; margin-top: 2rem">
            <button
                class="btn-primary"
                id="submit-btn"
                onclick="submitAnswer()">
                Submit Jawaban
            </button>
            <button
                class="btn-primary hidden"
                id="next-btn"
                onclick="nextQuestion()">
                Soal Berikutnya
            </button>
        </div>
    </div>

    <!-- Result Page -->
    <div id="result-page" class="container hidden">
        <div class="result-header">
            <h2>🎉 Hasil Kuis Anda</h2>
        </div>

        <div class="score-display">
            <div class="score-number" id="final-score">0</div>
            <div class="score-text" id="score-percentage">0%</div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="correct-answers">0</div>
                <div class="stat-label">Jawaban Benar</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="incorrect-answers">0</div>
                <div class="stat-label">Jawaban Salah</div>
            </div>
        </div>

        <div class="wrong-answers" id="wrong-answers-section">
            <h3>📝 Review Jawaban Salah</h3>
            <div id="wrong-answers-list"></div>
        </div>

        <div class="nav-buttons">
            <button class="btn-success" onclick="restartQuiz()">
                🔄 Ulangi Kuis
            </button>
            <button class="btn-primary" onclick="backToHome()">
                🏠 Kembali ke Beranda
            </button>
        </div>
    </div>

    <script src="main.js"></script>
</body>

</html>