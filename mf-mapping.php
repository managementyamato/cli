<?php
require_once 'config.php';
require_once 'mf-api.php';

// 編集権限チェック
if (!canEdit()) {
    header('Location: index.php');
    exit;
}

$data = getData();

// マッピング保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mappings'])) {
    $mappings = $_POST['mapping'] ?? array();

    foreach ($mappings as $billingId => $projectId) {
        if (empty($projectId) || $projectId === 'none') {
            continue;
        }

        // MF請求書データから情報を取得
        $invoice = $data['mf_invoices'][$billingId] ?? null;
        if (!$invoice) {
            continue;
        }

        // 財務データに反映
        $existingFinance = $data['finance'][$projectId] ?? array();

        $data['finance'][$projectId] = array(
            'revenue' => $invoice['total_price'],
            'cost' => $existingFinance['cost'] ?? 0,
            'labor_cost' => $existingFinance['labor_cost'] ?? 0,
            'material_cost' => $existingFinance['material_cost'] ?? 0,
            'other_cost' => $existingFinance['other_cost'] ?? 0,
            'gross_profit' => $invoice['total_price'] - ($existingFinance['cost'] ?? 0),
            'net_profit' => $invoice['total_price'] - (($existingFinance['cost'] ?? 0) + ($existingFinance['labor_cost'] ?? 0) + ($existingFinance['material_cost'] ?? 0) + ($existingFinance['other_cost'] ?? 0)),
            'notes' => ($existingFinance['notes'] ?? '') . "\n[MF手動マッピング] {$invoice['billing_date']} - {$invoice['title']}",
            'updated_at' => date('Y-m-d H:i:s'),
            'mf_synced' => true,
            'mf_billing_id' => $billingId
        );
    }

    saveData($data);
    header('Location: mf-mapping.php?saved=' . count(array_filter($mappings, function($v) { return !empty($v) && $v !== 'none'; })));
    exit;
}

// MF請求書データを取得（まだない場合は取得）
if (!isset($data['mf_invoices']) || empty($data['mf_invoices'])) {
    if (MFApiClient::isConfigured()) {
        try {
            $client = new MFApiClient();
            $from = date('Y-m-d', strtotime('-3 months'));
            $to = date('Y-m-d');
            $invoices = $client->getInvoices($from, $to);

            if (!isset($data['mf_invoices'])) {
                $data['mf_invoices'] = array();
            }

            if (isset($invoices['data']) && is_array($invoices['data'])) {
                foreach ($invoices['data'] as $invoice) {
                    $billingId = $invoice['id'] ?? null;
                    $data['mf_invoices'][$billingId] = array(
                        'id' => $billingId,
                        'billing_number' => $invoice['billing_number'] ?? null,
                        'title' => $invoice['title'] ?? '',
                        'total_price' => floatval($invoice['total_price'] ?? 0),
                        'billing_date' => $invoice['billing_date'] ?? '',
                        'partner_name' => $invoice['partner_name'] ?? '',
                        'status' => $invoice['payment_status'] ?? '',
                        'synced_at' => date('Y-m-d H:i:s')
                    );
                }
                saveData($data);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

require_once 'header.php';
?>

<style>
.mapping-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.mapping-table th,
.mapping-table td {
    padding: 0.75rem;
    border: 1px solid var(--gray-200);
    text-align: left;
}

.mapping-table th {
    background: var(--gray-100);
    font-weight: 600;
}

.mapping-table tr:hover {
    background: var(--gray-50);
}

.mapped {
    background: #d1fae5 !important;
}

.project-select {
    padding: 0.5rem;
    border: 1px solid var(--gray-300);
    border-radius: 4px;
    width: 100%;
    max-width: 300px;
}
</style>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success"><?= intval($_GET['saved']) ?>件のマッピングを保存しました</div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-error">エラー: <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 style="margin: 0;">MF請求書とプロジェクトのマッピング</h2>
        <p style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem;">
            MFから取得した請求書を、システム内のプロジェクトに手動で対応付けます。
        </p>
    </div>
    <div class="card-body">
        <?php if (empty($data['mf_invoices'])): ?>
            <p style="text-align: center; color: var(--gray-600); padding: 2rem;">
                MFから請求書データを取得してください。<br>
                <a href="finance.php" class="btn btn-primary" style="margin-top: 1rem; text-decoration: none;">財務管理ページへ</a>
            </p>
        <?php else: ?>
            <form method="POST" action="">
                <input type="hidden" name="save_mappings" value="1">

                <div style="margin-bottom: 1rem;">
                    <strong>取得済み請求書: <?= count($data['mf_invoices']) ?>件</strong>
                </div>

                <div style="overflow-x: auto;">
                    <table class="mapping-table">
                        <thead>
                            <tr>
                                <th>請求書番号</th>
                                <th>タイトル</th>
                                <th>金額</th>
                                <th>請求日</th>
                                <th>取引先</th>
                                <th>対応プロジェクト</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['mf_invoices'] as $invoice): ?>
                                <?php
                                // 既にマッピング済みかチェック
                                $mappedProjectId = null;
                                foreach ($data['finance'] as $projectId => $finance) {
                                    if (isset($finance['mf_billing_id']) && $finance['mf_billing_id'] === $invoice['id']) {
                                        $mappedProjectId = $projectId;
                                        break;
                                    }
                                }
                                $isMapped = $mappedProjectId !== null;
                                ?>
                                <tr class="<?= $isMapped ? 'mapped' : '' ?>">
                                    <td><?= htmlspecialchars($invoice['billing_number']) ?></td>
                                    <td><?= htmlspecialchars($invoice['title']) ?></td>
                                    <td>¥<?= number_format($invoice['total_price']) ?></td>
                                    <td><?= htmlspecialchars($invoice['billing_date']) ?></td>
                                    <td><?= htmlspecialchars($invoice['partner_name']) ?></td>
                                    <td>
                                        <select name="mapping[<?= htmlspecialchars($invoice['id']) ?>]" class="project-select">
                                            <option value="none">-- 選択してください --</option>
                                            <?php foreach ($data['projects'] as $project): ?>
                                                <option value="<?= htmlspecialchars($project['id']) ?>"
                                                        <?= $mappedProjectId === $project['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($project['id']) ?> - <?= htmlspecialchars($project['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($isMapped): ?>
                                            <div style="font-size: 0.75rem; color: var(--success); margin-top: 0.25rem;">
                                                ✓ マッピング済み
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">マッピングを保存</button>
                    <a href="finance.php" class="btn btn-secondary" style="text-decoration: none;">財務管理に戻る</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
