/*!
    @attention 関数名はパスカルケース(PascalCase)、それ以外はキャメルケース(camelCase)
*/

/*  @brief モジュールの書き方

    @param モジュール名(ng-app属性で指定)
    @param 取り込む外部モジュールのリスト
*/
app = angular.module("pubkeyManager", ["ui.bootstrap"]);

/*  @brief サービス・コントローラの書き方

    引数は次の通り
    @param サービス・コントローラなどの名前 
    @param 最後以外は使用する他のサービス・コントローラなどの名前($付きはビルトイン)、最後は定義するサービス・コントローラなどの内容を表す関数(関数の引数にはそれより前の名前をそのまま与える)からなる配列
*/
app.service("valueFormatter", [function() {
    this.keyTypeTable = {
        "ssh-rsa": {type: "RSA", colorClass: "text-primary"},
        "ssh-dss": {type: "DSA", colorClass: "text-danger"},
        "ecdsa-sha2-nistp256": {type: "ECDSA(256bit)", colorClass: "text-success"},
        "ecdsa-sha2-nistp384": {type: "ECDSA(384bit)", colorClass: "text-success"},
        "ecdsa-sha2-nistp521": {type: "ECDSA(521bit)", colorClass: "text-success"},
        "ssh-ed25519": {type: "Ed25519", colorClass: "text-success"}
    };
    this.FormatKeyType = function(keyTypeStr) {
        var ret = this.keyTypeTable[keyTypeStr];
        return ret === void 0 ? keyTypeStr : ret.type;
    };
    this.FormatKeyTypeClass = function(keyTypeStr) {
        var ret = this.keyTypeTable[keyTypeStr];
        return ret === void 0 ? "text-muted" : ret.colorClass;
    };
    this.FormatKeyContent = function(keyContent) {
        return "........." + keyContent.substr(-8); 
    };
}]);

app.constant("dataManagerURL", "managekeylist.php");

/*! @brief メインコントローラ
    
*/
app.controller("pubkeyListController", ["$scope","valueFormatter", "$timeout", "$http", "$uibModal", "dataManagerURL", function($scope, valueFormatter, $timeout, $http, $uibModal, dataManagerURL) {
    $scope.valueFormatter = valueFormatter;
    this.keys = [];
    THIS = this;

    //!<
    this.OpenAddKeyModal = function() {
        var modalObj = $uibModal.open({
            templateUrl: "keyAddModalContent",
            controller: 'pubkeyAddModalController',
        });
        modalObj.result.then(function(addKeyObj) {
            $http({
                method: "POST",
                url: dataManagerURL,
                data: 
                {
                    targetUser: myID,
                    operation: "add",
                    key: addKeyObj,
                }
            }).success(function(data) {
                if(data.succeeded) THIS.keys.push(addKeyObj);
                else alert("鍵の追加に失敗しました\n" + data.message);
            }).error(function(data,status) {
                alert("鍵の追加に失敗しました");
            });
        }, function() {
            // キャンセルされた
        });
    };

    this.DeleteOneKey = function(index) {
        $http({
            method: "POST",
            url: dataManagerURL,
            data: 
            {
                targetUser: myID,
                operation: "delete",
                key: THIS.keys[index],
            }
        }).success(function(data) {
            if(data.succeeded) {
                if(data.message !== false) {
                    alert("鍵の削除には成功しましたが、警告があります\n" + data.message);
                }
                THIS.keys.splice(index, 1);
            }
            else alert("鍵の削除に失敗しました\n" + data.message);
        }).error(function(data,status) {
            alert("鍵の削除に失敗しました");
        });
    };

    this.GetKeyList = function() {
        $http({
            method: "POST",
            url: dataManagerURL,
            data: {operation: "get",
                targetUser: myID}
        }).success(function(data) {
            if(data.succeeded == false) {
                var errorMessage = "データの取得に失敗しました";
                if(data.message != "") errorMessage += "\n" + data.message;
                alert(errorMessage);
                return;
            }
            THIS.keys.length = 0; // 削除
            Array.prototype.push.apply(THIS.keys, data.keys); // 末尾に追加
        }).error(function(data,status) {
            alert("データの取得に失敗しました");
            THIS.keys.length = 0;
        });
    };

    this.GetKeyList();
}]);

app.controller("pubkeyAddModalController", ["$scope", "$uibModalInstance", function($scope, $uibModalInstance) {
    $scope.keyName = ""; //!< 鍵の名前の入力フォームにバインド
    $scope.keyDataFile = null; //!< 鍵のファイルにバインド

    /*! @brief キャンセルボタンを押したときに呼ばれる関数
        モーダルダイアログを閉じる。
    */
    $scope.CloseAddKeyModal = function() {
        $uibModalInstance.dismiss("cancel");
    };

    /*! @brief 登録ボタンを押したとき呼ばれる関数
        まず、鍵の名前・ファイルが未指定かどうかチェックする。未指定なら、指定を促して登録をキャンセルする。
        指定されていたら、鍵ファイルを読み込む。
        読み込んだら、「OpenSSH形式の」「公開鍵か」どうかチェックし、違うなら、「ファイルを未指定にして」登録をキャンセルする。
        また、DSA・RSAの場合、2048bit未満の鍵は拒否する。
        公開鍵の形式は「DSA」「RSA」「ECDSA」(ecdsa-sha2-nistpXXX)「Ed25519」のいずれかである。
    */
    $scope.ExecAddKey = function() {
        if($scope.keyName == "") {
            alert("鍵の名前を入力してください");
            return;
        }
        if($scope.keyDataFile === null) {
            alert("公開鍵ファイルを選択してください");
            return;
        }
        var fileReader = new FileReader();
        fileReader.onload = function(event) {
            var rawContent = event.target.result;
            var space1 = rawContent.indexOf(" ");
            if(space1 == -1) {
                alert("これは公開鍵ファイルではありません");
                $scope.$apply(function() {
                    $scope.keyDataFile = null;
                });
                return;
            }
            var space2 = rawContent.indexOf(" ", space1 + 1);
            if(space2 == -1) {
                alert("これは公開鍵ファイルではありません");
                $scope.$apply(function() {
                    $scope.keyDataFile = null;
                });
                return;
            }
            var keyType = rawContent.slice(0,space1);
            var keyContent = rawContent.slice(space1 + 1,space2);
            var keyComment = rawContent.slice(space2 + 1);
            keyComment = keyComment.replace(/(\r|\n).*$/, "");
            if(/^-+BEGIN$/.test(keyType) && /^PRIVATE KEY-+/.test(keyComment)) {
                alert("これは秘密鍵ファイルです\n「"+ $scope.keyDataFile.name + ".pub」があればそれが公開鍵なので選んでください");
                $scope.$apply(function() {
                    $scope.keyDataFile = null;
                });
                return;
            }
            if(keyType == "PuTTY-User-Key-File-2:") {
                alert("これはPuTTY形式の秘密鍵ファイルです\nPuTTYgenでOpenSSH形式の公開鍵ファイルを生成してください");
                $scope.$apply(function() {
                    $scope.keyDataFile = null;
                });
                return;
            }
            if(/^-+$/.test(keyType) && keyContent == "SSH2" && /^PUBLIC KEY -+/.test(keyComment)) {
                alert("これはssh.com形式の公開鍵です\nOpenSSH形式に変換してください");
                $scope.$apply(function() {
                    $scope.keyDataFile = null;
                });
                return;
            }
            if(!/(ssh-(rsa|dss|ed25519)|ecdsa-sha2-nistp(256|384|521))/.test(keyType) || !/^[0-9A-Za-z+/]+(==?)?$/.test(keyContent)) {
                alert("これは公開鍵ファイルではありません");
                $scope.$apply(function() {
                    $scope.keyDataFile = null;
                });
                return;
            }
            if(keyType == "ssh-rsa" && keyContent.length <= 360 || keyType == "ssh-dss" && keyContent.length <= 690) {
                alert("鍵長が短すぎます\nECDSA・Ed25519鍵を使うか2048bit以上の鍵を指定してください");
                $scope.$apply(function() {
                    $scope.keyDataFile = null;
                });
                return;
            }
            
            $uibModalInstance.close({
                name: $scope.keyName,
                type: keyType,
                content: keyContent,
                comment: keyComment
            });
        };
        fileReader.onerror = function() {
            alert("エラー: " + $scope.keyDataFile.name + " が開けませんでした");
        };
        fileReader.readAsText($scope.keyDataFile);
    };
}]);

/*! @brief コメント
    input(file)の属性に「ng-model="HOGE"」「pubkey-file」を追加(HOGEにバインドされる)

*/
app.directive("pubkeyFile", [function () {
    return {
        require: "ngModel",
        restrict: 'A',
        link: function ($scope, el, attrs, ngModel) {
            el.bind('change', function (event) {
                ngModel.$setViewValue(event.target.files[0]);
                $scope.$apply();
            });

            $scope.$watch(function () {
                return ngModel.$viewValue;
            }, function (value) {
                if (!value) {
                    el.val("");
                }
            });
        }
    };
}]);

app.controller("naviController", ["$scope","$uibModal",function ($scope,$uibModal) {
    this.editingID = "";
    this.editingOthers = false;
    var THIS = this;

    this.ShowSwitchEditingUserModal = function() {
        var modalObj = $uibModal.open({
            templateUrl: "switchEditingUserModalContent",
            controller: 'switchEditUserModalController',
        });
        modalObj.result.then(function (nextID) {
            if(nextID == myID) {
                THIS.editingID = "";
                THIS.editingOthers = false;
            } else {
                THIS.editingID = nextID;
                THIS.editingOthers = true;
            }
        }/* , キャンセル */);
    };
}]);

app.controller("switchEditUserModalController", ["$scope", "$http","$uibModalInstance", function($scope, $http, $uibModalInstance) {
    $scope.userName = "";

    $scope.ExecSwitchUser = function() {
        if($scope.userName == "") {
            alert("切り替えたいユーザ名を入力してください");
            return;
        }
        $uibModalInstance.close($scope.userName);
    };
    $scope.CloseSwitchUserModal = function() {
        $uibModalInstance.dismiss("cancel");
    };
}]);