<!DOCTYPE html>
<html lang="en">
<head>
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
    </style>
</head>
<body>
<div class="container">
    <h3 class="mt-3">Very6-大尾崩潰監視者</h3>
    <div class="row mb-3">
        <div class="col">
            <a target="_blank" href="https://very6.tw/大尾崩潰檢測check_status_20240104_v0906.rar" class="btn btn-primary">一般版下載點</a>
            <a target="_blank" href="https://drive.google.com/file/d/17OAEMUqbV8p5rdG-TsXxQRoTdWRwD19J/view?usp=sharing" class="btn btn-secondary">鑽石版下載點</a>
            <a target="_blank" href="https://docs.google.com/document/d/19y_lxsepZpKKQ8x-AjpNptyVy2-QzwYA35n8DoVtwfI/edit" class="btn btn-info">教學文件</a>
            <a target="_blank" href="https://line.me/ti/g2/5gBZGGhG_e3jylabmmkSQbpqW3PamjCxY490YQ" class="btn btn-success">歡迎加入 Line 群討論</a>
        </div>
    </div>
    <p>資料每10分鐘, 主機沒訊號監測30分鐘, 更新一次, 遊戲維修時間不推播, 私人 line token 請勿外流避免被不當使用</p>
    <p>綠燈 正常運作, 黃燈 大尾沒開, 紅燈 大尾沒回應, 灰色 主機沒訊號</p>
    <p>使用期限：{{ $user['date'] }}, 可使用台數：{{ $user['amount'] }}</p>
    <p>共有礦場 {{ $machines_total }} 座, 有打幣機正在挖礦中 {{ $dnplayer_running_total }} / {{ $dnplayer_total }}</p>
    <p>全伺服器統計：{{ $money_total }}, 平均帳號打鑽數：{{ round($money_total / $dnplayer_total, 0) }}, 各伺服器鑽石統計：<select name="server" class="custom-select">
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
            @foreach ($machines as $machine)
                <tr>
                    <td>{{ $machine['pc_name'] }}</td>
                    <td>
                        <span class="status-icon {{ $machine['data']['status'] }}"></span>
                        {{ $machine['data']['status'] }}
                    </td>
                    <td>@foreach ($machine['rows'] as $status => $total){{ $status }}:{{ $total }}<br>@endforeach</td>
                    <td>{{ $machine['dnplayer_running'] }}/{{ $machine['dnplayer'] }}</td>
{{--                    <td>@foreach ($machine['merge'] as $server => $total){{ $server }}:{{ $total }}<br>@endforeach</td>--}}
                    <td>
{{--                        @foreach ($machine['merge'] as $server => $total)--}}
{{--                            {{ $server }}:{{ $total }}<br>--}}
{{--                        @endforeach--}}
                        @foreach ($machine['money_rows'] as $server => $items)
                            <button class="btn btn-info" onclick="copyToClipboard('#server-data-{{ $machine['pc_name'] }}-{{ $server }}')">{{ $server }}:{{ $items['total'] }}</button><br>
                            <div id="server-data-{{ $machine['pc_name'] }}-{{ $server }}" style="display: none">{!! $items['rows'] !!}</div>
{{--                            <button onclick="copyToClipboard('#server-data-{{ $machine['pc_name'] }}-{{ $server }}')">複製</button>--}}
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
    $(document).ready(function() {
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