<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Login</title>

    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/auth/login.css">
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
</head>
<body>

<nav id="navbar">
    <div class="navbar-container">
        <a href="../public/main.php" class="navbar-logo">
            <img src="../../assets/img/loger.jpg" alt="SK Logo">
            <span>SKonnect</span>
        </a>
        <ul class="navbar-menu">
            <li><a href="../public/main.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i>  Home</a></li>
        </ul>
    </div>
</nav>

<main class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>

    <div class="login-card">

        <div class="card-logo">
            <img src="../../assets/img/loger.jpg" alt="SK Logo">
        </div>

        <h1 class="card-title">LOGIN</h1>
        <p class="card-subtitle">Sign in to access portal services</p>

        <form id="loginForm" autocomplete="off">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="input-field" placeholder="Enter your email" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>

                <div class="password-wrap">
                    <input 
                        type="password"
                        id="password"
                        name="password"
                        class="input-field"
                        placeholder="Enter your password"
                        required
                    >
                    <i
                        class="fa-solid fa-eye toggle-icon"
                        onclick="togglePassword('password', this)"
                    ></i>
                </div>
            </div>

            <p id="loginMessage" class="form-message"></p>

            <div id="lockout-notice" class="lockout-notice" hidden>
                <i class="fa-solid fa-lock lockout-notice__icon"></i>
                <div class="lockout-notice__body">
                    <span class="lockout-notice__label">Account temporarily locked</span>
                    <span class="lockout-notice__timer" id="lockout-timer">—</span>
                </div>
            </div>

            <button type="submit" class="login-btn">Login</button>

            <div class="form-footer">
                <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                <p class="register-block">
                    No Account? <a href="register.php" class="register-link">Register</a>
                </p>
            </div>
        </form>
    </div>
</main>

<!-- BAN MODAL -->
<div class="ban-modal-overlay" id="ban-modal-overlay" aria-hidden="true">
    <div class="ban-modal" role="dialog" aria-modal="true" aria-labelledby="ban-modal-title">
        <div class="ban-modal-icon">⛔</div>
        <h2 class="ban-modal-title" id="ban-modal-title">Account Restricted</h2>
        <p class="ban-modal-body">Your account has been restricted by an administrator and cannot be accessed.</p>
        <div class="ban-modal-reason-wrap">
            <span class="ban-modal-reason-label">Reason</span>
            <p class="ban-modal-reason" id="ban-modal-reason">—</p>
        </div>
        <p class="ban-modal-contact">If you believe this is a mistake, please contact the barangay office.</p>
        <button class="ban-modal-btn" id="ban-modal-close">Okay</button>
    </div>
</div>

<script src="../../scripts/auth/login.js"></script>
</body>
</html>