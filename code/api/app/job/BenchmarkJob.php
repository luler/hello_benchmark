<?php

namespace app\job;

use app\common\exception\CommonException;
use app\common\helper\SwooleBenchmarkHelper;
use app\model\Params;
use app\model\Requests;
use app\model\RequestsRecord;
use think\queue\Job;

class BenchmarkJob
{
    public function fire(Job $job, $data)
    {
        if ($job->attempts() >= 2) {
            $job->delete();
            return;
        }
        $record_id = $data['record_id'];
        $record = RequestsRecord::find($record_id);
        if (empty($record) || $record['status'] != 0) { //存在并且等待执行才继续执行
            $job->delete();
            return;
        }
        $request_id = $record['request_id'];

        $update_data['status'] = 2;
        $update_data['id'] = $record_id;
        RequestsRecord::update([
            'id' => $record_id,
            'status' => 1, //正在执行
        ]);

        $requests = Requests::where('id', $request_id)->find();
        if (empty($requests)) {
            $job->delete();
            return;
        }
        $out = '';
        try {
            $params = Params::where('request_id', $requests['id'])->select()->toArray();
            $param1 = collect($params)->where('type', 1)->column('value', 'name');
            $param2 = collect($params)->where('type', 2)->first();
            $param3 = collect($params)->where('type', 3)->column('value', 'name');
            if (empty($requests)) {
                throw new CommonException('压测对象为空');
            }

            $path = $requests['path'];
            if (!empty($param1)) {
                $path .= '?' . http_build_query($param1);
            }
            switch ($requests['driver']) {
                case 'co':
                    if ($requests['type'] == 'WEBSOCKET') {
                        $out = SwooleBenchmarkHelper::instance()->testWebSocket(
                            $requests['host'],
                            $requests['port'],
                            $path,
                            $param2['value'] ?? '',
                            $requests['connect_count'],
                            $requests['duration'],
                            $requests['timeout'],
                        );
                    } else {
                        $out = SwooleBenchmarkHelper::instance()->testUrl(
                            $requests['host'],
                            $requests['port'],
                            $path,
                            $requests['type'],
                            $param2['value'] ?? '',
                            $param3,
                            $requests['connect_count'],
                            $requests['duration'],
                            $requests['timeout'],
                            $requests['scheme']
                        );
                    }
                    break;
                case 'wrk':
                    $lua_path = runtime_path('lua');
                    if (!file_exists($lua_path)) {
                        mkdir($lua_path, 0777, true);
                    }
                    $url = "{$requests['scheme']}://{$requests['host']}:{$requests['port']}{$path}";
                    $header = [];
                    foreach ($param3 as $key => $item) {
                        $header[] = "wrk.headers[\"{$key}\"] = \"{$item}\"";
                    }
                    $header = join("\n", $header);
                    $body = $this->formatWrkBody($param2['value'] ?? '');
                    $lua_path .= $requests['id'] . '.lua';
                    $lua_text = <<<lua
wrk.method = "{$requests['type']}"
wrk.body = {$body}
{$header}
lua;
                    file_put_contents($lua_path, $lua_text);
                    $process_nums = swoole_cpu_num(); //指定开启的线程数与服务器核心数一致
                    $command = [];
                    $command[] = 'wrk';
                    $command[] = "-t {$process_nums}";
                    $command[] = "-c {$requests['connect_count']}";
                    $command[] = "-d {$requests['duration']}";
                    $command[] = "-T {$requests['timeout']}s";
                    $command[] = "-s {$lua_path}";
                    $command[] = "--latency";
                    $command[] = '"' . $url . '"';
                    $command = join(' ', $command);
                    $process = proc_open($command, [
                        0 => ["pipe", "r"],    //标准输入，子进程从此管道读取数据
                        1 => ["pipe", "w"],    //标准输出，子进程向此管道写入数据
                        2 => ["pipe", "w",]    //标准错误，写入到指定文件
                    ], $pipes);

                    if (is_resource($process)) {
                        fwrite($pipes[0], "");
                        fclose($pipes[0]);
                        $out = stream_get_contents($pipes[1]);
                        fclose($pipes[1]);
                        $out .= stream_get_contents($pipes[2]);
                        fclose($pipes[2]);
                        proc_close($process);    //在调用proc_close之前必须关闭所有管道
                    }
                    break;
            }
        } catch (\Throwable $exception) {
            $out .= "\n" . $exception->getMessage();
        }

        $update_data = [];
        switch ($requests['driver']) {
            case 'co':
                preg_match_all('/请求数：(\d+)|错误请求数：(\d+)|(\d+.?\d*)次\/秒/im', $out, $match);
                $update_data = [
                    'content' => $out,
                    'request_count' => join($match[1]) ?: 0,
                    'fail_count' => join($match[2]) ?: 0,
                    'request_rate' => join($match[3]) ?: 0,
                ];
                break;
            case 'wrk':
                preg_match_all('/(\d+) requests in|connect\s*(\d+)|read\s*(\d+)|write\s*(\d+)|timeout\s*(\d+)|responses:\s*(\d+)|Requests\/sec:\s*(\d*\.?\d*)/im', $out, $match);
                $fail_count = array_sum([join($match[2]), join($match[3]), join($match[4]), join($match[5]), join($match[6])]);
                $update_data = [
                    'content' => $out,
                    'request_count' => join($match[1]) ?: 0,
                    'fail_count' => $fail_count,
                    'request_rate' => join($match[7]) ?: 0,
                ];
                break;
        }
        $update_data['status'] = 2;
        $update_data['id'] = $record_id;
        RequestsRecord::update($update_data);

        $job->delete();
    }

    /**
     * wrk请求body格式化一下
     * @param $raw
     * @return string
     */
    public function formatWrkBody($raw)
    {
        if (empty($raw)) {
            $body = 'nil';
        } else {
            $body = json_decode($raw);
            if (json_last_error() == JSON_ERROR_NONE) {
                $body = json_encode($body, 256);
            } else {
                $body = str_replace(["\n", "\r", " "], '', $raw);
            }
            $body = '"' . addslashes($body) . '"';
        }
        return $body;
    }

    public function failed($data)
    {
        // ...任务达到最大重试次数后，失败了
    }
}