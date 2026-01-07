<?php
require_once 'config.php';
$data = getData();

$message = '';
$messageType = '';

// PJ追加
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pj'])) {
    $pjNumber = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['pj_number'] ?? ''));
    $pjName = trim($_POST['pj_name'] ?? '');

    if ($pjNumber && $pjName) {
        // 重複チェック
        $exists = false;
        foreach ($data['projects'] as $p) {
            if ($p['id'] === $pjNumber) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            $message = 'このPJ番号は既に登録されています';
            $messageType = 'danger';
        } else {
            $data['projects'][] = ['id' => $pjNumber, 'name' => $pjName];
            saveData($data);
            $message = 'PJを追加しました';
            $messageType = 'success';
        }
    }
}

// PJ削除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_pj'])) {
    $deleteId = $_POST['delete_pj'];
    $data['projects'] = array_values(array_filter($data['projects'], function($p) use ($deleteId) {
        return $p['id'] !== $deleteId;
    }));
    saveData($data);
    $message = 'PJを削除しました';
    $messageType = 'success';
}

// 担当者追加
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignee'])) {
    $assigneeName = trim($_POST['assignee_name'] ?? '');

    if ($assigneeName) {
        // 重複チェック
        $exists = false;
        foreach ($data['assignees'] as $a) {
            if ($a['name'] === $assigneeName) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            $message = 'この担当者は既に登録されています';
            $messageType = 'danger';
        } else {
            $maxId = 0;
            foreach ($data['assignees'] as $a) {
                if ($a['id'] > $maxId) $maxId = $a['id'];
            }
            $data['assignees'][] = ['id' => $maxId + 1, 'name' => $assigneeName];
            saveData($data);
            $message = '担当者を追加しました';
            $messageType = 'success';
        }
    }
}

// 担当者削除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assignee'])) {
    $deleteId = (int)$_POST['delete_assignee'];
    $data['assignees'] = array_values(array_filter($data['assignees'], function($a) use ($deleteId) {
        return $a['id'] !== $deleteId;
    }));
    saveData($data);
    $message = '担当者を削除しました';
    $messageType = 'success';
}

// 自動同期設定の保存
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sync_settings'])) {
    $syncUrl = trim($_POST['sync_url'] ?? '');

    $data['settings']['spreadsheet_url'] = $syncUrl;
    saveData($data);

    $message = '自動同期設定を保存しました';
    $messageType = 'success';
}

// ワンクリック同期（PJマスタ）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_now'])) {
    $url = isset($data['settings']['spreadsheet_url']) ? $data['settings']['spreadsheet_url'] : '';

    if (empty($url)) {
        $message = '同期URLが設定されていません。先に自動同期設定を保存してください。';
        $messageType = 'danger';
    } else {
        // URLをCSV形式に変換
        if (strpos($url, '/edit') !== false) {
            preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $url, $matches);
            if (isset($matches[1])) {
                $url = 'https://docs.google.com/spreadsheets/d/' . $matches[1] . '/export?format=csv';
            }
        }

        $csvContent = @file_get_contents($url);

        if ($csvContent === false) {
            $message = 'スプレッドシートを取得できませんでした。公開設定とURLを確認してください。';
            $messageType = 'danger';
        } else {
            $lines = explode("\n", $csvContent);

            $debugInfo = array();

            // 1-23行目をスキップ（24行目がヘッダー）
            for ($i = 0; $i < 23; $i++) {
                array_shift($lines);
            }

            // 24行目をヘッダーとして使用
            $headers = str_getcsv(array_shift($lines));
            $headers = array_map(function($h) { return trim($h); }, $headers);

            $addedPj = 0;
            $addedAssignee = 0;
            $skippedPj = 0;
            $skippedAssignee = 0;
            $totalRows = 0;

            // デバッグ: ヘッダー情報を記録
            $debugInfo[] = '【24行目をヘッダーとして使用】';
            $debugInfo[] = '列名: ' . implode(', ', $headers);

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                $totalRows++;

                $values = str_getcsv($line);

                // 列数を調整（ヘッダーと同じ数に）
                if (count($values) > count($headers)) {
                    $values = array_slice($values, 0, count($headers));
                } else {
                    $values = array_pad($values, count($headers), '');
                }

                $row = array_combine($headers, $values);

                // PJマスタインポート
                $pjNumber = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $row['PJ番号'] ?? ''));
                $pjName = $row['案件名'] ?? $row['現場名'] ?? $row['契約名'] ?? $row['契約者名'] ?? $row['スペース'] ?? $row['スペース名'] ?? '';
                $assignee = $row['YA担当'] ?? $row['担当者'] ?? $row['スペース担当者名'] ?? '';

                // デバッグ: 最初の3行のデータを記録
                if ($totalRows <= 3) {
                    $debugInfo[] = "行{$totalRows}: PJ={$pjNumber}, 名前={$pjName}, 担当={$assignee}";
                }

                if ($pjNumber && $pjName && $pjName !== '-') {
                    $exists = false;
                    foreach ($data['projects'] as $p) {
                        if ($p['id'] === $pjNumber) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $data['projects'][] = ['id' => $pjNumber, 'name' => $pjName];
                        $addedPj++;
                    } else {
                        $skippedPj++;
                    }
                }

                if ($assignee && $assignee !== '-') {
                    $exists = false;
                    foreach ($data['assignees'] as $a) {
                        if ($a['name'] === $assignee) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $maxId = 0;
                        foreach ($data['assignees'] as $a) {
                            if ($a['id'] > $maxId) $maxId = $a['id'];
                        }
                        $data['assignees'][] = ['id' => $maxId + 1, 'name' => $assignee];
                        $addedAssignee++;
                    } else {
                        $skippedAssignee++;
                    }
                }
            }

            saveData($data);

            $message = "同期完了: PJ {$addedPj}件、担当者 {$addedAssignee}件を追加しました";
            if ($skippedPj > 0 || $skippedAssignee > 0) {
                $message .= "（PJ {$skippedPj}件、担当者 {$skippedAssignee}件は既存のためスキップ）";
            }
            $message .= " / スプシ全{$totalRows}行を処理";
            if (!empty($debugInfo)) {
                $message .= "<br><br>【デバッグ情報】<br>" . implode('<br>', $debugInfo);
            }
            $messageType = 'success';
        }
    }
}

require_once 'header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- PJマスタ自動同期設定 -->
<div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
    <h2 class="card-title" style="color: white;">⚡ PJマスタ自動同期</h2>

    <div style="background: rgba(255,255,255,0.1); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">
            PJマスタのスプレッドシートURLを登録すると、ワンクリックで最新のPJ・担当者を同期できます
        </p>
        <p style="font-size: 0.75rem; opacity: 0.9;">
            ※スプシを「ウェブに公開」または「リンクを知っている全員が閲覧可」に設定してください
        </p>
        <p style="font-size: 0.75rem; opacity: 0.9; margin-top: 0.5rem;">
            ※列名: PJ番号、案件名（または現場名）、YA担当（または担当者）
        </p>
    </div>

    <form method="POST" style="margin-bottom: 1.5rem;">
        <div class="form-group">
            <label class="form-label" style="color: white;">PJマスタスプレッドシートURL</label>
            <input type="text" class="form-input" name="sync_url"
                   value="<?= htmlspecialchars(isset($data['settings']['spreadsheet_url']) ? $data['settings']['spreadsheet_url'] : '') ?>"
                   placeholder="https://docs.google.com/spreadsheets/d/...">
        </div>
        <button type="submit" name="save_sync_settings" class="btn btn-primary" style="background: white; color: var(--primary);">
            設定を保存
        </button>
    </form>

    <?php if (!empty($data['settings']['spreadsheet_url'])): ?>
        <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem;">
            <form method="POST" onsubmit="return confirm('スプレッドシートから最新のPJマスタを同期しますか？');">
                <button type="submit" name="sync_now" class="btn btn-primary" style="background: rgba(255,255,255,0.9); color: var(--primary); font-weight: 600;">
                    🔄 今すぐ同期
                </button>
                <p style="font-size: 0.75rem; margin-top: 0.5rem; opacity: 0.8;">
                    最終設定: <?= htmlspecialchars(substr($data['settings']['spreadsheet_url'], 0, 50)) ?>...
                </p>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- PJマスタ登録 -->
<div class="card">
    <h2 class="card-title">PJマスタ登録</h2>
    <form method="POST">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div class="form-group" style="flex: 0 0 120px;">
                <label class="form-label required">PJ番号</label>
                <input type="text" class="form-input" name="pj_number" placeholder="001" required>
            </div>
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label class="form-label required">現場名</label>
                <input type="text" class="form-input" name="pj_name" placeholder="現場名を入力" required>
            </div>
            <div class="form-group" style="flex: 0 0 auto; display: flex; align-items: flex-end;">
                <button type="submit" name="add_pj" class="btn btn-primary">追加</button>
            </div>
        </div>
    </form>
</div>

<!-- PJ一覧 -->
<div class="card">
    <h2 class="card-title">PJ一覧 <span style="font-size: 0.875rem; color: var(--gray-500);">（<?= count($data['projects']) ?>件）</span></h2>
    <div class="table-wrapper" style="max-height: 400px; overflow-y: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>PJ番号</th>
                    <th>現場名</th>
                    <th>トラブル件数</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['projects'] as $pj): ?>
                    <?php
                    $troubleCount = count(array_filter($data['troubles'], function($t) use ($pj) {
                        return $t['pjNumber'] === $pj['id'];
                    }));
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($pj['id']) ?></strong></td>
                        <td><?= htmlspecialchars($pj['name']) ?></td>
                        <td><?= $troubleCount ?>件</td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('削除しますか？');">
                                <input type="hidden" name="delete_pj" value="<?= htmlspecialchars($pj['id']) ?>">
                                <button type="submit" class="btn-icon" title="削除">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($data['projects'])): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--gray-500);">データがありません</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 担当者マスタ -->
<div class="card">
    <h2 class="card-title">担当者マスタ</h2>
    <form method="POST">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label class="form-label required">担当者名</label>
                <input type="text" class="form-input" name="assignee_name" placeholder="担当者名を入力" required>
            </div>
            <div class="form-group" style="flex: 0 0 auto; display: flex; align-items: flex-end;">
                <button type="submit" name="add_assignee" class="btn btn-primary">追加</button>
            </div>
        </div>
    </form>
    <div style="margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
        <?php foreach ($data['assignees'] as $a): ?>
            <span style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--gray-100); padding: 0.5rem 1rem; border-radius: 9999px;">
                <?= htmlspecialchars($a['name']) ?>
                <form method="POST" style="display: inline;" onsubmit="return confirm('削除しますか？');">
                    <input type="hidden" name="delete_assignee" value="<?= $a['id'] ?>">
                    <button type="submit" style="background: none; border: none; cursor: pointer; color: var(--gray-500);">&times;</button>
                </form>
            </span>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
