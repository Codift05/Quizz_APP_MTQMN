<?php
require_once 'auth/auth.php';

// Destroy session
session_destroy();

// Redirect to home page
header('Location: index.php');
exit;
