<!DOCTYPE html>
<html>
<head>
    <title>備品管理 (Equipment Management)</title>
    <link rel="stylesheet" href="/assets/css/manage.css">
    <link rel="stylesheet" href="/assets/css/style.css">

</head>
<body>
    <h1 class="header-title">備品貸出管理 (Equipment Loan Management)</h1>

     <div class="tab-nav">
        <a href="<?php echo Uri::base(); ?>box" class="tab-nav-item">備品一覧 (Equipment List)</a>
        <a href="<?php echo Uri::base(); ?>box/manage" class="tab-nav-item active">備品管理 (Equipment Management)</a>
    </div>


    <?php if (isset($flash_message_success)): ?>
        <div class="alert alert-success">
            <?php echo $flash_message_success; ?>
        </div>
    <?php endif; ?>
    <?php if (isset($flash_message_error)): ?>
        <div class="alert alert-danger">
            <?php echo $flash_message_error; ?>
        </div>
    <?php endif; ?>

    <p>
        <input type="text" data-bind="textInput: newBoxLabel" placeholder="新しい備品ラベル (New Item Label)" class="form-control" style="display:inline-block; width: auto; margin-right: 10px;">
        <button type="button" class="btn btn-success" data-bind="click: addBox, enable: canAddBox">新しい備品を追加 (Add New Item)</button>
    </p>

    <div data-bind="if: boxes().length > 0">
        <h3>モニター (Monitor)</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ラベル (Label)</th>
                    <th>種類 (Type)</th>
                    <th>アクション (Actions)</th>
                </tr>
            </thead>
            <tbody data-bind="foreach: boxes">
                <tr>
                    <td data-bind="text: 'B-' + box_id"></td>
                    <td data-bind="text: label"></td>
                    <td>モニター (Monitor)</td> <td>
                        <button type="button" class="btn btn-danger btn-sm" data-bind="click: $parent.deleteBox">削除 (Delete)</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div data-bind="if: boxes().length === 0">
        <p>現在、登録されている備品はありません。(No items are currently registered.)</p>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.1/knockout-latest.js"></script> 
    <script src="/assets/js/box_manage_viewmodel.js"></script>
    
    <script>
        // コントローラーから渡された初期データをJavaScriptオブジェクトに変換
        var initialBoxesData = <?php echo $initial_boxes_json; ?>;
        var csrfToken = '<?php echo \Security::fetch_token(); ?>'; // CSRFトークンをJavaScriptで利用可能にする

        // ViewModel をインスタンス化し、HTMLに適用
        var viewModel = new BoxManageViewModel(initialBoxesData);
        viewModel.csrf_token(csrfToken); // CSRFトークンをViewModelに設定
        ko.applyBindings(viewModel);
    </script>
</body>
</html>