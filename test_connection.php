<?php

require_once __DIR__ . '/backend/config/Database.php';

$startTime = microtime(true);

try {
    $database = new Database();
    $conn = $database->getConnection();

    $elapsed = round((microtime(true) - $startTime) * 1000, 2);

    $checks = [];

    $stmt = $conn->query("SELECT VERSION() AS version");
    $checks['mysql_version'] = $stmt->fetchColumn();

    $stmt = $conn->query("SELECT DATABASE() AS db");
    $checks['active_database'] = $stmt->fetchColumn();

    $stmt = $conn->query("SELECT @@character_set_connection AS charset");
    $checks['charset'] = $stmt->fetchColumn();

    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $checks['tables_found'] = count($tables);
    $checks['tables'] = $tables;

    $result = [
        'status'          => 'success',
        'message'         => 'Database connection successful.',
        'connection_time' => $elapsed . ' ms',
        'diagnostics'     => $checks,
    ];
} catch (Exception $e) {
    $elapsed = round((microtime(true) - $startTime) * 1000, 2);
    $result = [
        'status'          => 'error',
        'message'         => $e->getMessage(),
        'connection_time' => $elapsed . ' ms',
    ];
}

$isCli = PHP_SAPI === 'cli';

if ($isCli) {
    echo "\n=== SKonnect — Database Connection Test ===\n\n";
    echo "Status  : " . strtoupper($result['status']) . "\n";
    echo "Message : " . $result['message'] . "\n";
    echo "Time    : " . $result['connection_time'] . "\n";

    if ($result['status'] === 'success') {
        $d = $result['diagnostics'];
        echo "\n--- Diagnostics ---\n";
        echo "MySQL Version    : " . $d['mysql_version'] . "\n";
        echo "Active Database  : " . $d['active_database'] . "\n";
        echo "Charset          : " . $d['charset'] . "\n";
        echo "Tables Found     : " . $d['tables_found'] . "\n";
        if ($d['tables_found'] > 0) {
            echo "Tables           :\n";
            foreach ($d['tables'] as $table) {
                echo "  - $table\n";
            }
        }
    }
    echo "\n";
} else {
    $isSuccess = $result['status'] === 'success';
    $color     = $isSuccess ? '#16a34a' : '#dc2626';
    $bgColor   = $isSuccess ? '#f0fdf4' : '#fef2f2';
    $border    = $isSuccess ? '#bbf7d0' : '#fecaca';
    $icon      = $isSuccess ? '✔' : '✘';
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SKonnect — DB Connection Test</title>
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: 'Segoe UI', sans-serif;
                background: #f8fafc;
                color: #1e293b;
                padding: 40px 20px;
            }

            .card {
                max-width: 580px;
                margin: 0 auto;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
            }

            .card-header {
                padding: 20px 24px;
                background: <?= $bgColor ?>;
                border-bottom: 1px solid <?= $border ?>;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .status-icon {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: <?= $color ?>;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                font-weight: 700;
                flex-shrink: 0;
            }

            .card-header h1 {
                font-size: 16px;
                font-weight: 700;
                color: <?= $color ?>;
            }

            .card-header p {
                font-size: 13px;
                color: #64748b;
                margin-top: 2px;
            }

            .card-body {
                padding: 24px;
            }

            .row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 10px 0;
                border-bottom: 1px solid #f1f5f9;
                font-size: 13px;
                gap: 12px;
            }

            .row:last-child {
                border-bottom: none;
            }

            .row-label {
                font-weight: 600;
                color: #64748b;
                white-space: nowrap;
                min-width: 140px;
            }

            .row-value {
                color: #1e293b;
                text-align: right;
                word-break: break-all;
            }

            .tables-list {
                list-style: none;
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                justify-content: flex-end;
            }

            .tables-list li {
                background: #e0f2fe;
                color: #0369a1;
                padding: 2px 10px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }

            .empty {
                color: #94a3b8;
                font-style: italic;
            }

            .warning {
                margin-top: 20px;
                padding: 12px 14px;
                background: #fffbeb;
                border: 1px solid #fde68a;
                border-radius: 8px;
                font-size: 12px;
                color: #92400e;
            }

            .warning strong {
                display: block;
                margin-bottom: 3px;
            }
        </style>
    </head>

    <body>
        <div class="card">
            <div class="card-header">
                <div class="status-icon"><?= $icon ?></div>
                <div>
                    <h1><?= $isSuccess ? 'Connection Successful' : 'Connection Failed' ?></h1>
                    <p><?= htmlspecialchars($result['message']) ?></p>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <span class="row-label">Connection Time</span>
                    <span class="row-value"><?= htmlspecialchars($result['connection_time']) ?></span>
                </div>
                <?php if ($isSuccess) : $d = $result['diagnostics']; ?>
                    <div class="row">
                        <span class="row-label">MySQL Version</span>
                        <span class="row-value"><?= htmlspecialchars($d['mysql_version']) ?></span>
                    </div>
                    <div class="row">
                        <span class="row-label">Active Database</span>
                        <span class="row-value"><?= htmlspecialchars($d['active_database']) ?></span>
                    </div>
                    <div class="row">
                        <span class="row-label">Charset</span>
                        <span class="row-value"><?= htmlspecialchars($d['charset']) ?></span>
                    </div>
                    <div class="row">
                        <span class="row-label">Tables (<?= $d['tables_found'] ?>)</span>
                        <span class="row-value">
                            <?php if ($d['tables_found'] > 0) : ?>
                                <ul class="tables-list">
                                    <?php foreach ($d['tables'] as $t) : ?>
                                        <li><?= htmlspecialchars($t) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <span class="empty">No tables found</span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="warning">
                    <strong>⚠ Security reminder</strong>
                    Remove or restrict access to this file before deploying to production.
                </div>
            </div>
        </div>
    </body>

    </html>
<?php } ?>