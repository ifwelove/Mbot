<?php

namespace App\Http\Controllers;

use App\Http\FileService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class CommandController extends Controller
{

    public function __construct()
    {
    }

    private function getTokens()
    {
        $tokens = config('monitor-token');

        return $tokens;
    }

    private function checkAllowToken($token)
    {
        // todo 幾台 和 日期
        $tokens = $this->getTokens();
        if (isset($tokens[$token])) {
            return true;
        } else {
            return false;
        }
    }

    public function storeCommand(Request $request)
    {
        $validated = $request->validate([
            'token'   => 'required|string',
            'mac'     => 'required|string',
            'command' => 'required|string',
        ]);

        $token   = $validated['token'];
        $mac     = $validated['mac'];
        $command = $validated['command'];
        $commands = ['close', 'reopen', 'open', 'update', 'reboot'];
        if (!in_array($command, $commands)) {
            return response()->json(['message' => 'Command stored failed']);
        }
        $redisKey = "token:{$token}:mac:{$mac}:command";

        // 使用 SET 命令存储命令，并设置过期时间
        Redis::set($redisKey, $command, 'EX', 86400 / 24 / 60); // 这里我们设置了 24 小时的过期时间

        return response()->json(['message' => 'Command stored successfully']);
    }

    public function getAndClearCommand(Request $request)
    {
        $validated['token'] = 'M7PMOK6orqUHedUCqMVwJSTUALCnMr8FQyyEQS6gyrB';
        $validated['mac'] = '';
//        $validated = $request->validate([
//            'token' => 'required|string',
//            'mac'   => 'required|string',
//        ]);


        $token = $validated['token'];
        $mac   = $validated['mac'];

        $redisKey = "token:{$token}:mac:{$mac}:command";

        // 获取命令
        $command = Redis::get($redisKey);

        if ($command) {
            // 命令存在，删除 key
            Redis::del($redisKey);

            return response()->json(['command' => $command]);
        } else {
            // 命令不存在
            return response()->json(['message' => 'No command available']);
        }
    }

    public function clearCommands(Request $request)
    {
        // 驗證請求參數
        $validated = $request->validate([
            'token' => 'required|string',
            'mac'   => 'required|string',
        ]);

        $token = $validated['token'];
        $mac   = $validated['mac'];

        // 定義 Redis key
        $redisKey = "token:{$token}:mac:{$mac}:commands";

        // 刪除與這個 key 關聯的所有指令
        Redis::del($redisKey);

        // 返回一個成功的響應
        return response()->json([
            'message' => 'Commands cleared successfully',
        ]);
    }

}
