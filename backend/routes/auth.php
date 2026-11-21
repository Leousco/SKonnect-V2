<?php

// Keep uncaught failures as JSON for the client, but log the details serverside
// instead of exposing errors, file paths, or stack traces in the response.
set_exception_handler(function (Throwable $e) {
    error_log('auth.php uncaught exception: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Something went wrong. Please try again.']);
    exit();
});

session_start();

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/LoginController.php';

$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit();
}

switch ($action) {
    case 'register':
        (new AuthController())->register();
        break;

    case 'verify_otp':
        (new AuthController())->verifyOTP();
        break;

    case 'resend_otp':
        (new AuthController())->resendOTP();
        break;

    case 'login':
        (new LoginController())->login();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        exit();
}