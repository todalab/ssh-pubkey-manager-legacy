<?php

require_once 'config.php';
require_once 'db_lib.php';

\header('Content-Type: application/json; charset=utf-8');
function ErrorAndExit($message = ''): void
{
    $outputJSON = ['succeeded' => false, 'message' => $message];
    echo \json_encode($outputJSON);
    exit();
}

if (!($initializationStatus = InitDBIfNeeded($dbServer, $dbUser, $dbPass, $dbName))['succeeded']) {
    echo \json_encode($initializationStatus);
    exit();
}

\session_start();

if (!$_SESSION || empty($_SESSION['state'])) {
    ErrorAndExit('認証がされていません');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ErrorAndExit('POSTのみ受け付けています');
}

$data = \json_decode(\file_get_contents('php://input'), true);

$operation = $data['operation'];
$targetUser = $data['targetUser'];

switch ($operation) {
    case 'get':
        if ($targetUser !== $_SESSION['userName']) {
            ErrorAndExit('現在はログインしているユーザの鍵しか取得できません');
        }

        echo \json_encode(GetKeyListFromDB($dbServer, $dbUser, $dbPass, $dbName, $targetUser));
        break;
    case 'add':
        if ($targetUser !== $_SESSION['userName']) {
            ErrorAndExit('現在はログインしているユーザの鍵しか登録できません');
        }

        if (!\array_key_exists('key', $data)) {
            ErrorAndExit('公開鍵の情報が与えられていません');
        }

        echo \json_encode(AddOneKey($dbServer, $dbUser, $dbPass, $dbName, $targetUser, $data['key']));
        break;
    case 'delete':
        if ($targetUser !== $_SESSION['userName']) {
            ErrorAndExit('現在はログインしているユーザの鍵しか削除できません');
        }

        if (!\array_key_exists('key', $data)) {
            ErrorAndExit('公開鍵の情報が与えられていません');
        }

        echo \json_encode(DeleteOneKey($dbServer, $dbUser, $dbPass, $dbName, $targetUser, $data['key']));
        break;
    default:
        ErrorAndExit('operationの値が不正か、指定されていません');
        break;
}
