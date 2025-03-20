<?php

require_once 'vendor/autoload.php';

// .envファイルを読み込む
if (\file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::create(__DIR__);
    $dotenv->load();
}

function GetConfigFromEnvOrDie($name)
{
    $valueOrFalse = \getenv($name);

    if ($valueOrFalse !== false) {
        return $valueOrFalse;
    }
    \http_response_code(500);
    echo ".envなどに環境変数「${name}」を設定してください\n";
    exit();
}

$ldapServer = GetConfigFromEnvOrDie('LDAP_SERVER');
$ldapBaseDN = GetConfigFromEnvOrDie('LDAP_BASE_DN');
$ldapUserRootDN = GetConfigFromEnvOrDie('LDAP_USER_ROOT_DN');
// 後方互換のため、先頭の「,」があれば除去
if (substr($ldapUserRootDN, 0, 1) === ",") {
    $ldapUserRootDN = substr($ldapUserRootDN, 1);
}
$ldapGroupRootDN = GetConfigFromEnvOrDie('LDAP_GROUP_ROOT_DN');
$ldapBindDN = GetConfigFromEnvOrDie('LDAP_BIND_DN');
$ldapPassword = GetConfigFromEnvOrDie('LDAP_PASSWORD');

//! データベースサーバ
$dbServer = GetConfigFromEnvOrDie('DB_SERVER');

//! データベースユーザ名
$dbUser = \getenv('DB_USER') ?: 'root';

//! データベースパスワード
$dbPass = GetConfigFromEnvOrDie('DB_PASS');

//! データベース名
$dbName = \getenv('DB_NAME') ?: 'pubkey_manager';

//! アプリ名
$appName = \getenv('APP_NAME') ?: '公開鍵管理システム';
