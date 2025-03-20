<?php

function redirectToLoginPage(): void
{
    \header('Location: login.php', true, 307);
    echo "Redirecting to the login page...\n";
    exit();
}

\session_start();

if (!$_SESSION || empty($_SESSION['state'])) {
    redirectToLoginPage();
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>鍵管理ページ -
        <?php echo $appName; ?>
    </title>
    <!-- jQuery JS 3.1.0 -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <!-- Bootstrap JS 3.3.7 -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <!-- Bootstrap CSS 3.3.7 -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <!-- AngularJS JS 1.5.7 -->
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.7/angular.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-bootstrap/2.2.0/ui-bootstrap-tpls.min.js"></script>
</head>

<body ng-app="pubkeyManager">
    <!-- 1.ナビゲーションバーの設定 -->
    <nav class="navbar navbar-default">
        <div class="container">
            <!-- 2.ヘッダ情報 -->
            <div class="navbar-header">
                <a class="navbar-brand"><span class="glyphicon glyphicon-user"></span> <?=$_SESSION['userName']; ?></a>
            </div>
            <!-- 3.リストの配置 -->
            <ul class="nav navbar-nav text-right">
                <li><a href="logout.php"><span class="glyphicon glyphicon-log-out"></span> ログアウト</a></li>
            </ul>
        </div>
    </nav>
    <hgroup class="text-center">
        <h1><?php echo $appName; ?>
        </h1>
        <h2>鍵管理ページ</h2>
    </hgroup>
    <div ng-controller="pubkeyListController as ctl" class="container">
        <!-- 公開鍵登録画面(モーダル) -->
        <script type="text/ng-template" id="keyAddModalContent">
            <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h4 class="modal-title" id="keyAddModalLabel">公開鍵の追加</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="key_name"><span class="glyphicon glyphicon-tags"></span> 公開鍵の名前</label>
            <input type="text" placeholder="公開鍵の名前" ng-model="keyName">
          </div>
          <div class="form-group">
            <label for="key_whole_data"><span class="glyphicon glyphicon-file"></span> 公開鍵ファイル</label>
            <input type="file" ng-model="keyDataFile" pubkey-file>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-default" ng-click="CloseAddKeyModal()"><span class="glyphicon glyphicon-close"></span> キャンセル</button>
          <button class="btn btn-primary" ng-click="ExecAddKey()"><span class="glyphicon glyphicon-ok"> 追加</span></button>
        </div>
      </script>
        <!--ここまで-->
        <div class="text-center">
            <button type="button" class="btn btn-primary" ng-click="ctl.OpenAddKeyModal()"><span class="glyphicon glyphicon-plus"></span>
                追加
            </button>
        </div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>鍵の名前</th>
                    <th>暗号の種類</th>
                    <th>鍵の内容</th>
                    <th>コメント</th>
                    <th>削除</th>
                </tr>
            </thead>
            <tbody>
                <tr ng-repeat="key in ctl.keys" ng-cloak>
                    <td>{{key.name}}</td>
                    <td><span ng-class="valueFormatter.FormatKeyTypeClass(key.type)">{{valueFormatter.FormatKeyType(key.type)}}</span>
                    </td>
                    <td>{{valueFormatter.FormatKeyContent(key.content)}}</td>
                    <td>{{key.comment}}</td>
                    <td>
                        <button class="btn btn-default" ng-click="ctl.DeleteOneKey($index)"><span class="glyphicon glyphicon-trash"></span></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <script>
        myID = "<?=$_SESSION['userName']; ?>";

    </script>
    <script src="manager.js"></script>
</body>

</html>
