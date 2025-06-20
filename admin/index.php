<?php
// Include database connection
require_once '../config/database.php';

require_once '../auth/auth.php';

// Check if user is admin
requireAdmin();

// Get statistics
try {
    // Total questions
    $stmt = $db->query("SELECT COUNT(*) FROM questions");
    $totalQuestions = $stmt->fetchColumn();

    // Recent quiz results
    $stmt = $db->query("SELECT * FROM results ORDER BY completed_at DESC LIMIT 10");
    $recentResults = $stmt->fetchAll();

    // Average score
    $stmt = $db->query("SELECT AVG(percentage) as avg_score FROM results");
    $avgScore = round($stmt->fetchColumn(), 1);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kuis Al-Qur'an</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-container {
            background: white;
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .admin-title {
            font-size: 1.8rem;
            color: #2c3e50;
        }

        .admin-nav {
            display: flex;
            gap: 1rem;
        }

        .admin-nav a {
            color: #3498db;
            text-decoration: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #3498db;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #7f8c8d;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .data-table th {
            background: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
        }

        .data-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
        }

        .section-title {
            margin: 2rem 0 1rem;
            color: #2c3e50;
            font-size: 1.5rem;
        }

        .data-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="background-pattern"></div>

    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Admin Dashboard</h1>
            <div class="admin-nav">
                <a href="add-question.php">Add Question</a>
                <a href="manage-questions.php">Manage Questions</a>
                <a href="../index.php" target="_blank">View Quiz</a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div style="color: red; margin-bottom: 1rem;"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalQuestions; ?></div>
                <div class="stat-label">Total Questions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($recentResults); ?></div>
                <div class="stat-label">Quiz Attempts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $avgScore; ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
        </div>

        <div class="data-card">
            <h2 class="section-title">Recent Quiz Results</h2>
            <?php if (empty($recentResults)): ?>
                <p>No quiz results yet.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentResults as $result): ?>
                            <tr>
                                <td><?php echo date('d M Y H:i', strtotime($result['completed_at'])); ?></td>
                                <td><?php echo $result['score'] . '/' . $result['total_questions']; ?></td>
                                <td><?php echo $result['percentage'] . '%'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="add-question.php" class="btn-primary">Add New Question</a>
        </div>
    </div>
</body>

</html>