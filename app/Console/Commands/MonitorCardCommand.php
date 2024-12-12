<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class MonitorCardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:card';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //        $owen_token = 'M7PMOK6orqUHedUCqMVwJSTUALCnMr8FQyyEQS6gyrB';
        //        $client   = new Client();
        //        $headers  = [
        //            'Authorization' => sprintf('Bearer %s', $owen_token),
        //            'Content-Type'  => 'application/x-www-form-urlencoded'
        //        ];
        //        $options  = [
        //            'form_params' => [
        //                'message' => 'test MonitorCrashCommand'
        //            ]
        //        ];
        //        $response = $client->request('POST', 'https://notify-api.line.me/api/notify', [
        //            'headers'     => $headers,
        //            'form_params' => $options['form_params']
        //        ]);

        $tokens = config('monitor-token');
        try {
            $not_check_role_status = [
                '',
                '工具開始',
                '遊戲執行',
                '角色死亡',
                '死亡工具結束',
            ];
            $not_check_dead_status = [
                '',
                '工具開始',
                '遊戲執行',
                '角色死亡',
                '工具結束',
            ];
            $extra = [
                'M7PMOK6orqUHedUCqMVwJSTUALCnMr8FQyyEQS6gyrB',
                'bWBWihKBoPyGbNN5Ht14TtBtfN0H9f7quS1fV7LCyU3',
                '1EW9dRJOANPRwZYvS0gZblhxGPZvJ9ZNEBdpLlvARUu',
                ];
            foreach ($tokens as $token => $name) {
//                if ($token != 'M7PMOK6orqUHedUCqMVwJSTUALCnMr8FQyyEQS6gyrB') {
//                    continue;
//                }
                $macAddresses = Redis::sMembers("token:$token:machines");
                foreach ($macAddresses as $mac) {
                    $key         = "token:$token:mac:$mac";
                    $machine     = Redis::hGetAll($key);
//                    if ($machine['pc_name'] === '台北1') {
//                        dump($machine['pro_version']);
//                    }
//dump($machine);
                    $rows   = [];
                    $role_gg   = 0;
                    $dead_gg   = 0;
                    $bag_gg   = 0;
                    $role_gg_items   = [];
                    $dead_gg_items   = [];
                    $bag_gg_items   = [];
                    if (isset($machine['m_info']) && $machine['m_info'] != '' && ! is_null($machine['m_info'])) {
                        $m_info = json_decode(base64_decode($machine['m_info']), true);
                        if (isset($m_info['card'])) {
                            $card = str_replace('?', '時', $m_info['card']);
                            if (preg_match('/(\d+)天\s*(\d+)小時/', $card, $matches)) {
                                // 使用當前時間，加上解析出來的天數和小時數
                                $days           = $matches[1];
                                $hours          = $matches[2];
                                $expirationTime = Carbon::now()
                                    ->addDays($days)
                                    ->addHours($hours);

                                if (isset($machine['card_alert_total'])) {
                                    $card_alert_total = (int) $machine['card_alert_total'] + 1;
                                } else {
                                    $card_alert_total = 1;
                                }
                                // 判斷是否需要發送通知

                                if ($expirationTime->lte(Carbon::now()
                                        ->addHours()) && $card_alert_total <= 3) {
//                                    dump($card_alert_total);
                                    //                                    echo "發送通知";
                                    Redis::hSet($key, 'card_alert_total', (string) $card_alert_total);
                                    $breakLine = "\n";
                                    $message   = $breakLine;
                                    $message   .= sprintf('自訂代號 : %s%s', isset($machine['pc_name']) ? $machine['pc_name'] : '', $breakLine);
                                    $message   .= sprintf('電腦資訊 : %s%s', isset($machine['pc_info']) ? $machine['pc_info'] : '', $breakLine);
                                    $message   .= sprintf('大尾狀態 : %s:%s%s', '卡號即將到期剩餘時間', $card, $breakLine);
                                    $message   .= sprintf('如已經處理請至網頁點選重置訊號 : https://mbot-3-ac8b63fd9692.herokuapp.com/pro/%s', $token);
//                                    $message   .= sprintf('已經處理點選清除通知 : https://mbot-3-ac8b63fd9692.herokuapp.com/delete-machine?token=%s&mac=%s', $token, $mac);

                                    $client   = new Client();
                                    $headers  = [
                                        'Authorization' => sprintf('Bearer %s', $token),
                                        'Content-Type'  => 'application/x-www-form-urlencoded'
                                    ];
                                    $options  = [
                                        'form_params' => [
                                            'message' => $message
                                        ]
                                    ];
                                    $response = $client->request('POST', 'https://notify-api.line.me/api/notify', [
                                        'headers'     => $headers,
                                        'form_params' => $options['form_params']
                                    ]);
                                } else {
                                    Redis::hSet($key, 'card_alert_total', '1');
                                    //                                    echo "不需要發送通知";
                                }
                            } else {
                                //@todo 卡號到期 ... etc
                                //                                echo "時間格式不正確";
                                continue;
                            }

                        }
                        if (isset($m_info['rows'])) {
                            $rows = $m_info['rows'];
                        }
                    }

                    $time_counts = [];
                    foreach ($rows as $role) {
                        if ($role[0] === "1" && preg_match('/(\d+)天\s*(\d+)小?/', $role[12], $matches)) {
                            $time = "{$matches[1]}天 {$matches[2]}小";
                            $time_counts[$time] = ($time_counts[$time] ?? 0) + 1;
                        }

                        if(!in_array($role[2], $not_check_role_status)) {
                            $role_gg = 1;
                            $role_gg_items[] = $role[1];
                        }
                        if(!in_array($role[2], $not_check_dead_status)) {
                            $dead_gg = 1;
                            $dead_gg_items[] = $role[1];
                        }
                        if($role[2] !== '遊戲執行' and $role[5] !== '' and (int) $role[5] <= 0) {
//                            $bag_gg = 1;
//                            $bag_gg_items[] = $role[1];
                            $counterKey = "token:$token:mac:{$mac}:role:{$role[1]}:count";
                            $currentCount = (int)Redis::get($counterKey);
                            // 如果当前计数小于 2，则增加计数，否则执行通知逻辑
                            if ($currentCount < 4) {
                                // 增加计数并设置过期时间为 2 天
                                Redis::setex($counterKey, 86400, $currentCount + 1); // 使用 setex 来同时设置值和 TTL
                            } else {
//                                dump($role);
                                // 执行通知逻辑
                                $bag_gg = 1;
                                $bag_gg_items[] = $role[1];

                                // 重置计数器并设置过期时间
                                Redis::setex($counterKey, 86400, 0);
                            }
                        }
                    }

                    if (isset($machine['role_gg_alert_total'])) {
                        $role_gg_alert_total = (int) $machine['role_gg_alert_total'] + 1;
                    } else {
                        $role_gg_alert_total = 1;
                    }

                    if (isset($machine['dead_gg_alert_total'])) {
                        $dead_gg_alert_total = (int) $machine['dead_gg_alert_total'] + 1;
                    } else {
                        $dead_gg_alert_total = 1;
                    }

                    if (isset($machine['bag_gg_alert_total'])) {
                        $bag_gg_alert_total = (int) $machine['bag_gg_alert_total'] + 1;
                    } else {
                        $bag_gg_alert_total = 1;
                    }

                    if (isset($machine['m_pro_gg_count'])) {
                        $m_pro_gg_count = (int) $machine['m_pro_gg_count'] + 1;
                    } else {
                        $m_pro_gg_count = 1;
                    }
                    if (isset($machine['m_pro_gg_alert_total'])) {
                        $m_pro_gg_alert_total = (int) $machine['m_pro_gg_alert_total'] + 1;
                    } else {
                        $m_pro_gg_alert_total = 1;
                    }
                    if (count($time_counts) > 1) {
                        $m_pro_gg_count++;
                        Redis::hSet($key, 'm_pro_gg_count', (string) $m_pro_gg_count);
                    } else {
                        Redis::hSet($key, 'm_pro_gg_count', '1');
                        $m_pro_gg_count = 1;
                    }

                    if ($m_pro_gg_count > 6 && $m_pro_gg_alert_total <= 3) {
//                    if (in_array($token, $extra) && $m_pro_gg_count > 6 && $m_pro_gg_alert_total <= 3) {
                        Redis::hSet($key, 'm_pro_gg_alert_total', (string) $m_pro_gg_alert_total);
                        $breakLine = "\n";
                        $message   = $breakLine;
                        $message   .= sprintf('自訂代號 : %s%s', isset($machine['pc_name']) ? $machine['pc_name'] : '', $breakLine);
                        $message   .= sprintf('電腦資訊 : %s%s', isset($machine['pc_info']) ? $machine['pc_info'] : '', $breakLine);
                        $message   .= sprintf('大尾狀態 : %s:%s', '資料出現異常請人工排除', $breakLine);
                        $message   .= sprintf('如已經處理請至網頁點選重置訊號 : https://mbot-3-ac8b63fd9692.herokuapp.com/pro/%s', $token);
                        $client   = new Client();
                        $headers  = [
                            'Authorization' => sprintf('Bearer %s', $token),
                            'Content-Type'  => 'application/x-www-form-urlencoded'
                        ];
                        $options  = [
                            'form_params' => [
                                'message' => $message
                            ]
                        ];
                        $response = $client->request('POST', 'https://notify-api.line.me/api/notify', [
                            'headers'     => $headers,
                            'form_params' => $options['form_params']
                        ]);
                    } else {
                        if ($m_pro_gg_alert_total > 3){
                            Redis::hSet($key, 'm_pro_gg_count', '1');
                            Redis::hSet($key, 'm_pro_gg_alert_total', '1');
                        }
                    }

//                    角色死亡,
                    // 結束工具
                    if (isset($machine['role_gg_alert']) && $machine['role_gg_alert'] === 'yes' && $role_gg === 1 && $role_gg_alert_total <= 3) {
                        Redis::hSet($key, 'role_gg_alert_total', (string) $role_gg_alert_total);
                        // 將每個元素用方括號包圍
                        $wrappedItems = array_map(function($item) {
                            return sprintf('[%s]', $item);
                        }, $role_gg_items);
                        $breakLine = "\n";
                        $message   = $breakLine;
                        $message   .= sprintf('自訂代號 : %s%s', isset($machine['pc_name']) ? $machine['pc_name'] : '', $breakLine);
                        $message   .= sprintf('電腦資訊 : %s%s', isset($machine['pc_info']) ? $machine['pc_info'] : '', $breakLine);
                        $message   .= sprintf('大尾狀態 : %s:%s', '發生工具結束', $breakLine);
                        $message   .= sprintf('編號 : %s%s', implode('', $wrappedItems), $breakLine);
                        $message   .= sprintf('如已經處理請至網頁點選重置訊號 : https://mbot-3-ac8b63fd9692.herokuapp.com/pro/%s', $token);
//                        $message   .= sprintf('已經處理點選清除通知 : https://mbot-3-ac8b63fd9692.herokuapp.com/delete-machine?token=%s&mac=%s', $token, $mac);
//                        dump($message);
                        $client   = new Client();
                        $headers  = [
                            'Authorization' => sprintf('Bearer %s', $token),
                            'Content-Type'  => 'application/x-www-form-urlencoded'
                        ];
                        $options  = [
                            'form_params' => [
                                'message' => $message
                            ]
                        ];
                        $response = $client->request('POST', 'https://notify-api.line.me/api/notify', [
                            'headers'     => $headers,
                            'form_params' => $options['form_params']
                        ]);
                    } else {
                        Redis::hSet($key, 'role_gg_alert_total', '1');
                    }

                    if (isset($machine['dead_gg_alert']) && $machine['dead_gg_alert'] === 'yes' && $dead_gg === 1 && $dead_gg_alert_total <= 3) {
                        Redis::hSet($key, 'dead_gg_alert_total', (string) $dead_gg_alert_total);
                        // 將每個元素用方括號包圍
                        $wrappedItems = array_map(function($item) {
                            return sprintf('[%s]', $item);
                        }, $dead_gg_items);
                        $breakLine = "\n";
                        $message   = $breakLine;
                        $message   .= sprintf('自訂代號 : %s%s', isset($machine['pc_name']) ? $machine['pc_name'] : '', $breakLine);
                        $message   .= sprintf('電腦資訊 : %s%s', isset($machine['pc_info']) ? $machine['pc_info'] : '', $breakLine);
                        $message   .= sprintf('大尾狀態 : %s:%s', '死亡工具結束', $breakLine);
                        $message   .= sprintf('編號 : %s%s', implode('', $wrappedItems), $breakLine);
                        $message   .= sprintf('如已經處理請至網頁點選重置訊號 : https://mbot-3-ac8b63fd9692.herokuapp.com/pro/%s', $token);
                        //                        $message   .= sprintf('已經處理點選清除通知 : https://mbot-3-ac8b63fd9692.herokuapp.com/delete-machine?token=%s&mac=%s', $token, $mac);
                        //                        dump($message);
                        $client   = new Client();
                        $headers  = [
                            'Authorization' => sprintf('Bearer %s', $token),
                            'Content-Type'  => 'application/x-www-form-urlencoded'
                        ];
                        $options  = [
                            'form_params' => [
                                'message' => $message
                            ]
                        ];
                        $response = $client->request('POST', 'https://notify-api.line.me/api/notify', [
                            'headers'     => $headers,
                            'form_params' => $options['form_params']
                        ]);
                    } else {
                        Redis::hSet($key, 'dead_gg_alert_total', '1');
                    }

                    if (isset($machine['bag_alert']) && $machine['bag_alert'] === 'yes' && $bag_gg === 1 && $bag_gg_alert_total <= 3) {
                        Redis::hSet($key, 'bag_gg_alert_total', (string) $bag_gg_alert_total);
                        // 將每個元素用方括號包圍
                        $wrappedItems = array_map(function($item) {
                            return sprintf('[%s]', $item);
                        }, $bag_gg_items);
                        $breakLine = "\n";
                        $message   = $breakLine;
                        $message   .= sprintf('自訂代號 : %s%s', isset($machine['pc_name']) ? $machine['pc_name'] : '', $breakLine);
                        $message   .= sprintf('電腦資訊 : %s%s', isset($machine['pc_info']) ? $machine['pc_info'] : '', $breakLine);
                        $message   .= sprintf('大尾狀態 : %s:%s', '包包滿了請擴充格子不然我要叫了', $breakLine);
                        $message   .= sprintf('編號 : %s%s', implode('', $wrappedItems), $breakLine);
                        $message   .= sprintf('如已經處理請至網頁點選重置訊號 : https://mbot-3-ac8b63fd9692.herokuapp.com/pro/%s', $token);
                        //                        $message   .= sprintf('已經處理點選清除通知 : https://mbot-3-ac8b63fd9692.herokuapp.com/delete-machine?token=%s&mac=%s', $token, $mac);
                        //                        dump($message);
                        $client   = new Client();
                        $headers  = [
                            'Authorization' => sprintf('Bearer %s', $token),
                            'Content-Type'  => 'application/x-www-form-urlencoded'
                        ];
                        $options  = [
                            'form_params' => [
                                'message' => $message
                            ]
                        ];
                        $response = $client->request('POST', 'https://notify-api.line.me/api/notify', [
                            'headers'     => $headers,
                            'form_params' => $options['form_params']
                        ]);
                    } else {
                        Redis::hSet($key, 'bag_gg_alert_total', '1');
                    }
                }
            }
        } catch (\Exception $exception) {
//            $client   = new Client();
//            $headers  = [
//                'Authorization' => sprintf('Bearer %s', 'M7PMOK6orqUHedUCqMVwJSTUALCnMr8FQyyEQS6gyrB'),
//                'Content-Type'  => 'application/x-www-form-urlencoded'
//            ];
//            $options  = [
//                'form_params' => [
//                    'message' => json_encode([
//                        'token'   => $token,
//                        'message' => $exception->getMessage(),
//                        'data'    => $machine
//                    ])
//                ]
//            ];
//            $response = $client->request('POST', 'https://notify-api.line.me/api/notify', [
//                'headers'     => $headers,
//                'form_params' => $options['form_params']
//            ]);
        }
    }
}
