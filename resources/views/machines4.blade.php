<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-S77TGXYZGF"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-S77TGXYZGF');
    </script>
    <meta charset="UTF-8">
    <title>Machines Status</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        .status-icon {
            height: 20px;
            width: 20px;
            border-radius: 50%;
            display: inline-block;
        }
        .success { background-color: green; }
        .plugin_not_open { background-color: yellow; }
        .pc_not_open { background-color: grey; }
        .failed { background-color: red; }

        /* 添加 Bootstrap 表格样式 */
        .custom-table .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }
        .custom-table th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
        }
        .custom-table td {
            padding: .75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }

        .modal-table .table {
            margin-bottom: 0; /* 移除表格底部間距 */
        }
        .modal-table .table td,
        .modal-table .table th {
            padding: .3rem; /* 減少單元格的內邊距 */
            font-size: .875rem; /* 縮小字體大小 */
        }
    </style>
</head>
<body>
<div class="container">
    <h3 class="mt-3">Very6-大尾崩潰監視者</h3>
    <div class="row mb-3">
        <div class="col">
            <a href="javascript:void(0)" class="btn btn-light">重新整理於: <span id="countdown">120</span> 秒</a>
            <a href="javascript:void(0)" id="pauseButton" class="btn btn-danger">暫停倒數</a>
            <a target="_blank" href="https://drive.google.com/file/d/16ZL3_hhbGx3oKcuY47YwiJWxgjpT4Or8/view?usp=sharing" class="btn btn-secondary">介面版+工作室自動更新大尾版下載點</a>
            <a target="_blank" href="https://docs.google.com/document/d/19y_lxsepZpKKQ8x-AjpNptyVy2-QzwYA35n8DoVtwfI/edit" class="btn btn-info">教學文件</a>
            <a target="_blank" href="https://line.me/ti/g2/5gBZGGhG_e3jylabmmkSQbpqW3PamjCxY490YQ" class="btn btn-success">歡迎加入 Line 群討論</a>
        </div>
    </div>
    <p>資料每10分鐘, 主機沒訊號監測30分鐘, 更新一次, 遊戲維修時間不推播, 私人 line token 請勿外流避免被不當使用</p>
    <p>綠燈 正常運作, 黃燈 大尾沒開, 紅燈 大尾沒回應, 灰色 主機沒訊號</p>
    <p>使用期限：{{ $user['date'] }}, 可使用台數：{{ $user['amount'] }}</p>
    <p>共有礦場 {{ $machines_total }} 座, 有打幣機正在挖礦中 {{ $dnplayer_running_total }} / {{ $dnplayer_total }}</p>
    <p>全伺服器統計：{{ $money_total }}@if ($money_total!=0 && $dnplayer_total!=0), 平均帳號打鑽數：{{ round($money_total / $dnplayer_total, 0) }}@endif, 各伺服器鑽石統計：
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#dataModal">
            顯示資料
        </button>
    </p>
    <p>
        <select name="server" class="custom-select">
            @foreach ($merges as $server => $total)
                <optgroup label="{{ $server }}">
                    @php
                        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                    @endphp
                    <option value="{{ $server }}" style="color: {{ $color }}">{{ $server }}: {{ $total }}</option>
                </optgroup>
            @endforeach
        </select>
    </p>
    <p>
        <select name="pc_status" class="custom-select">
            @foreach ($machines as $index => $machine)
                @if ($machine['data']['status'] !== 'success')
                <optgroup label="{{ $machine['pc_name'] }}">
                    @php
                        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                    @endphp
                    <option value="{{ $machine['pc_name'] }}" style="color: {{ $color }}">{{ $machine['pc_name'] }}-{{ $machine['data']['status'] }}</option>
                </optgroup>
                @endif
            @endforeach
        </select>
    </p>

    <div class="modal fade" id="dataModal" tabindex="-1" aria-labelledby="dataModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dataModalLabel">資料列表</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul>
                        @foreach ($merges as $key => $value)
                            <li>{{ $key }}: {{ $value }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">關閉</button>
                </div>
            </div>
        </div>
    </div>
    @foreach ($machines as $index => $machine)
        <div class="modal fade" id="detailModal{{ $index }}" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel{{ $index }}" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document"> <!-- 可以調整 modal-lg 為 modal-sm 來進一步控制模態框的大小 -->
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailModalLabel{{ $index }}">詳細資訊 - {{ $machine['pc_name'] }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body modal-table"> <!-- 使用 modal-table 類來應用自定義樣式 -->
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>伺服器</th>
                                <th>狀態</th>
                                <th>鑽石數</th>
                                <th>格子數量</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($machine['role_list'] as $detailIndex => $detail)
                                <tr>
                                    <td>{{ $detailIndex + 1 }}</td>
                                    <td>{{ $detail[2] }}</td>
                                    <td>{{ $detail[4] }}</td>
                                    <td>{{ $detail[3] }}</td>
                                    <td>{{ $detail[5] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">關閉</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="custom-table">
        <table class="table">
            <!-- 表格头部 -->
            <thead>
            <tr>
                <th scope="col">電腦代號</th>
                <th scope="col">狀態</th>
                <th scope="col">帳號狀態</th>
                <th scope="col">模擬器數量</th>
                <th scope="col">鑽石(點選可複製)</th>
                <th scope="col">卡號到期</th>
                <th scope="col"></th>
{{--                <th scope="col">MAC</th>--}}
                <th scope="col">最後更新時間</th>
            </tr>
            </thead>
            <!-- 表格主体 -->
            <tbody>
            @foreach ($machines as $index => $machine)
                <tr>
                    <td>
                        <button type="button" class="btn btn-info btn-block" data-toggle="modal" data-target="#detailModal{{ $index }}">
                            {{ $machine['pc_name'] }}
                        </button>
                        <p>{{ $machine['data']['pro_version'] }}</p>
                    </td>
                    <td>
                        <span class="status-icon {{ $machine['data']['status'] }}"></span>
                        {{ $machine['data']['status'] }}
                    </td>
                    <td>@foreach ($machine['rows'] as $status => $total){{ $status }}:{{ $total }}<br>@endforeach</td>
                    <td>{{ $machine['dnplayer_running'] }}/{{ $machine['dnplayer'] }}</td>
                    <td>
                        @foreach ($machine['money_rows'] as $server => $items)
                            <button class="btn btn-warning btn-block" onclick="copyToClipboard('#server-data-{{ $machine['pc_name'] }}-{{ $server }}')">{{ $server }}:{{ $items['total'] }}</button><br>
                            <div id="server-data-{{ $machine['pc_name'] }}-{{ $server }}" style="display: none">{!! $items['rows'] !!}</div>
                            <br>
                        @endforeach
                    </td>

                    <td>{{ $machine['card'] }}</td>
                    <td>
                        <!-- 删除按钮 -->
                        <button class="delete-btn btn btn-danger" data-token="{{ $token }}" data-mac="{{ $machine['mac'] }}">重置訊號</button>
                    </td>
{{--                    <td>{{ $machine['mac'] }}</td>--}}
                    <td>{{ $machine['data']['last_updated'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.9/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    function copyToClipboard(element) {
        var text = $(element).html().replace(/<br\s*[\/]?>/gi, '\n'); // 將 <br> 標籤轉換為換行符
        var $temp = $("<textarea>"); // 使用 textarea 來保持文本格式
        $("body").append($temp);
        $temp.val(text).select();
        document.execCommand("copy");
        $temp.remove();
        alert("已複製");
    }
</script>

<script>
    // 設定計時器每秒更新
    var seconds = 120; // 60秒後重新整理
    var isPaused = false; // 控制暫停的變量

    function updateTimer() {
        if (!isPaused) { // 如果不是暫停狀態，則繼續倒數
            seconds--;
            $('#countdown').text(seconds);
            if (seconds <= 0) {
                window.location.reload(); // 到達0秒時重新整理頁面
            }
        }
    }
    setInterval(updateTimer, 1000);

    $(document).ready(function() {
        $('#pauseButton').click(function() { // 暫停/恢復按鈕的點擊事件
            isPaused = !isPaused; // 切換暫停狀態
            $(this).text(isPaused ? '恢復倒數' : '暫停倒數'); // 更新按鈕文本
        });

        $('.delete-btn').click(function() {
            var token = $(this).data('token');
            var mac = $(this).data('mac');

            $.ajax({
                url: '/delete-machine', // 这是处理删除请求的路由
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    token: token,
                    mac: mac
                },
                success: function(response) {
                    // 处理成功响应
                    alert(response.message);
                    location.reload(); // 重新加载页面
                },
                error: function(response) {
                    // 处理错误响应
                    alert("Error: " + response.responseText);
                }
            });
        });
    });
</script>
</body>
</html>
