<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Access Denied</title>
    <link rel="stylesheet" href="../../styles/global.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0d1117; color: #c9d1d9;
            min-height: 100vh; display: flex;
            align-items: center; justify-content: center;
        }
        .card {
            text-align: center; padding: 48px 56px;
            background: #161b22; border: 1px solid #21262d;
            border-radius: 16px; max-width: 420px;
        }
        .icon { font-size: 3rem; color: #f85149; margin-bottom: 20px; }
        h1 { font-size: 1.5rem; margin-bottom: 10px; }
        p  { color: #6e7681; font-size: .9rem; line-height: 1.6; margin-bottom: 28px; }
        .btn {
            display: inline-block; background: #f0883e; color: #000;
            padding: 11px 28px; border-radius: 8px; text-decoration: none;
            font-weight: 600; font-size: .9rem; transition: opacity .15s;
        }
        .btn:hover { opacity: .85; }
        .back { display: block; margin-top: 12px; font-size: .82rem; color: #6e7681; text-decoration: none; }
        .back:hover { color: #c9d1d9; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">🔒</div>
    <h1>Access Denied</h1>
    <p>You don't have permission to view this page. Please contact your administrator if you believe this is a mistake.</p>
    <a href="javascript:history.back()" class="btn">Go Back</a>
    <a href="/SKonnect/views/auth/login.php" class="back">Return to Login</a>
</div>
</body>
</html>