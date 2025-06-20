<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// Function to check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Function to check if logged in user is admin
function isAdmin()
{
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

// Function to require login
function requireLogin()
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /Quizz_APP_MTQMN/login.php');
        exit;
    }
}

// Function to require admin role
function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: /Quizz_APP_MTQMN/index.php?error=unauthorized');
        exit;
    }
}

// Get current user info
function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    global $db;
    $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = :id");
    $stmt->bindValue(':id', $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->fetch();
}
