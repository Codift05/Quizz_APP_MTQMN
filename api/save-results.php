<?php
// Include database connection and auth
require_once '../config/database.php';
require_once '../auth/auth.php';

// Set header for JSON response
header('Content-Type: application/json');

// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Log data yang diterima untuk debugging
error_log("Received quiz result data: " . json_encode($data));

if (!isset($data['score']) || !isset($data['totalQuestions'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Calculate percentage
    $percentage = ($data['score'] / $data['totalQuestions']) * 100;

    // Check session status and user login
    $userId = null;
    if (isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        error_log("User is logged in. User ID: $userId");
    } else {
        error_log("User is not logged in. Result will be saved without user_id.");
    }

    // Log transaction start
    error_log("Starting database transaction for saving quiz result...");

    // Begin transaction for data integrity
    $db->beginTransaction();

    // Insert into results table with user_id if available
    if ($userId) {
        $stmt = $db->prepare("INSERT INTO results (score, total_questions, percentage, user_id) 
                          VALUES (:score, :total_questions, :percentage, :user_id)");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        error_log("Prepared query with user_id: $userId");
    } else {
        $stmt = $db->prepare("INSERT INTO results (score, total_questions, percentage) 
                          VALUES (:score, :total_questions, :percentage)");
        error_log("Prepared query without user_id");
    }

    $stmt->bindValue(':score', $data['score'], PDO::PARAM_INT);
    $stmt->bindValue(':total_questions', $data['totalQuestions'], PDO::PARAM_INT);
    $stmt->bindValue(':percentage', $percentage, PDO::PARAM_INT);

    // Execute the query
    $success = $stmt->execute();

    // Check result
    if ($success) {
        $resultId = $db->lastInsertId();
        error_log("Result saved successfully with ID: $resultId");

        // Commit transaction
        $db->commit();

        // Return success with the ID of the inserted result
        echo json_encode([
            'success' => true,
            'resultId' => $resultId,
            'userId' => $userId
        ]);
    } else {
        // Log SQL error info
        $errorInfo = $stmt->errorInfo();
        error_log("SQL Error: " . json_encode($errorInfo));

        // Rollback transaction
        $db->rollBack();

        // Return error
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to execute SQL statement',
            'sql_error' => $errorInfo
        ]);
    }
} catch (PDOException $e) {
    // Log the error
    error_log("PDO Exception: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());

    // Rollback transaction if active
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    // Return detailed error
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
