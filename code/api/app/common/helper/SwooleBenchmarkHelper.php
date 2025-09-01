<?php

namespace app\common\helper;

use Swoole\Coroutine\Http\Client;
use Swoole\Process;
use Swoole\Table;

class SwooleBenchmarkHelper
{
    private static $instance;

    /**
     * 单例
     * @return static
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * websocket压力测试
     * @param string $host //主机
     * @param int $port //端口
     * @param string $path //路径
     * @param string $param //参数
     * @param int $concurrent_counts //并发用户
     * @param int $duration // 持续压测时间,单位秒
     * @param float $timeout //超时
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public function testWebSocket(string $host, int $port, string $path = '/', string $param = '', int $concurrent_counts = 5000, int $duration = 10, float $timeout = 10)
    {
        $messages = [];
        $table = new Table(1024);
        $table->column('code', Table::TYPE_INT);
        $table->column('count', Table::TYPE_INT);
        $table->column('error', Table::TYPE_STRING, 256);
        $table->column('body', Table::TYPE_STRING, 1024 * 1024);
        $table->create();
        $messages[] = ('类型：websocket压测');
        $messages[] = ('主机：' . $host);
        $messages[] = ('端口：' . $port);
        $messages[] = ('请求路径：' . $path);
        $messages[] = ('并发数：' . $concurrent_counts);
        $process_nums = swoole_cpu_num();
        $child_pids = [];
        $avg = ceil($concurrent_counts / $process_nums);
        $time = microtime(true);

        for ($i = 0; $i < $process_nums; $i++) {
            if ($concurrent_counts > $avg) {
                $concurrent_counts -= $avg;
                $concurrent_count = $avg;
            } else {
                if ($concurrent_counts > 0) {
                    $concurrent_count = $concurrent_counts;
                } else {
                    break;
                }
            }

            $process = new Process(function () use ($table, $host, $port, $path, $param, $concurrent_count, $timeout) {
                for ($j = 0; $j < $concurrent_count; $j++) {
                    \go(function () use ($table, $host, $port, $path, $param, $timeout) {
                        $client = new Client($host, $port);
                        $client->set([
                            'timeout' => $timeout,
                        ]);
                        $ret = $client->upgrade($path);
                        if (!$ret) {
                            $table->set($client->errCode, ['code' => $client->errCode, 'error' => $client->errMsg ?? '配置无法upgrade为websocket', 'body' => $ret]);
                            $table->incr($client->errCode, 'count');
                        } else {
                            while (1) {
                                $client->push($param);
                                $res = $client->recv();
                                if ($res === false) {
                                    $table->set($client->errCode, ['code' => $client->errCode, 'error' => $client->errMsg, 'body' => $res]);
                                    $table->incr($client->errCode, 'count');
                                } else {
                                    $table->incr(200, 'count');
                                }
                                unset($res);
                            }
                        }

                    });
                }
            });
            $process->start();
            $child_pids[] = $process->pid;
        }
        //压测指定时间后，终止所有子进程
        usleep($duration * 1000 * 1000);
        $spend_time = microtime(true) - $time;
        foreach ($child_pids as $child_pid) {
            Process::kill($child_pid, 9);
            Process::wait();
        }

        $error_count = 0;
        $success_count = 0;
        $error_messages = [];
        foreach ($table as $key => $row) {
            if ($key == 200) {
                $success_count += $row['count'];
            } else {
                $error_count += $row['count'];
                $error_messages[] = "[状态码:{$key}][数量:{$row['count']}][客户端错误:{$row['error']}][接口报错返回:{$row['body']}]";
            }
        }
        $request_count = $success_count + $error_count;
        $messages[] = ('请求数：' . $request_count);
        $messages[] = ('错误请求数：' . $error_count);
        if (!empty($error_messages)) {
            $messages[] = join("\n", $error_messages);
        }
        $messages[] = sprintf('错误请求占比：%.2f%%', number_format(($error_count / $request_count) * 100, 2, '.', ''));
        $messages[] = ('耗时(秒)：' . number_format($spend_time, 3, '.', '') . '秒');
        $messages[] = ('吞吐量(次/秒)：' . (number_format($request_count / $spend_time, 2, '.', '')) . '次/秒');
        return join("\n", $messages);
    }

    /**
     * 接口压力测试
     * @param string $host //主机
     * @param int $port //端口
     * @param string $path //路径
     * @param string $method //请求方法，仅支持GET、POST请求
     * @param mixed $body //参数,当非get请求时
     * @param array $headers //请求头
     * @param int $concurrent_counts //并发用户
     * @param int $duration // 持续压测时间,单位秒
     * @param float $timeout //超时
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public function testUrl(
        string $host,
        int    $port,
        string $path = '/',
        string $method = 'GET',
        string $body = '',
        array  $headers = [],
        int    $concurrent_counts = 500,
        int    $duration = 10,
        float  $timeout = 10,
        string $scheme = 'http'
    )
    {
        $messages = [];
        $table = new Table(1024);
        $table->column('code', Table::TYPE_INT);
        $table->column('count', Table::TYPE_INT);
        $table->column('error', Table::TYPE_STRING, 256);
        $table->column('body', Table::TYPE_STRING, 1024 * 1024);
        $table->create();
        $messages[] = ('类型：接口压测');
        $messages[] = ('主机：' . $host);
        $messages[] = ('端口：' . $port);
        $messages[] = ('请求路径：' . $path);
        $messages[] = ('并发数：' . $concurrent_counts);
        $process_nums = swoole_cpu_num();
        $child_pids = [];
        $avg = ceil($concurrent_counts / $process_nums);
        $is_default_ssl = $scheme == 'https' ? true : false;
        $time = microtime(true);
        $ip = gethostbyname($host); //防止dns解析错误

        for ($i = 0; $i < $process_nums; $i++) {
            if ($concurrent_counts > $avg) {
                $concurrent_counts -= $avg;
                $concurrent_count = $avg;
            } else {
                if ($concurrent_counts > 0) {
                    $concurrent_count = $concurrent_counts;
                } else {
                    break;
                }
            }

            $process = new Process(function () use ($table, $host, $ip, $port, $path, $method, $body, $headers, $concurrent_count, $timeout, $is_default_ssl) {

                for ($j = 0; $j < $concurrent_count; $j++) {
                    \go(function () use ($method, $table, $host, $ip, $port, $path, $body, $headers, $timeout, $is_default_ssl) {
                        //创建请求实例
                        $client = new Client($ip, $port, $is_default_ssl);
                        //设置请求配置
                        $client->set([
                            'timeout' => $timeout,
                        ]);
                        //设置请求头
                        $headers['Host'] = $host; //设置主机域名，防止80端口绑定多个域名而无法到达准确位置
                        $client->setHeaders($headers);
                        $client->setMethod($method);
                        $client->setData($body);
                        while (1) {
                            $client->execute($path);
                            $code = $client->getStatusCode();
                            $table->incr($code, 'count');
                            if ($code != 200) {
                                $table->set($code, ['code' => $code, 'error' => $client->errMsg, 'body' => $client->getBody()]);
                            }
                        }
                    });
                }
            });
            $process->start();
            $child_pids[] = $process->pid;
        }
        //压测指定时间后，终止所有子进程
        usleep($duration * 1000 * 1000);
        $spend_time = microtime(true) - $time;
        foreach ($child_pids as $child_pid) {
            Process::kill($child_pid, 9);
            Process::wait();
        }

        $error_count = 0;
        $success_count = 0;
        $error_messages = [];
        foreach ($table as $key => $row) {
            if ($key == 200) {
                $success_count += $row['count'];
            } else {
                $error_count += $row['count'];
                $error_messages[] = "   [状态码:{$key}][数量:{$row['count']}][客户端错误:{$row['error']}][接口报错返回:{$row['body']}]";
            }
        }
        $request_count = $success_count + $error_count;
        $messages[] = ('请求数：' . $request_count);
        $messages[] = ('错误请求数：' . $error_count);
        if (!empty($error_messages)) {
            $messages[] = join("\n", $error_messages);
        }
        $messages[] = sprintf('错误请求占比：%.2f%%', number_format(($error_count / $request_count) * 100, 2, '.', ''));
        $messages[] = ('耗时(秒)：' . number_format($spend_time, 3, '.', '') . '秒');
        $messages[] = ('吞吐量(次/秒)：' . (number_format($request_count / $spend_time, 2, '.', '')) . '次/秒');
        return join("\n", $messages);
    }
}