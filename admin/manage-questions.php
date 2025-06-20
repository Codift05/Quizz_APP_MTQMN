<?php
// Include database connection
require_once '../config/database.php';

require_once '../auth/auth.php';

// Check if user is admin
requireAdmin();

// Process delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $db->prepare("DELETE FROM questions WHERE id = :id");
        $stmt->bindValue(':id', $_GET['delete']);
        $stmt->execute();

        $message = '<div style="color: green">Question deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div style="color: red">Error deleting question: ' . $e->getMessage() . '</div>';
    }
}

// Get questions with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE text LIKE :search OR answer LIKE :search OR category LIKE :search";
    $params[':search'] = "%$search%";
}

try {
    // Get total count of questions
    $countQuery = "SELECT COUNT(*) FROM questions $whereClause";
    $countStmt = $db->prepare($countQuery);
    if (!empty($search)) {
        $countStmt->bindValue(':search', "%$search%");
    }
    $countStmt->execute();
    $totalQuestions = $countStmt->fetchColumn();
    $totalPages = ceil($totalQuestions / $perPage);

    // Get questions for current page
    $query = "SELECT * FROM questions $whereClause ORDER BY id DESC LIMIT :offset, :perPage";
    $stmt = $db->prepare($query);
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%");
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = '<div style="color: red">Database error: ' . $e->getMessage() . '</div>';
    $questions = [];
    $totalPages = 0;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - Admin Panel</title>
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

        .action-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 5px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-block;
            margin-right: 0.5rem;
            margin-bottom: 5px;
        }

        .action-btn.danger {
            background: #e74c3c;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #3498db;
        }

        .page-link.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .search-bar {
            margin-bottom: 1.5rem;
        }

        .search-bar input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 70%;
        }

        .search-bar button {
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="background-pattern"></div>

    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Kelola Soal</h1>
            <div class="admin-nav">
                <a href="admin-panel.php">Dashboard</a>
                <a href="add-question.php">Tambah Soal</a>
                <a href="../index.php">Halaman Kuis</a>
            </div>
        </div>

        <?php if (isset($message)) echo $message; ?>

        <div class="search-bar">
            <form action="" method="get">
                <input type="text" name="search" placeholder="Search questions..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pertanyaan</th>
                    <th>Tipe</th>
                    <th>Jawaban</th>
                    <th>Kategori</th>
                    <th>Tingkat Kesulitan</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Tidak ada pertanyaan</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($questions as $question): ?>
                        <tr>
                            <td><?php echo $question['id']; ?></td>
                            <td><?php echo htmlspecialchars(substr($question['text'], 0, 50)) . (strlen($question['text']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo ucfirst($question['type']); ?></td>
                            <td><?php echo htmlspecialchars(substr($question['answer'], 0, 30)); ?></td>
                            <td><?php echo $question['category'] ?? 'N/A'; ?></td>
                            <td><?php echo ucfirst($question['difficulty']); ?></td>
                            <td>
                                <a href="add-question.php?edit=<?php echo $question['id']; ?>" class="action-btn">Edit</a>
                                <a href="manage-questions.php?delete=<?php echo $question['id']; ?>" class="action-btn danger" onclick="return confirm('Are you sure you want to delete this question?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="add-question.php" class="btn-primary">Tambah Soal Baru</a>
        </div>
    </div>
</body>

</html>