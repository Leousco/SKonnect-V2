<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/models/UserAdminModel.php';

$token  = trim($_GET['token'] ?? '');
$db     = (new Database())->getConnection();
$model  = new UserAdminModel($db);

$verified = $token && $model->verifyByToken($token);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SKonnect — Account Verification</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('../../assets/img/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            /* Black with 50% opacity */
            z-index: 0;
        }

        .card {
            background: linear-gradient(135deg, #0f2545, #1e5fa8);
            border-radius: 14px;
            border-left: 5px solid #facc15;
            padding: 40px 44px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 8px 40px rgba(15, 37, 69, 0.25);
            position: relative;
            z-index: 2; 
        }

        .icon {
            font-size: 52px;
            margin-bottom: 20px;
            display: block;
            line-height: 1;
        }

        .badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 14px;
        }

        h1 {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.3;
            margin-bottom: 12px;
        }

        p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.78);
            line-height: 1.7;
            margin-bottom: 10px;
        }

        .btn {
            font-family: "Poppins", sans-serif;
            display: inline-block;
            margin-top: 24px;
            padding: 12px 28px;
            background: #facc15;
            color: #0f2545;
            font-size: 13px;
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.15s;
        }

        .btn:hover {
            background: #eab308;
        }

        .footer {
            color: rgba(255, 255, 255, 0.35);
            font-size: 11px;
            margin-top: 28px;
        }
    </style>
</head>

<body>
    <div class="card">
        <?php if ($verified) : ?>
            <span class="icon">✅</span>
            <span class="badge" style="color: #4ade80;">Account Verified</span>
            <h1>Your account is now verified!</h1>
            <p>You can now log in to SKonnect using your email and the password provided by the administrator.</p>
            <a href="/SKonnect/views/auth/login.php" class="btn">Go to Login</a>
        <?php else : ?>
            <span class="icon">❌</span>
            <span class="badge" style="color: #f87171;">Verification Failed</span>
            <h1>This link is invalid or already used.</h1>
            <p>The verification link may have already been used, or it doesn't exist. If you believe this is an error, please contact the barangay office.</p>
            <a href="/SKonnect/views/auth/login.php" class="btn">Back to Login</a>
        <?php endif; ?>
        <p class="footer">SKonnect &mdash; Sangguniang Kabataan Portal &bull; Barangay Sauyo</p>
    </div>
</body>

</html>