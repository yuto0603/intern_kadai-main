
function BoxManageViewModel(initialBoxes) {
    var self = this;

    self.boxes = ko.observableArray(initialBoxes.map(function(box) {
        
        return {
            box_id: box.box_id,
            label: ko.observable(box.label), 
            status: ko.observable(box.status),
            current_user_name: ko.observable(box.current_user_name)
        };
    }));

    // 新しい備品のラベル用 observable
    self.newBoxLabel = ko.observable('');

    // CSRFトークンを保持するobservable
    self.csrf_token = ko.observable('');

    // 新しい備品追加ボタンの活性化ロジック
    self.canAddBox = ko.pureComputed(function() {
        return self.newBoxLabel().trim() !== '';
    });

    // 備品追加処理 (Ajax連携)
    self.addBox = function() {
        if (self.canAddBox()) {
            var newLabel = self.newBoxLabel().trim();

            $.ajax({
                url: '/box/api/create', // 追加用APIのエンドポイント
                type: 'POST',
                data: {
                    label: newLabel,
                    fuel_csrf_token: self.csrf_token() // CSRFトークンを送信
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // サーバーから返された新しい備品データをobservableArrayに追加
                        self.boxes.push({
                            box_id: response.box.box_id,
                            label: ko.observable(response.box.label),
                            status: ko.observable(response.box.status),
                            current_user_name: ko.observable(response.box.current_user_name)
                        });
                        self.newBoxLabel(''); // 入力フィールドをクリア
                        alert('成功: ' + response.message);

                        // Ajax成功時に新しいCSRFトークンがあれば更新
                        if (response.new_csrf_token) {
                            self.csrf_token(response.new_csrf_token);
                        }

                    } else {
                        alert('エラー: ' + response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('通信エラーが発生しました: ' + textStatus + ' ' + errorThrown);
                    console.error('Ajax Error:', jqXHR.responseText);
                }
            });
        }
    };

    // 備品削除処理 (Ajax連携)
    self.deleteBox = function(boxToDelete) {
        if (confirm('本当に「' + boxToDelete.label() + '」を削除しますか？')) {
            $.ajax({
                url: '/box/api/delete/' + boxToDelete.box_id, // 削除用APIのエンドポイント
                type: 'POST',
                data: {
                    fuel_csrf_token: self.csrf_token() // CSRFトークンを送信
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        self.boxes.remove(boxToDelete); // 成功したらリストから削除
                        alert('成功: ' + response.message);

                        // Ajax成功時に新しいCSRFトークンがあれば更新
                        if (response.new_csrf_token) {
                            self.csrf_token(response.new_csrf_token);
                        }

                    } else {
                        alert('エラー: ' + response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('通信エラーが発生しました: ' + textStatus + ' ' + errorThrown);
                    console.error('Ajax Error:', jqXHR.responseText);
                }
            });
        }
    };

    // その他の関数や計算プロパティなどをここに追加...
}