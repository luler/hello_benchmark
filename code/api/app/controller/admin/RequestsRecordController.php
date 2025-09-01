<?php

namespace app\controller\admin;

use app\ApiBaseController;
use app\common\exception\CommonException;
use app\common\helper\PageHelper;
use app\model\RequestsRecord;

class RequestsRecordController extends ApiBaseController
{
    /**
     * 获取压测结果记录列表
     * @return \think\response\Json|\think\response\Jsonp
     * @throws \think\db\exception\DbException
     */
    public function getRequestsRecordList()
    {
        $fields = ['request_id', 'status', 'search',];
        $param = $this->_apiParam($fields);

        $where = [];
        if (!empty($param['search'])) {
            $where[] = ['b.title|a.content', 'like', "%{$param['search']}%"];
        }
        if (!empty($param['request_id'])) {
            $where[] = ['a.request_id', '=', $param['request_id']];
        }
        if (isset($param['status']) && is_numeric($param['status'])) {
            $where[] = ['a.status', '=', $param['status']];
        }

        $res = (new PageHelper(new RequestsRecord()))
            ->alias('a')
            ->join('requests b', 'a.request_id=b.id')
            ->where($where)
            ->order('a.id', 'desc')
            ->field('a.*,b.title')
            ->autoPage()
            ->get();
        foreach ($res['list'] as &$item) {
            $item['content'] = str_replace("\n", '<br />', $item['content']);
        }

        return $this->successResponse('获取成功', $res);
    }

    /**
     * 删除压测结果记录
     * @return \think\response\Json|\think\response\Jsonp
     * @throws \app\common\exception\CommonException
     */
    public function delRequestsRecord()
    {
        $fields = ['ids',];
        $param = $this->_apiParam($fields);
        checkData($param, [
            'ids|压测结果记录' => 'require|array',
        ]);

        //检查
        $requestsRecords = RequestsRecord::alias('a')
            ->join('requests b', 'a.request_id=b.id')
            ->where('a.id', 'in', $param['ids'])
            ->where('a.status', 1)
            ->field('a.create_time as create_time_int,b.duration')
            ->select();
        $time = time();
        foreach ($requestsRecords as $record) {
            if ($time < $record['create_time_int'] + $record['duration']) {
                throw new CommonException('存在正在执行中的项目，请等待压测持续时间结束');
            }
        }

        RequestsRecord::where('id', 'in', $param['ids'])->delete();
        return $this->successResponse('删除成功');
    }

    /**
     * 取消压测
     * @return \think\response\Json|\think\response\Jsonp
     * @throws CommonException
     */
    public function cancelBenchmark()
    {
        $fields = ['ids',];
        $param = $this->_apiParam($fields);
        checkData($param, [
            'ids|压测结果记录' => 'require|array',
        ]);

        if (RequestsRecord::where('id', 'in', $param['ids'])->where('status', '<>', 0)->count()) {
            throw new CommonException('取消失败，只能取消待执行的项目');
        }

        RequestsRecord::where('id', 'in', $param['ids'])->update([
            'status' => 3,
            'update_time' => time(),
        ]);
        return $this->successResponse('取消成功');
    }
}
