<?php
// Include database connection
require_once '../config/database.php';

require_once '../auth/auth.php';

// Check if user is admin
requireAdmin();

$message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    if (empty($_POST['text']) || empty($_POST['answer'])) {
        $message = '<div style="color: red">Please fill in all required fields</div>';
    } else {
        try {
            // Begin transaction
            $db->beginTransaction();

            // Set type to multiple choice always
            $questionType = 'multiple';

            // Insert question
            $stmt = $db->prepare("INSERT INTO questions (text, arabic_text, type, answer, difficulty, category) 
                              VALUES (:text, :arabic_text, :type, :answer, :difficulty, :category)");

            $stmt->bindValue(':text', $_POST['text']);
            $stmt->bindValue(':arabic_text', $_POST['arabic_text'] ?? null);
            $stmt->bindValue(':type', $questionType);
            $stmt->bindValue(':answer', $_POST['answer']);
            $stmt->bindValue(':difficulty', $_POST['difficulty'] ?? 'medium');
            $stmt->bindValue(':category', $_POST['category'] ?? null);

            $stmt->execute();
            $questionId = $db->lastInsertId();

            // Process multiple choice options
            if (isset($_POST['options'])) {
                // Remove empty options
                $options = array_filter($_POST['options'], function ($option) {
                    return !empty(trim($option));
                });

                // Make sure the answer is in the options
                if (!in_array($_POST['answer'], $options)) {
                    $options[] = $_POST['answer'];
                }

                // Insert each option
                $optionStmt = $db->prepare("INSERT INTO options (question_id, option_text) VALUES (:question_id, :option_text)");

                foreach ($options as $option) {
                    $optionStmt->bindValue(':question_id', $questionId);
                    $optionStmt->bindValue(':option_text', trim($option));
                    $optionStmt->execute();
                }
            }

            // Commit transaction
            $db->commit();

            $message = '<div style="color: green">Question added successfully!</div>';

            // Reset form data
            $_POST = [];
        } catch (PDOException $e) {
            // Rollback transaction in case of error
            $db->rollBack();
            $message = '<div style="color: red">Error adding question: ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question - Admin Panel</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-container {
            background: white;
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .options-container {
            margin-top: 1rem;
            border: 1px solid #eee;
            padding: 1rem;
            border-radius: 5px;
        }

        .option-input {
            display: flex;
            margin-bottom: 0.5rem;
        }

        .option-input input {
            flex: 1;
        }

        .add-option-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 0.5rem;
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

        .question-type-badge {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-weight: bold;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="background-pattern"></div>

    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Add New Question</h1>
            <div class="admin-nav">
                <a href="admin-panel.php">Dashboard</a>
                <a href="manage-questions.php">Manage Questions</a>
                <a href="../index.php">View Quiz</a>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="question-type-badge">Pilihan Ganda</div>

        <form method="post" action="">
            <div class="form-group">
                <label for="text">Question Text*</label>
                <input type="text" id="text" name="text" required value="<?php echo $_POST['text'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label for="arabic_text">Arabic Text (optional)</label>
                <textarea id="arabic_text" name="arabic_text" rows="3"><?php echo $_POST['arabic_text'] ?? ''; ?></textarea>
            </div>

            <!-- Hidden field for type, always set to multiple -->
            <input type="hidden" name="type" value="multiple">

            <div class="form-group">
                <label for="answer">Correct Answer*</label>
                <input type="text" id="answer" name="answer" required value="<?php echo $_POST['answer'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label>Options (for multiple choice)*</label>
                <div class="options-container" id="options-container">
                    <?php
                    if (isset($_POST['options']) && is_array($_POST['options'])) {
                        foreach ($_POST['options'] as $index => $option) {
                            echo '<div class="option-input">
                                    <input type="text" name="options[]" value="' . htmlspecialchars($option) . '">
                                  </div>';
                        }
                    } else {
                        // Default 3 empty options
                        for ($i = 0; $i < 3; $i++) {
                            echo '<div class="option-input">
                                    <input type="text" name="options[]" placeholder="Option ' . ($i + 1) . '">
                                  </div>';
                        }
                    }
                    ?>
                </div>
                <button type="button" class="add-option-btn" onclick="addOption()">+ Add Option</button>
                <p><small>Note: The correct answer will be automatically added as an option if not included.</small></p>
            </div>

            <div class="form-group">
                <label for="difficulty">Difficulty Level</label>
                <select id="difficulty" name="difficulty">
                    <option value="easy" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === 'easy') ? 'selected' : ''; ?>>Easy</option>
                    <option value="medium" <?php echo (!isset($_POST['difficulty']) || $_POST['difficulty'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                    <option value="hard" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === 'hard') ? 'selected' : ''; ?>>Hard</option>
                </select>
            </div>

            <div class="form-group">
                <label for="category">Category (optional)</label>
                <input type="text" id="category" name="category" value="<?php echo $_POST['category'] ?? ''; ?>">
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 1rem;">Add Question</button>
        </form>
    </div>

    <script>
        function addOption() {
            const container = document.getElementById('options-container');
            const newOption = document.createElement('div');
            newOption.className = 'option-input';
            newOption.innerHTML = `<input type="text" name="options[]" placeholder="New option">`;
            container.appendChild(newOption);
        }
    </script>
</body>

</html>