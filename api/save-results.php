<?php
// Include database connection
require_once '../config/database.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['score']) || !isset($data['totalQuestions'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Calculate percentage
    $percentage = ($data['score'] / $data['totalQuestions']) * 100;

    // Insert into results table
    $stmt = $db->prepare("INSERT INTO results (score, total_questions, percentage) 
                          VALUES (:score, :total_questions, :percentage)");

    $stmt->bindValue(':score', $data['score']);
    $stmt->bindValue(':total_questions', $data['totalQuestions']);
    $stmt->bindValue(':percentage', $percentage);

    $stmt->execute();

    // Return success with the ID of the inserted result
    echo json_encode([
        'success' => true,
        'resultId' => $db->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
