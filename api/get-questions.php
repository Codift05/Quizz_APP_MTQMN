<?php
require_once '../config/database.php';

header('Content-Type: application/json');

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get parameters (optional)
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$category = isset($_GET['category']) ? $_GET['category'] : null;
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : null;

// Build the query
$query = "SELECT q.id, q.text, q.arabic_text, q.type, q.answer, q.category, q.difficulty 
          FROM questions q";

$params = [];
$whereConditions = [];

if ($category) {
    $whereConditions[] = "q.category = :category";
    $params[':category'] = $category;
}

if ($difficulty) {
    $whereConditions[] = "q.difficulty = :difficulty";
    $params[':difficulty'] = $difficulty;
}

if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(' AND ', $whereConditions);
}

$query .= " ORDER BY RAND() LIMIT :limit";
$params[':limit'] = $limit;

try {
    // Prepare and execute the query
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $questions = $stmt->fetchAll();

    // Log the query result for debugging
    error_log("Found " . count($questions) . " questions from database");

    // Get options for multiple choice questions
    $result = [];
    foreach ($questions as $question) {
        if ($question['type'] === 'multiple') {
            $optionsStmt = $db->prepare("SELECT option_text FROM options WHERE question_id = :id");
            $optionsStmt->bindValue(':id', $question['id'], PDO::PARAM_INT);
            $optionsStmt->execute();
            $options = $optionsStmt->fetchAll(PDO::FETCH_COLUMN);

            // Add the correct answer if it's not in the options
            if (!in_array($question['answer'], $options)) {
                $options[] = $question['answer'];
            }

            // Shuffle options
            shuffle($options);

            $question['options'] = $options;
        }
        $result[] = $question;
    }

    if (empty($result)) {
        // Return a more helpful error message if no questions found
        http_response_code(404);
        echo json_encode([
            'error' => 'No questions found in the database. Please run db-setup.php to create sample questions.',
            'query' => $query
        ]);
    } else {
        echo json_encode($result);
    }
} catch (PDOException $e) {
    // Return detailed error
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'query' => $query,
        'trace' => $e->getTraceAsString()
    ]);
}
