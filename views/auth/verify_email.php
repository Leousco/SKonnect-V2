<?php
session_start();

if (!isset($_SESSION['verify_email'])) {
    header("Location: register.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Verify Email</title>

    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/auth/verify_email.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<nav id="navbar">
    <div class="navbar-container">
        <a href="../public/main.php" class="navbar-logo">
            <img src="../../assets/img/loger.jpg" alt="SK Logo">
            <span>SKonnect</span>
        </a>
        <ul class="navbar-menu">
            <li><a href="../public/main.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i> Home</a></li>
        </ul>
    </div>
</nav>

<main class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>

    <div class="verify-card">

        <div class="card-logo">
            <img src="../../assets/img/logo.jpg" alt="SK Logo">
        </div>

        <h1 class="card-title">VERIFY EMAIL</h1>
        <p class="card-subtitle">
            Enter the 6-digit code sent to<br>
            <span class="email-highlight">your email address</span>
        </p>

        <!-- OTP FORM -->
        <form id="otpForm" autocomplete="off">

        <input type="hidden" name="otp" id="otpValue" autocomplete="off">

            <div class="otp-group">
                <input type="text" class="otp-input" maxlength="1" required>
                <input type="text" class="otp-input" maxlength="1" required>
                <input type="text" class="otp-input" maxlength="1" required>
                <span class="otp-dash">—</span>
                <input type="text" class="otp-input" maxlength="1" required>
                <input type="text" class="otp-input" maxlength="1" required>
                <input type="text" class="otp-input" maxlength="1" required>
            </div>

            <p id="message" class="otp-message"></p>

            <button type="submit" class="verify-btn">Verify Code</button>
        </form>

        <!-- RESEND -->
        <div class="resend-block">
            <p class="resend-text">Didn't receive a code?</p>
            <button type="button" class="resend-btn" id="resendBtn" disabled>
                Resend Code <span class="countdown" id="countdown">(30s)</span>
            </button>
        </div>

    </div>
</main>

<script src="../../scripts/auth/verify_email.js"></script>

</body>
</html>