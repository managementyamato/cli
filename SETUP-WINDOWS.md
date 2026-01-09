# Windows環境セットアップガイド

このガイドに従って、YA管理一覧システムの開発環境を構築してください。

## 📋 最短セットアップ（3ステップ）

1. **PHP** と **Git** をインストール
2. コマンドプロンプトで以下を実行:
   ```bash
   git clone https://github.com/managementyamato/cli.git
   cd cli
   git checkout claude/audit-dependencies-mk0uc1heu3tc90mg-wFx3f
   php -S localhost:8000
   ```
3. ブラウザで **http://localhost:8000** を開く

**注意:** Apache、MySQL、XAMPPのコントロールパネルは不要です！

---

## 必要なソフトウェアのインストール

### 1. PHP のインストール

**方法A: PHP単体インストール（推奨 - シンプル）**

1. [PHP公式サイト](https://windows.php.net/download/) にアクセス
2. 「PHP 8.3」の「VS16 x64 Non Thread Safe」の「Zip」をダウンロード
3. ダウンロードしたZIPを `C:\php` に解凍
4. 環境変数PATHに `C:\php` を追加
   - Windowsキー → 「環境変数」で検索
   - 「システム環境変数の編集」を開く
   - 「環境変数」ボタン → 「Path」を選択 → 「編集」
   - 「新規」→ `C:\php` を追加 → OK

**方法B: XAMPP経由（PHPコマンドのみ使用）**

1. [XAMPP公式サイト](https://www.apachefriends.org/jp/index.html) でダウンロード
2. インストール（Apache/MySQLは起動不要）
3. 環境変数PATHに `C:\xampp\php` を追加

**注意:** このプロジェクトではApache/MySQLは使用しません。PHPの組み込みサーバーのみ使用します。

**インストール後の確認:**
```bash
# コマンドプロンプトで実行
php -v
```

### 2. Git のインストール

1. [Git for Windows](https://git-scm.com/download/win) にアクセス
2. 「Download for Windows」をクリック
3. インストーラーを実行（デフォルト設定でOK）

**インストール後の確認:**
```bash
git --version
```

### 3. Node.js のインストール（オプション - ライブリロード機能が必要な場合）

1. [Node.js公式サイト](https://nodejs.org/ja/) にアクセス
2. 「LTS（推奨版）」をダウンロード
3. インストーラーを実行（デフォルト設定でOK）

**インストール後の確認:**
```bash
node -v
npm -v
```

---

## プロジェクトのセットアップ

### ステップ1: コードを取得

```bash
# 作業フォルダに移動（例: デスクトップ）
cd C:\Users\yamato\Desktop

# リポジトリをクローン（GitHubのURLに置き換えてください）
git clone https://github.com/managementyamato/cli.git
cd cli

# 最新の開発ブランチに切り替え
git checkout claude/audit-dependencies-mk0uc1heu3tc90mg-wFx3f
```

### ステップ2: 依存関係をインストール（ライブリロード使う場合のみ）

```bash
npm install
```

### ステップ3: データファイルの初期化（自動生成されるのでスキップ可能）

初回起動時に自動で作成されますが、手動で作成する場合：

```bash
# 空のJSONファイルを作成
echo {} > data.json
echo {} > users.json
echo {} > mf-config.json
```

---

## 開発サーバーの起動

### 方法A: シンプルなPHPサーバー（推奨）

```bash
# プロジェクトフォルダで実行
php -S localhost:8000
```

ブラウザで開く: **http://localhost:8000**

### 方法B: ライブリロード付き（Node.js必須）

```bash
npm run dev
```

ブラウザで開く: **http://localhost:3000**

ファイルを編集すると自動でブラウザがリロードされます。

---

## トラブルシューティング

### PHPコマンドが認識されない

環境変数PATHにPHPのパスが追加されているか確認してください:
- PHP単体: `C:\php`
- XAMPP経由: `C:\xampp\php`

**環境変数の確認方法:**
```bash
echo %PATH%
```

**環境変数の追加方法:**
1. Windowsキー → 「環境変数」で検索
2. 「システム環境変数の編集」を開く
3. 「環境変数」ボタンをクリック
4. 「Path」を選択 → 「編集」
5. 「新規」→ PHPのパスを追加
6. コマンドプロンプトを**再起動**（重要）

### ポート8000が使用中のエラー

別のポート番号を使用してください:
```bash
php -S localhost:8080
```

### データが表示されない

初回起動時はデータが空です。管理画面からデータを入力してください。

---

## 初回ログイン

初期ユーザーは自動作成されません。以下の手順で作成してください:

1. ブラウザで `http://localhost:8000/setup.php` にアクセス
2. 管理者アカウントを作成
3. ログイン画面からログイン

---

## MoneyForward連携設定

1. ログイン後、「MF連携設定」メニューを開く
2. MoneyForwardで取得した「Client ID」と「Client Secret」を入力
3. 「OAuth2認証を開始」ボタンをクリック
4. MoneyForwardの認証画面で承認

---

## よくある質問

**Q: XAMPPをインストールしたけどApacheやMySQLは起動する必要がある？**
A: いいえ、不要です。このプロジェクトはPHPコマンドのみ使用します。XAMPPのコントロールパネルは起動しなくてOKです。

**Q: データベースは必要ない？**
A: 不要です。このシステムはJSONファイルでデータを保存します（data.json, users.json）。

**Q: npm run dev が失敗する**
A: Node.jsがインストールされているか確認してください。不要であれば `php -S localhost:8000` のみで十分動作します。

**Q: ファイルを編集しても反映されない**
A: `php -S localhost:8000` の場合、ブラウザを手動でリロード（F5）してください。自動リロードが欲しい場合は `npm run dev` を使用してください。

**Q: 本番環境へのデプロイ方法は？**
A: 本番環境（https://cil.yamato-basic.com）にSSH接続し、`git pull` でコードを更新してください。

---

## サポート

問題が解決しない場合は、以下の情報を添えてお問い合わせください:
- エラーメッセージのスクリーンショット
- 実行したコマンド
- 使用しているWindowsのバージョン
