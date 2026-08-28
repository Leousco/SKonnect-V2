<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => "PHP ERROR [$errno]: $errstr in $errfile on line $errline"]);
    exit();
});

set_exception_handler(function($e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'PHP EXCEPTION: ' . $e->getMessage()]);
    exit();
});

session_start();

require_once '../controllers/AuthController.php';
require_once '../controllers/LoginController.php';

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