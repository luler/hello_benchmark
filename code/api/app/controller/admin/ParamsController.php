<?php

namespace app\controller\admin;

use app\ApiBaseController;
use app\model\Params;
use think\facade\Db;

class ParamsController extends ApiBaseController
{
    /**
     * 保存压测对象请求参数
     * @return \think\response\Json|\think\response\Jsonp
     * @throws \app\common\exception\CommonException
     * @author 我只想看看蓝天 <1207032539@qq.com>
     */
    public function saveParams()
    {
        $fields = ['request_id', 'type', 'data',];
        $param = $this->_apiParam($fields);
        checkData($param, [
            'request_id|压测对象' => 'require|integer',
            'type|类型' => 'require|in:1,2,3',
//            'data|数据' => 'require',
        ]);

        $data = [];
        switch ($param['type']) {//类型，1-普通请求参数，2-raw参数,3-请求头参数
            case 1:
            case 3:
                $param['data'] = !isset($param['data']) || !is_array($param['data']) ? [] : $param['data'];
                foreach ($param['data'] as $value) {
                    checkData($value, [
                        'name|请求头键名' => 'require',
                        'value|请求头键值' => 'require',
                    ]);
                    $temp = [
                        'name' => $value['name'],
                        'value' => $value['value'],
                    ];
                    if ($id = Params::where('type', $param['type'])
                        ->where('request_id', $param['request_id'])
                        ->where('name', $value['name'])
                        ->value('id')) {
                        $temp['id'] = $id;
                    } else {
                        $temp['creator_uid'] = is_login();
                    }
                    $temp['request_id'] = $param['request_id'];
                    $temp['type'] = $param['type'];
                    $temp['updator_uid'] = is_login();
                    $data[] = $temp;
                }
                break;
            case 2:
                $param['data'] = $param['data'] ?? '';
                $temp = [];
                $temp['name'] = 'raw';
                $temp['value'] = $param['data'];
                if ($id = Params::where('type', $param['type'])
                    ->where('request_id', $param['request_id'])
                    ->where('name', 'raw')
                    ->value('id')) {
                    $temp['id'] = $id;
                } else {
                    $temp['creator_uid'] = is_login();
                }
                $temp['request_id'] = $param['request_id'];
                $temp['type'] = $param['type'];
                $temp['updator_uid'] = is_login();
                $data[] = $temp;
                break;
        }

        $save_ids = array_column($data, 'id');

        Db::startTrans();
        Params::where('request_id', $param['request_id'])
            ->where('type', $param['type'])
            ->whereNotIn('id', $save_ids)->delete();
        (new Params())->saveAll($data);
        Db::commit();

        return $this->successResponse('保存成功');
    }
}
