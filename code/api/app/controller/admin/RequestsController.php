<?php

namespace app\controller\admin;

use app\ApiBaseController;
use app\common\exception\CommonException;
use app\common\helper\PageHelper;
use app\job\BenchmarkJob;
use app\model\Params;
use app\model\Requests;
use app\model\RequestsRecord;
use think\facade\Db;
use think\facade\Queue;
use think\helper\Str;

class RequestsController extends ApiBaseController
{
    /**
     * 获取压测对象列表
     * @return \think\response\Json|\think\response\Jsonp
     * @throws \think\db\exception\DbException
     */
    public function getRequestsList()
    {
        $fields = ['search',];
        $param = $this->_apiParam($fields);

        $where = [];
        if (!empty($param['search'])) {
            $where[] = ['title|type|host|port|path', 'like', "%{$param['search']}%"];
        }

        $res = (new PageHelper(new Requests()))->where($where)
            ->order('id', 'desc')
            ->autoPage()
            ->get();
        foreach ($res['list'] as &$item) {
            $item['driver_title'] = ['co' => 'swoole多进程&协程', 'wrk' => 'wrk多线程压测工具',][$item['driver']] ?? '';
            $item['header'] = Params::where('request_id', $item['id'])
                ->where('type', 3)
                ->field('name,value')
                ->select()
                ->toArray();
            $item['param'] = Params::where('request_id', $item['id'])
                ->where('type', 1)
                ->field('name,value')
                ->select()
                ->toArray();
            $item['raw'] = Params::where('request_id', $item['id'])
                ->where('type', 2)
                ->value('value');
            //最新一次压测的状态
            $record = RequestsRecord::where('request_id', $item['id'])->order('id', 'desc')->field('status,request_rate')->find();
            $item['status'] = $record['status'] ?? null;
            $item['request_rate'] = $record['request_rate'] ?? null;
        }

        return $this->successResponse('获取成功', $res);
    }

    /**
     * 保存压测对象
     * @return \think\response\Json|\think\response\Jsonp
     * @throws \app\common\exception\CommonException
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public function saveRequests()
    {
        $fields = ['id', 'title', 'type', 'driver', 'duration', 'host', 'port', 'path', 'connect_count', 'timeout', 'scheme',];
        $param = $this->_apiParam($fields);
        checkData($param, [
            'title|名称' => 'require|max:255',
            'type|类型' => 'require|in:GET,POST,PUT,PATCH,DELETE,WEBSOCKET',
            'driver|压测驱动' => 'require|in:co,wrk',
            'host|ip/域名' => 'require|regex:/^[\w\.\-]+$/',
            'port|端口' => 'require|between:1,65535',
            'path|路径' => 'require',
            'connect_count|模拟用户' => 'require|integer|egt:1',
            'timeout|超时设置' => 'require|float|gt:0',
            'duration|压测持续时间' => 'require|integer|egt:1',
        ]);

        if (!Str::startsWith($param['path'], '/')) {
            throw new CommonException('路径必须以/开头');
        }
        if (Str::contains($param['path'], '?')) {
            throw new CommonException('路径不能包含特殊字符?，如需携带该参数请在请求参数中设置');
        }

        switch ($param['type']) {
            case 'WEBSOCKET':
                if ($param['driver'] == 'wrk') {
                    throw new CommonException('WEBSOCKET不支持当压测驱动');
                }
                $param['scheme'] = '';
                break;
            case 'GET':
                //GET请求没有raw参数
                Params::where('request_id', $param['id'] ?? '')->where('type', 2)->delete();
            default:
                checkData($param, ['scheme|请求协议' => 'require|in:http,https',]);
                break;
        }

        $param['updator_uid'] = is_login();
        if (!empty($param['id'])) {
            Requests::update($param);
        } else {
            $param['creator_uid'] = is_login();
            Requests::create($param);
        }

        return $this->successResponse('保存成功');
    }

    /**
     * 启动压测
     * @return \think\response\Json|\think\response\Jsonp
     * @throws \app\common\exception\CommonException
     */
    public function startBenchmark()
    {
        $fields = ['ids',];
        $param = $this->_apiParam($fields);
        checkData($param, [
            'ids|压测对象' => 'require|array',
        ]);

        $param['ids'] = array_unique($param['ids']);

        foreach ($param['ids'] as $id) {
            if (!Requests::where('id', $id)->count()) {
                throw new CommonException('压测对象不存在');
            }
            if (RequestsRecord::where('request_id', $id)->order('id', 'desc')->value('status') == 1) {
                throw new CommonException('不能启动正在执行的压测对象');
            }
        }
        foreach ($param['ids'] as $id) {
            $res = RequestsRecord::create([
                'status' => 0,
                'request_id' => $id,
                'creator_uid' => is_login(),
            ]);
            Queue::push(BenchmarkJob::class, [
                'record_id' => $res['id'],
            ]);
        }

        return $this->successResponse('压测发布成功');
    }

    /**
     * 删除压测对象
     * @return \think\response\Json|\think\response\Jsonp
     * @throws \app\common\exception\CommonException
     */
    public function delBenchmark()
    {
        $fields = ['ids',];
        $param = $this->_apiParam($fields);
        checkData($param, [
            'ids|压测对象数组' => 'require|array',
        ]);

        Db::startTrans();
        Requests::where('id', 'in', $param['ids'])->delete();
        Params::where('request_id', 'in', $param['ids'])->delete();
        RequestsRecord::where('request_id', 'in', $param['ids'])->delete();
        Db::commit();
        return $this->successResponse('删除成功');
    }
}
