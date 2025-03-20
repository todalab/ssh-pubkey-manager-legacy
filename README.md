# 公開鍵管理システム

## 要件

- ユーザは LDAP で管理
- SSH 鍵は MySQL に保存 (MariaDB 可)
- SSH ログイン試行時には MySQL サーバから直接 SSH 鍵を読み出し

## デプロイ方法

### アプリ編

- `app` ディレクトリ内で `composer install`
- `/app/.env.example`をコピーして`/app/.env`にし、設定を適当なものに書き換える
- `nginx.conf`・`.htaccess`に以下のような設定を追加
  - `/`へのアクセスは`/app`に 302 で飛ばす
  - 以下のファイル・ディレクトリへのアクセスを拒否(404 での偽装を推奨)
    - `/.git`
    - `/.giignore`
    - `/.editorconfig`
    - `/app/composer*`
    - `/app/.env`
    - `/app/vendor`
    - `/*.md`
  - (Nginx の場合)`/app/*.php`にアクセスした時に PHP-FPM にプロキシするように設定
  - `index.php`をディレクトリインデックスの候補に追加

### DB 編

- MySQL に新しいデータベースを作成 (名前を`.env`の`DB_NAME`に設定) (以下`pubkey_manager`とする) (`CREATE DATABASE IF NOT EXISTS pubkey_manager`)
- MySQL にアプリ (ホスティングするサーバのIP例: `192.168.0.2`) 用のユーザ・ログインノード (例: `192.168.0.*`) からのアクセス用のユーザ (以下それぞれ`pubkey_mgr_rw`・`pubkey_mgr_ro`とする) を作成(`CREATE USER pubkey_mgr_rw@192.168.0.2 IDENTIFIED BY '[パスワード]'; CREATE USER pubkey_mgr_ro@'192.168.0.%' IDENTIFIED BY '[パスワード]'`)、どちらも作成したデータベースへのアクセスのみを許可
  - アプリ用のユーザはアプリをホスティングするサーバからのアクセスのみを許可 (`GRANT ALL PRIVILEGES ON pubkey_manager.* TO pubkey_mgr_rw@192.168.0.2`)
  - ログインノードからのアクセス用のユーザはログインノードからのアクセスのみを許可し、データベースへの書き込みを禁止する (読み取り専用) (`GRANT SELECT ON pubkey_manager.* TO pubkey_mgr_ro@'192.168.0.%'`)

### ログインノード編

- (TBD)
