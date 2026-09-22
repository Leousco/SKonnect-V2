<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/EmailService.php';

class AuthController {

    

    public function register() {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("Invalid request method.");
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $required = ['first_name','last_name','gender','birth_date','email','password','confirm_password'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                die("Missing required field: " . $field);
            }
        }

        if ($_POST['password'] !== $_POST['confirm_password']) {
            die("Passwords do not match.");
        }

        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            die("Invalid email address.");
        }

        $user = new User();

        $user->first_name  = trim($_POST['first_name']);
        $user->last_name   = trim($_POST['last_name']);
        $user->middle_name = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : null;
        $user->gender      = $_POST['gender'];
        $user->birth_date  = $_POST['birth_date'];

        $birth = new DateTime($user->birth_date);
        $today = new DateTime();
        $user->age = $today->diff($birth)->y;

        $user->email    = trim($_POST['email']);
        $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $emailCheck = $user->emailExists();
        if ($emailCheck['exists'] && $emailCheck['is_verified'] == 1) {
            die("Email already registered and verified.");
        }

        $otp = rand(100000, 999999);
        $user->otp_code = $otp;
        $user->otp_expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        if ($emailCheck['exists'] && $emailCheck['is_verified'] == 0) {
            $user->updateOTP();
        } else {
            $user->create();
        }

        $emailService = new EmailService();
        $emailService->sendOTP($user->email, $otp, $user->first_name);

        $_SESSION['verify_email'] = $user->email;

        header("Location: ../../views/auth/verify_email.php");
        exit();
    }

    

    public function verifyOTP() {

        if (session_status() === PHP_SESSION_NONE) session_start();

        header('Content-Type: application/json');

        if (!isset($_SESSION['verify_email'])) {
            echo json_encode([
                "status" => "error",
                "message" => "Session expired. Please register again."
            ]);
            exit();
        }

        $email = $_SESSION['verify_email'];
        $otp   = $_POST['otp'] ?? null;

        if (!$otp || strlen($otp) != 6) {
            echo json_encode([
                "status" => "error",
                "message" => "Please enter a valid 6-digit OTP."
            ]);
            exit();
        }

        $user = new User();
        $user->email = $email;
        $user->otp_code = $otp;

        if ($user->verifyUser()) {
            unset($_SESSION['verify_email']);

            echo json_encode([
                "status" => "success",
                "message" => "Email verified successfully."
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid or expired OTP."
            ]);
        }

        exit();
    }

    
    
    public function resendOTP() {

        if (session_status() === PHP_SESSION_NONE) session_start();

        header('Content-Type: application/json');

        if (!isset($_SESSION['verify_email'])) {
            echo json_encode([
                "status" => "error",
                "message" => "Session expired. Please register again."
            ]);
            exit();
        }

        $email = $_SESSION['verify_email'];

        $user = new User();
        $user->email = $email;

        $otp = rand(100000, 999999);
        $user->otp_code = $otp;
        $user->otp_expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));
        $user->updateOTP();

        $emailService = new EmailService();
        $emailService->sendOTP($user->email, $otp, $user->first_name);

        echo json_encode([
            "status" => "success",
            "message" => "A new OTP has been sent to your email."
        ]);

        exit();
    }
}