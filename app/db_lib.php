<?php

require_once 'config.php';

function InitDBIfNeeded($dbServer, $dbUser, $dbPass, $dbName)
{
    $dsn = "mysql:dbname=${dbName};host=${dbServer}";
    $dbObj = null;

    try {
        $dbObj = new PDO($dsn, $dbUser, $dbPass);
    } catch (PDOException $e) {
        return ['succeeded' => false, 'message' => 'データベースに接続できませんでした'];
    }

    try {
        $dbObj->query('CREATE TABLE `pubkeys` IF NOT EXISTS (
        `user_index` bigint(20) unsigned NOT NULL,
        `key_name` varchar(255) NOT NULL,
        `key_type` varchar(31) NOT NULL,
        `key_content` text NOT NULL,
        `key_comment` varchar(255) NOT NULL,
        KEY `user_index` (`user_index`),
        CONSTRAINT `pubkeys_ibfk_1` FOREIGN KEY (`user_index`) REFERENCES `user_name` (`user_index`)
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin');
        $dbObj->query('CREATE TABLE `user_name` IF NOT EXISTS (
        `user_index` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `user_name` varchar(255) NOT NULL,
        PRIMARY KEY (`user_index`)
        ) AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin');
    } catch (PDOException $e) {
        return ['succeeded' => false, 'message' => 'テーブルの作成に失敗しました'];
    }
    return ['succeeded' => true, 'message' => ''];
}

function GetKeyListFromDB($dbServer, $dbUser, $dbPass, $dbName, $userName)
{
    $dsn = "mysql:dbname=${dbName};host=${dbServer}";
    $dbObj = null;

    try {
        $dbObj = new PDO($dsn, $dbUser, $dbPass);
    } catch (PDOException $e) {
        return ['succeeded' => false, 'message' => 'データベースに接続できませんでした'];
    }

    try {
        $dbQuery = $dbObj->prepare('select key_name,key_type,key_content,key_comment from pubkeys where user_index in (select user_index from user_name where user_name = ?)');

        if (!$dbQuery->execute([$userName])) {
            return ['succeeded' => false, 'message' => '公開鍵のリストを取得するクエリに失敗しました'];
        }

        $result = $dbQuery->fetchAll();
        return ['succeeded' => true, 'keys' => \array_map(function ($row) {
            return ['name' => $row['key_name'], 'type' => $row['key_type'], 'content' => $row['key_content'], 'comment' => $row['key_comment']];
        }, $result)];
    } catch (PDOException $e) {
        return ['succeeded' => false, 'message' => '公開鍵のリストを取得するクエリに失敗しました'];
    }
}

/**
 * @brief 公開鍵を登録する
 *
 * @param dbServer データベースサーバを表す文字列
 * @param dbUser データベースに接続するユーザ名
 * @param dbPass データベースに接続するパスワード
 * @param dbName データベースの名前
 * @param userName 公開鍵を登録するユーザ名
 * @param keyData 公開鍵(["name" => "公開鍵の名前", "type" => "公開鍵の種類(ssh-ed25519など)", "content" => "公開鍵を表す文字列(Base64)", "comment" => "公開鍵のコメント"])
 *
 * @return ["succeeded" => bool(登録に成功したか), "message" => "エラーメッセージ(登録失敗時のみ)"]
 */
function AddOneKey($dbServer, $dbUser, $dbPass, $dbName, $userName, $keyData)
{
    $dsn = "mysql:dbname=${dbName};host=${dbServer}";
    $dbObj = null;

    if (!isset($dbServer, $dbUser, $dbPass, $dbName, $userName, $keyData)) {
        return ['succeeded' => false, 'message' => '引数が不足しています'];
    }

    if (!\array_key_exists('name', $keyData) || !\array_key_exists('type', $keyData) || !\array_key_exists('content', $keyData) || !\array_key_exists('comment', $keyData)) {
        return ['succeeded' => false, 'message' => '公開鍵を登録するのに必要なデータのいずれかが不足しています'];
    }

    if ($keyData['name'] === '') {
        return ['succeeded' => false, 'message' => '名前が空です'];
    }

    if (!\preg_match('/(ssh-(rsa|dss|ed25519)|ecdsa-sha2-nistp(256|384|521))/', $keyData['type'])) {
        return ['succeeded' => false, 'message' => '公開鍵のタイプ「' . $keyData['type'] . '」は無効です'];
    }

    if (!\preg_match('@[0-9A-Za-z+/]+(==?)?@', $keyData['content'])) {
        return ['succeeded' => false, 'message' => '鍵の内容が有効なBase64文字列ではありません'];
    }

    try {
        $dbObj = new PDO($dsn, $dbUser, $dbPass);
    } catch (PDOException $e) {
        return ['succeeded' => false, 'message' => 'データベースに接続できませんでした'];
    }

    try {
        $dbObj->beginTransaction();
        $dbQuery = $dbObj->prepare('select user_index from user_name where user_name = ?');

        if (!$dbQuery->execute([$userName])) {
            $dbObj->rollBack();
            return ['succeeded' => false, 'message' => 'ユーザインデックスを取得できませんでした'];
        }
        $userIndex = null;

        switch ($rowNum = $dbQuery->rowCount()) {
            case 1:
                $userIndex = $dbQuery->fetch()[0];
                break;
            case 0: // ユーザインデックスに登録
                $dbQuery = $dbObj->prepare('insert into user_name(user_name) values (?)');

                if (!$dbQuery->execute([$userName])) {
                    $dbObj->rollBack();
                    return ['succeeded' => false, 'message' => 'ユーザインデックスを登録できませんでした'];
                }
                $dbQuery = $dbObj->prepare('select user_index from user_name where user_name = ?');

                if (!$dbQuery->execute([$userName]) || $dbQuery->rowCount() !== 1) {
                    $dbObj->rollBack();
                    return ['succeeded' => false, 'message' => '登録したユーザインデックスの再取得に失敗しました'];
                }
                $userIndex = $dbQuery->fetch()[0];
                break;
            default:
                $dbObj->rollBack();
                return ['succeeded' => false, 'message' => $userName . 'のユーザ番号が' . $rowNum . '個登録されています'];
        }
        $dbQuery = $dbObj->prepare('select count(*) from pubkeys where user_index = ? and key_type = ? and key_content = ?');

        if (!$dbQuery->execute([$userIndex, $keyData['type'], $keyData['content']])) {
            $dbObj->rollBack();
            return ['succeeded' => false, 'message' => '登録する公開鍵の重複チェックをするクエリに失敗しました'];
        }

        if ($dbQuery->fetchColumn() > 0) {
            $dbObj->rollBack();
            return ['succeeded' => false, 'message' => 'その鍵はすでに登録されています'];
        }
        $dbQuery = $dbObj->prepare('insert into pubkeys values (?,?,?,?,?)');

        if (!$dbQuery->execute([$userIndex, $keyData['name'], $keyData['type'], $keyData['content'], $keyData['comment']])) {
            $dbObj->rollBack();
            return ['succeeded' => false, 'message' => '公開鍵をリストに登録するクエリに失敗しました'];
        }
        $dbObj->commit();
        return ['succeeded' => true];
    } catch (PDOException $e) {
        $dbObj->rollBack();
        return ['succeeded' => false, 'message' => '公開鍵をリストに登録するクエリに失敗しました'];
    }
}

/**
 * @brief 与えられた公開鍵を削除する
 *
 * @param dbServer データベースサーバを表す文字列
 * @param dbUser データベースに接続するユーザ名
 * @param dbPass データベースに接続するパスワード
 * @param dbName データベースの名前
 * @param userName 公開鍵を削除するユーザ名
 * @param keyData 公開鍵(["name" => "公開鍵の名前", "type" => "公開鍵の種類(ssh-ed25519など)", "content" => "公開鍵を表す文字列(Base64)", "comment" => "公開鍵のコメント"])
 *
 * @return ["succeeded" => bool(削除に成功したか), "message" => "エラーメッセージ(削除失敗時のみ) / 警告メッセージ(削除成功したが削除鍵数が1つでなかった場合)" / false (1つの鍵を削除することに成功した場合)]
 */
function DeleteOneKey($dbServer, $dbUser, $dbPass, $dbName, $userName, $keyData)
{
    $dsn = "mysql:dbname=${dbName};host=${dbServer}";
    $dbObj = null;

    if (!isset($dbServer, $dbUser, $dbPass, $dbName, $userName, $keyData)) {
        return ['succeeded' => false, 'message' => '引数が不足しています'];
    }

    if (!\array_key_exists('name', $keyData) || !\array_key_exists('type', $keyData) || !\array_key_exists('content', $keyData) || !\array_key_exists('comment', $keyData)) {
        return ['succeeded' => false, 'message' => '公開鍵を削除するのに必要なデータのいずれかが不足しています'];
    }

    if ($keyData['name'] === '') {
        return ['succeeded' => false, 'message' => '名前が空です'];
    }

    if (!\preg_match('/(ssh-(rsa|dss|ed25519)|ecdsa-sha2-nistp(256|384|521))/', $keyData['type'])) {
        return ['succeeded' => false, 'message' => '公開鍵のタイプ「' . $keyData['type'] . '」は無効です'];
    }

    if (!\preg_match('@[0-9A-Za-z+/]+(==?)?@', $keyData['content'])) {
        return ['succeeded' => false, 'message' => '鍵の内容が有効なBase64文字列ではありません'];
    }

    try {
        $dbObj = new PDO($dsn, $dbUser, $dbPass);
    } catch (PDOException $e) {
        return ['succeeded' => false, 'message' => 'データベースに接続できませんでした'];
    }

    try {
        $dbObj->beginTransaction();
        $dbQuery = $dbObj->prepare('select user_index from user_name where user_name = ?');

        if (!$dbQuery->execute([$userName])) {
            $dbObj->rollBack();
            return ['succeeded' => false, 'message' => 'ユーザインデックスを取得できませんでした'];
        }
        $userIndex = null;

        if ($dbQuery->rowCount() !== 1) {
            $dbObj->rollBack();
            return ['succeeded' => false, 'message' => $userName . 'のユーザ番号が非ユニークです'];
        }
        $userIndex = $dbQuery->fetch()[0];
        $keyComment = \preg_replace('/(\r|\n).*$/', '', $keyData['comment']);
        $dbQuery = $dbObj->prepare('delete from pubkeys where user_index = ? and key_name = ? and key_type = ? and key_content = ? and key_comment = ?');

        if (!$dbQuery->execute([$userIndex, $keyData['name'], $keyData['type'], $keyData['content'], $keyComment])) {
            $dbObj->rollBack();
            return ['succeeded' => false, 'message' => '公開鍵をリストから削除するクエリに失敗しました'];
        }
        $deletedKeyCount = $dbQuery->rowCount();
        $dbObj->commit();
        $message = false;

        switch ($deletedKeyCount) {
            case 1:
                // 該当する鍵あり(正常)
                break;
            case 0:
                $message = '該当する鍵はリストにはなく、すでに削除されているようです';
                break;
            default:
                $message = '該当する鍵が複数個あったため、全て削除しました';
        }
        return ['succeeded' => true, 'message' => $message];
    } catch (PDOException $e) {
        $dbObj->rollBack();
        return ['succeeded' => false, 'message' => '公開鍵のリストを削除するクエリを実行中に例外を受け取りました'];
    }
}
