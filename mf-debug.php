<?php
require_once 'config.php';
require_once 'mf-api.php';

// 編集権限チェック
if (!canEdit()) {
    header('Location: index.php');
    exit;
}

if (!MFApiClient::isConfigured()) {
    die('MF APIの設定が完了していません。<a href="mf-settings.php">設定ページ</a>から設定してください。');
}

try {
    $client = new MFApiClient();

    // 過去3ヶ月分のデータを取得
    $from = date('Y-m-d', strtotime('-3 months'));
    $to = date('Y-m-d');

    echo "<h2>MF API デバッグ情報</h2>";
    echo "<p>期間: {$from} ～ {$to}</p>";

    echo "<h3>請求書データ取得中...</h3>";
    $invoices = $client->getInvoices($from, $to);

    echo "<h3>取得した請求書件数:</h3>";
    $invoiceCount = isset($invoices['data']) ? count($invoices['data']) : 0;
    echo "<p>請求書: {$invoiceCount}件</p>";

    if (isset($invoices['meta'])) {
        echo "<h4>ページネーション情報:</h4>";
        echo "<pre>";
        print_r($invoices['meta']);
        echo "</pre>";
    }

    echo "<h3>取得した請求書一覧:</h3>";
    if (isset($invoices['data']) && is_array($invoices['data'])) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>請求書番号</th><th>タイトル</th><th>金額</th><th>請求日</th><th>取引先</th><th>ステータス</th></tr>";
        foreach ($invoices['data'] as $inv) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($inv['id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($inv['billing_number'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($inv['title'] ?? '') . "</td>";
            echo "<td>¥" . number_format($inv['total_price'] ?? 0) . "</td>";
            echo "<td>" . htmlspecialchars($inv['billing_date'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($inv['partner_name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($inv['payment_status'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>請求書データが取得できませんでした。</p>";
        echo "<pre>";
        print_r($invoices);
        echo "</pre>";
    }

    // プロジェクトデータも表示
    $data = getData();
    echo "<h3>登録されているプロジェクト (最初の10件):</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>プロジェクトID</th><th>プロジェクト名</th></tr>";
    foreach (array_slice($data['projects'], 0, 10) as $project) {
        echo "<tr><td>{$project['id']}</td><td>" . htmlspecialchars($project['name']) . "</td></tr>";
    }
    echo "</table>";

    echo "<p>総プロジェクト数: " . count($data['projects']) . "件</p>";

} catch (Exception $e) {
    echo "<h3>エラー発生:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
