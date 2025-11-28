cat > /var/www/mysite/index.php << 'EOF'
<?php
// ===== Настройки подключения к БД =====
$dbHost = '10.4.4.4';
$dbName = 'soclab';
$dbUser = 'webuser';
$dbPass = 'WarGag12'; // пароль webuser

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
    [
        'scenario' => 'SQL Injection',
        'severity' => 'High',
        'description' => 'Обнаружена попытка SQL-инъекции в параметре ?id= на веб-сервере.'
    ],
    [
        'scenario' => 'Brute force SSH',
        'severity' => 'Medium',
        'description' => 'Замечена серия неудачных попыток входа по SSH с одного IP-адреса.'
    ],
    [
        'scenario' => 'DDoS HTTP flood',
        'severity' => 'Critical',
        'description' => 'Резкий рост числа HTTP-запросов на /login с множества IP-адресов.'
    ],
    [
        'scenario' => 'Phishing mail',
        'severity' => 'Low',
        'description' => 'Пользователь сообщил о подозрительном письме с вложением.'
    ],
    [
        'scenario' => 'Privilege escalation',
        'severity' => 'Critical',
        'description' => 'Выявлена попытка повышения привилегий на сервере базы данных.'
    ],
    [
        'scenario' => 'Malware detected',
        'severity' => 'High',
        'description' => 'Антивирус обнаружил вредоносный файл в домашнем каталоге пользователя.'
    ],
    [
        'scenario' => 'Port scan',
        'severity' => 'Medium',
        'description' => 'Сетевой экран зафиксировал массовое сканирование портов с внешнего адреса.'
    ],
];

// Если нажата кнопка "Смоделировать атаку" — добавляем запись
$lastInsert = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate'])) {
    $attack = $scenarios[array_rand($scenarios)];

    $stmt = $pdo->prepare("
        INSERT INTO attacks (scenario, severity, description)
        VALUES (:scenario, :severity, :description)
    ");
    $stmt->execute([
        ':scenario'   => $attack['scenario'],
        ':severity'   => $attack['severity'],
        ':description'=> $attack['description'],
    ]);

    $lastId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM attacks WHERE id = :id");
    $stmt->execute([':id' => $lastId]);
    $lastInsert = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Статистика
$total = (int)$pdo->query("SELECT COUNT(*) FROM attacks")->fetchColumn();
$critical = (int)$pdo->query("SELECT COUNT(*) FROM attacks WHERE severity = 'Critical'")->fetchColumn();

// Последние 10 атак
$stmt = $pdo->query("
    SELECT id, created_at, scenario, severity, description
    FROM attacks
    ORDER BY created_at DESC
    LIMIT 10
");
$attacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>SOC Attack Simulator</title>
<style>
    body { background-color: #020617; color: #e5e7eb; font-family: Arial; padding: 20px; }
    .container { max-width: 960px; margin: 0 auto; }
    h1, h2 { color: #f97316; }
    .card { background-color: #020617; border-radius: 8px; border: 1px solid #1f2937; padding: 16px; margin-bottom: 20px; }
    .stats { display: flex; gap: 16px; flex-wrap: wrap; }
    .stat-box { flex:1; background:#0f172a; border-radius:8px; padding:12px; text-align:center; }
    .btn { padding:10px 18px; border-radius:6px; border:none; cursor:pointer; font-weight:bold; margin-top:8px; }
    .btn-primary { background:#f97316; color:#111827; }
    .severity-Low { color:#22c55e; }
    .severity-Medium { color:#eab308; }
    .severity-High { color:#f97316; }
    .severity-Critical { color:#ef4444; font-weight:bold; }
</style>
</head>
<body>
<div class="container">
<h1>SOC Attack Simulator</h1>

<form method="post">
<button type="submit" name="simulate" value="1" class="btn btn-primary">
⚡ Сымитировать атаку
</button>
</form>

<div class="stats">
<div class="stat-box"><div>Всего атак:</div><div><?= $total ?></div></div>
<div class="stat-box"><div>Критических:</div><div><?= $critical ?></div></div>
</div>

<h2>Последние 10 атак</h2>
<table border="1" cellpadding="6" cellspacing="0">
<tr><th>ID</th><th>Время</th><th>Сценарий</th><th>Уровень</th><th>Описание</th></tr>
<?php foreach ($attacks as $row): ?>
<tr>
<td><?= $row['id']; ?></td>
<td><?= $row['created_at']; ?></td>
<td><?= $row['scenario']; ?></td>
<td class="severity-<?= $row['severity']; ?>"><?= $row['severity']; ?></td>
<td><?= nl2br($row['description']); ?></td>
</tr>
<?php endforeach; ?>
</table>

</div>
</body>
</html>
EOF
