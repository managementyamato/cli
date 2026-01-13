<?php
require_once 'config.php';

// 編集権限チェック
if (!canEdit()) {
    header('Location: index.php');
    exit;
}

// データ読み込み
$data = getData();

$pageTitle = 'MF請求書デバッグ';
require_once 'header.php';
?>

<style>
.debug-section {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.debug-section h3 {
    margin: 0 0 1rem 0;
    color: var(--gray-700);
    border-bottom: 2px solid var(--gray-200);
    padding-bottom: 0.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.info-label {
    font-weight: 600;
    color: var(--gray-600);
}

.info-value {
    color: var(--gray-800);
}

.json-viewer {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 6px;
    overflow-x: auto;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    max-height: 400px;
    overflow-y: auto;
}

.invoice-card {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    border-left: 4px solid var(--primary-color);
}

.invoice-card h4 {
    margin: 0 0 0.5rem 0;
    color: var(--gray-800);
    font-size: 1rem;
}

.invoice-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
    font-size: 0.875rem;
}

.invoice-details dt {
    font-weight: 600;
    color: var(--gray-600);
}

.invoice-details dd {
    color: var(--gray-800);
    margin: 0;
}
</style>

<div class="page-header">
    <h1>MF請求書デバッグ</h1>
    <a href="finance.php" class="btn btn-secondary">財務管理に戻る</a>
</div>

<!-- 基本情報 -->
<div class="debug-section">
    <h3>基本情報</h3>
    <div class="info-grid">
        <div class="info-label">MF請求書数:</div>
        <div class="info-value"><?= isset($data['mf_invoices']) ? count($data['mf_invoices']) : 0 ?>件</div>

        <div class="info-label">最終同期日時:</div>
        <div class="info-value"><?= isset($data['mf_sync_timestamp']) ? htmlspecialchars($data['mf_sync_timestamp']) : '未同期' ?></div>

        <div class="info-label">プロジェクト数:</div>
        <div class="info-value"><?= count($data['projects']) ?>件</div>

        <div class="info-label">財務データ登録数:</div>
        <div class="info-value"><?= isset($data['finance']) ? count($data['finance']) : 0 ?>件</div>
    </div>
</div>

<!-- MF請求書一覧 -->
<div class="debug-section">
    <h3>MF請求書一覧</h3>
    <?php if (isset($data['mf_invoices']) && !empty($data['mf_invoices'])): ?>
        <p style="color: var(--gray-600); margin-bottom: 1rem;">
            取得した請求書: <?= count($data['mf_invoices']) ?>件
        </p>
        <div style="max-height: 500px; overflow-y: auto;">
            <?php foreach ($data['mf_invoices'] as $index => $invoice): ?>
                <div class="invoice-card">
                    <h4><?= $index + 1 ?>. <?= htmlspecialchars($invoice['title']) ?></h4>
                    <dl class="invoice-details">
                        <dt>請求書ID:</dt>
                        <dd><?= htmlspecialchars($invoice['id']) ?></dd>

                        <dt>請求書番号:</dt>
                        <dd><?= htmlspecialchars($invoice['billing_number']) ?></dd>

                        <dt>金額:</dt>
                        <dd>¥<?= number_format($invoice['total_price']) ?></dd>

                        <dt>請求日:</dt>
                        <dd><?= htmlspecialchars($invoice['billing_date']) ?></dd>

                        <dt>取引先:</dt>
                        <dd><?= htmlspecialchars($invoice['partner_name']) ?></dd>

                        <dt>ステータス:</dt>
                        <dd><?= htmlspecialchars($invoice['status']) ?></dd>

                        <?php if (!empty($invoice['memo'])): ?>
                            <dt>メモ:</dt>
                            <dd><?= htmlspecialchars($invoice['memo']) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--gray-600); text-align: center; padding: 2rem;">
            MF請求書データがありません。「MFから同期」ボタンでデータを取得してください。
        </p>
    <?php endif; ?>
</div>

<!-- プロジェクト一覧 -->
<div class="debug-section">
    <h3>プロジェクト一覧 (<?= count($data['projects']) ?>件)</h3>
    <?php if (!empty($data['projects'])): ?>
        <div style="max-height: 400px; overflow-y: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>PJ ID</th>
                        <th>案件名</th>
                        <th>顧客名</th>
                        <th>財務データ</th>
                        <th>MF請求書ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['projects'] as $project): ?>
                        <?php
                        $finance = isset($data['finance'][$project['id']]) ? $data['finance'][$project['id']] : null;
                        $hasMFLink = $finance && isset($finance['mf_billing_id']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($project['id']) ?></td>
                            <td><?= htmlspecialchars($project['name']) ?></td>
                            <td><?= htmlspecialchars($project['customer_name'] ?? '-') ?></td>
                            <td><?= $finance ? '✅' : '-' ?></td>
                            <td><?= $hasMFLink ? htmlspecialchars($finance['mf_billing_id']) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--gray-600); text-align: center; padding: 2rem;">
            プロジェクトが登録されていません。
        </p>
    <?php endif; ?>
</div>

<!-- 生データビュー -->
<div class="debug-section">
    <h3>生データ (JSON)</h3>
    <details>
        <summary style="cursor: pointer; color: var(--primary-color); margin-bottom: 1rem;">クリックして表示</summary>
        <div class="json-viewer">
            <pre><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
    </details>
</div>

<?php require_once 'footer.php'; ?>
