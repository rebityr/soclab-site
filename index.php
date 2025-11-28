<?php
// ===== Настройки подключения к БД =====
$dbHost = '10.4.4.4';
$dbName = 'soclab';
$dbUser = 'webuser';
$dbPass = 'WarGag12';

$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

// Подключение к MariaDB
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("<h1>Ошибка подключения к БД</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>");
}

// Набор сценариев атак
$scenarios = [
    ['scenario' => 'SQL Injection', 'severity' => 'High', 'description' => 'Попытка SQL-инъекции в параметре ?id='],
    ['scenario' => 'Bruteforce SSH', 'severity' => 'Medium', 'description' => 'Множество попыток входа по SSH с одного IP.'],
    ['scenario' => 'DDoS HTTP Flood', 'severity' => 'Critical', 'description' => 'Резкое увеличение количества HTTP-запросов.'],
    ['scenario' => 'Phishing Email', 'severity' => 'Low', 'description' => 'Пользователь получил подозрительное письмо.'],
    ['scenario' => 'Privilege Escalation', 'severity' => 'Critical', 'description' => 'Попытка повышения привилегий на сервере.'],
    ['scenario' => 'Malware Detected', 'severity' => 'High', 'description' => 'Вредоносный файл обнаружен антивирусом.'],
    ['scenario' => 'Port Scan', 'severity' => 'Medium', 'description' => 'Зафиксировано сканирование портов.'],
];

// Если нажата кнопка "Смоделировать атаку"
$lastInsert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate'])) {
    $attack = $scenarios[array_rand($scenarios)];

    $stmt = $pdo->prepare("INSERT INTO attacks (scenario, severity, description) VALUES (:scenario, :severity, :description)");
    $stmt->execute([
        ':scenario' => $attack['scenario'],
        ':severity' => $attack['severity'],
        ':description' => $attack['description']
    ]);

    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM attacks WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $lastInsert = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Статистика
$total = (int)$pdo->query("SELECT COUNT(*) FROM attacks")->fetchColumn();
$critical = (int)$pdo->query("SELECT COUNT(*) FROM attacks WHERE severity = 'Critical'")->fetchColumn();

// Последние 10 атак
$stmt = $pdo->query("SELECT id, created_at, scenario, severity, description FROM attacks ORDER BY created_at DESC LIMIT 10");
$attacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>SOC Attack Simulator</title>
    <style>
        body { background:#020617; color:#e5e7eb; font-family:Arial; padding:20px; }
        h1, h2 { color:#f97316; }
        .container { max-width:900px; margin:auto; }
        .card { background:#0f172a; padding:20px; margin:20px 0; border-radius:8px; }
        .btn { background:#f97316; padding:10px 14px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; }
        .btn:hover { background:#fb923c; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { padding:8px; border-bottom:1px solid #1f2937; }
        .severity-Low { color:#22c55e; }
        .severity-Medium { color:#eab308; }
        .severity-High { color:#f97316; }
        .severity-Critical { color:#ef4444; font-weight:bold; }
        .stat-box { background:#1e293b; padding:10px; border-radius:6px; display:inline-block; margin-right:10px; }
    </style>
</head>
<body>

<div class="container">
    <h1>SOC Attack Simulator</h1>
    <p>DMZ Web Server → MariaDB в ЛВС</p>

    <div class="card">
        <h2>Симуляция атаки</h2>
        <form method="post">
            <button class="btn" type="submit" name="simulate">⚡ Сымитировать атаку</button>
        </form>

        <div class="stat-box">Всего атак: <b><?= $total ?></b></div>
        <div class="stat-box">Критических: <b><?= $critical ?></b></div>

        <?php if ($lastInsert): ?>
            <div class="card" style="border-left:3px solid #f97316;">
                <h3>Последняя атака:</h3>
                <p><b><?= htmlspecialchars($lastInsert['scenario']) ?></b></p>
                <p class="severity-<?= htmlspecialchars($lastInsert['severity']) ?>">
                    Критичность: <?= htmlspecialchars($lastInsert['severity']) ?>
                </p>
                <p><?= nl2br(htmlspecialchars($lastInsert['description'])) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Последние 10 инцидентов</h2>

        <?php if (!$attacks): ?>
            <p>Пока нет данных. Нажми “Сымитировать атаку”.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Время</th>
                        <th>Сценарий</th>
                        <th>Уровень</th>
                        <th>Описание</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attacks as $row): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td><?= htmlspecialchars($row['scenario']) ?></td>
                            <td class="severity-<?= htmlspecialchars($row['severity']) ?>">
                                <?= htmlspecialchars($row['severity']) ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
