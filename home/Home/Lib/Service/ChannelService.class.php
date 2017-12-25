<?php
/**
*   渠道
 */


class ChannelService  {

    //最大备注数
    private static $max_memo = 5;
    //搜索活动的结果集
    public  $list;

    /**
     *条件筛选
     *
     * @return array
     **/
    public function filterCondition($map,$id){

        $cName = I('c_name', null, 'mysql_real_escape_string');
        if (isset($cName) && $cName != '') {
            $map['tchannel.name'] = array(
                    'like',
                    "%{$cName}%");
        }
        $memo = I('c_label', null, 'mysql_real_escape_string');
        if (isset($memo) && $memo != '') {
            $memo_list = array();
            $memo_arr = array();
            $memo = str_replace('，',',',$memo);
            $memo_list = explode(',',$memo);

            foreach($memo_list as $k => $v){
                $memo_arr[] = '%'.$v.'%';
            }
            $map['tchannel.memo'] = array(
                    'like',
                    $memo_arr,'OR');
        }
        //获得渠道ID
        $activity_name = I('activity_name', null, 'mysql_real_escape_string');
        if (isset($activity_name) && $activity_name != '') {

            $data = array();
            $data['tmarketing_info.name'] = array(
                    'like',
                    "%{$activity_name}%");
            $data['S.node_id'] = $id;
            $data['T.type'] = CommonConst::CHANNEL_TYPE_MY_CHANNEL;

            $result = $this->getChannelIDbyActivityName($data);

            if($result){
                $channel_id = [];
                $result_list = [];
                foreach($result as $k =>$v){
                    $channel_id = $v['channel_id'];
                    //结果集 channel_id batch_id
                    $result_list[] = array(
                        'channel_id' => $v['channel_id'],
                        'batch_id' => $v['batch_id']
                    );

                    $this->list = $result_list;
                }
                $map['tchannel.id'] = array(
                        'eq',
                        $channel_id,'OR');
            }
        }

        return $map;
    }
    /**
     * 首页
     **/
    public function index($map, $map_group, $node_id)
    {
        $model = M('tchannel');

        $count = $model->where($map)->count();
        import("ORG.Util.Page");

        $Page = new Page($count, 10);
        $show = $Page->show();
        //渠道列表，访问量，总活动数
        $list = $model->join('tbatch_channel T ON T.channel_id=tchannel.id')
                ->where($map)->group('tchannel.id')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->field(
                        'tchannel.id,tchannel.name,tchannel.add_time,tchannel.memo,count(T.id) as num_all,sum(T.click_count) as click')
                ->order($map_group)
                ->select();
        //进行中活动数
        $list_ = $model->join('tbatch_channel T ON T.channel_id=tchannel.id')->join(
                        'tmarketing_info M ON M.id=T.batch_id')
                ->where(
                        array(
                                'tchannel.node_id' => $node_id,
                                'tchannel.type'    => CommonConst::CHANNEL_TYPE_MY_CHANNEL,
                                'M.start_time'     => array('elt', date('Ymdhis')),
                                'M.end_time'       => array('egt', date('Ymdhis'))
                        )
                )->group('T.channel_id')
                ->field('tchannel.id as tid,count(T.id) as num_now')
                ->order($map_group)
                ->select(
                );

        foreach ($list as $k1 => $v1) {
            $list[$k1]['num_now'] = 0;
            $id                   = $v1['id'];
            if (!empty($v1['memo']) && $v1['memo'] !== null) {
                $memoList = explode(',', $v1['memo']);
                $list[$k1]['memoList'] = $memoList;
            }
            foreach ($list_ as $k2 => $v2) {
                if ($v2['tid'] == $id) {
                    $list[$k1]['num_now'] = $v2['num_now'];
                }
            }
        }


        return array($list, $Page, $show);

    }
    /**
     * 组合排序语句
     *
     * @return array
     **/
    public function sort($sortByVisit,$sortByActivitynum,$clickType){
        $map_group = "tchannel.add_time DESC";

        if($clickType == 'sortByVisit') {
            if ($sortByVisit == 'asc') {

                $map_group = "click ASC," . $map_group;
            } else if ($sortByVisit == 'desc') {
                $map_group = "click DESC," . $map_group;

            }
        }
        if($clickType == 'sortByActivitynum') {
            if ($sortByActivitynum == 'asc') {
                $map_group = "num_all ASC," . $map_group;
            } else if ($sortByActivitynum == 'desc') {
                $map_group = "num_all DESC," . $map_group;
            }
        }

        return $map_group;

    }

    /**
     *通过活动名称获得渠道id
     *
     * @return array
     */
    public function getChannelIDbyActivityName($data){
        $model = M('tmarketing_info');
        $result = $model->join('tbatch_channel S ON S.batch_id=tmarketing_info.id')
                ->join('tchannel T ON T.id = S.channel_id')
                ->field('S.channel_id,S.batch_id')
                ->where($data)
                ->select();
        return $result;
    }


    /**
     * 获得渠道数量
     * @param $map
     * @return mixed
     */
    public function getChannelCount ($map)
    {
        $model    = M ('channel');
        $mapcount = $model->join ()->where ($map)->count ();
        return $mapcount;
    }


    /**
     * 获得渠道名称
     *
     * @param $id
     * @return mixed
     */
    public function getChannelNameById ($id)
    {
        $model = M('tchannel');
        $name  = $model->where('id=' . $id)->getField('name');
        return $name;
    }

    /**
     * 添加渠道处理
     *
     * @param $nodeId
     * @return bool
     */
    public function addSubmit ($nodeId)
    {

        $data                 = I ('post.', '', 'trim');
        $model                = M ('tchannel');
        $data_arr             = array();
        $data_arr['name']     = $data['name'];
        $data_arr['add_time'] = date ('YmdHis');
        $data_arr['node_id']  = $nodeId;
        $data_arr['status']   = '1';
        $data_arr['type']     = CommonConst::CHANNEL_TYPE_MY_CHANNEL;

        //开启事务
        $tranDb = new Model();
        $tranDb->startTrans ();

        $execute = $model->add ($data_arr);
        if (!$execute) {
            $tranDb->rollback ();
            return false;
        } else {

            $tranDb->commit ();
            return true;
        }
    }
    /**
     * 编辑渠道处理
     * @param $nodeId
     *
     * @return bool
     */
    public function editSubmit ($id, $name)
    {

        $model        = M('tchannel');
        $data         = array();
        $data['name'] = $name;
        //        $data['add_time'] = date('YmdHis');

        //开启事务
        $tranDb = new Model();
        $tranDb->startTrans();

        $execute = $model->where('id=' . $id)->save($data);
        if (!$execute) {
            $tranDb->rollback();
            return false;
        } else {
            $tranDb->commit();
            return true;
        }
    }

    /**
     * 添加备注
     *
     * @param $channel_id
     * @return int
     */
    public function memoAdd($channel_id)
    {
        $memo     = str_replace ('，', ',', trim (I ('post.name')));
        $memoList = array();
        $memoList = explode (',', $memo);


        foreach ($memoList as $k => $v) {
            if (trim ($v) == '') {
                unset($memoList[$k]);
            }
        }
        $memoList = array_merge ($memoList);
        $memo     = implode (',', $memoList);
        if ($memo == '') {
            return 5;
        }

        $channelModel = M ('tchannel');

        $memoOld = $channelModel->field ('memo')->where (array('id' => $channel_id))->find ();

        if (empty($memoOld['memo'])) {
            $arr_ = $memo;
        } else {
            $memoListOld = explode (',', $memoOld['memo']);
            if ((count ($memoList) + count ($memoListOld)) > self::$max_memo) {
                return 1;
            }
            foreach ($memoList as $k => $v) {
                if (in_array ($v, $memoListOld)) {
                    return 2;
                }
            }
            $arr_ = $memoOld['memo'] . ',' . $memo;
        }

        $result = $channelModel->where ('id=' . $channel_id)->setField ('memo', $arr_);;

        if ($result) {
            return 3;
        } else {
            return 4;
        }
    }
    /**
     * 删除备注
     *
     * @param $channel_id
     * @return boolean
     */
    public function memoDelete ($memoDelete,$channel_id)
    {

        $model = M('tchannel');
        $memo = $model->where(
                array(
                  'id' => $channel_id
                ))
                ->getField('memo');
        $list = explode(',',$memo);

        foreach ($list as $k =>$v) {
            if($v == $memoDelete[0]){
               unset($list[$k]);
            }
        }
        $list=implode(',', array_merge($list));
        $result = $model->where(
                array(
                        'id' => $channel_id
                ))
                ->setField('memo',$list);
    return $result;
    }



    /**
     * 渠道删除
     *
     * @param $id
     * @param $node_id
     * @return bool
     */
    public function deleteChannel ($id, $node_id)
    {
        $channelModel = M ('tchannel');
        $batchModel   = M ('tbatch_channel');
        //开启事务
        $tranDb = new Model();
        $tranDb->startTrans ();

        $execute = $channelModel->where (
                        array(
                                'id'      => $id
                        )
                )->delete ();

        $execute1 = $batchModel->where (
                        array(
                                'channel_id' => $id,
                                'node_id'    => $node_id
                        )
                )->delete ();
        if ($execute) {
            $tranDb->commit ();
            return true;
        } else {
            $tranDb->rollback ();
            return false;
        }

    }

    /**
     * 活动删除
     *
     * @param $id
     * @return mixed
     */
    public function deleteActivity ($id)
    {
        $tbatchModel = M('tbatch_channel');
        $result      = $tbatchModel->where('id=' . $id)->delete();
        return $result;
    }


    /**
     * 添加二维码处理
     *
     * @param $nodeId
     * @return bool
     */
    public function addSubmitQrCodes ($nodeId)
    {

        $data                 = I ('post.', '', 'mysql_real_escape_string');
        $model                = M ('tchannel');
        $data_arr             = array();
        $data_arr['name']     = $data['name'];
        $data_arr['add_time'] = date ('YmdHis');
        $data_arr['node_id']  = $nodeId;
        $data_arr['status']   = '1';
        $data_arr['type']     = CommonConst::CHANNEL_TYPE_CODE_LABEL_CHANNEL;

        //开启事务
        $tranDb = new Model();
        $tranDb->startTrans ();

        $execute = $model->add ($data_arr);
        if (!$execute) {
            $tranDb->rollback ();
            return false;
        } else {

            $tranDb->commit ();
            return true;
        }
    }
    /**
     * 获得二维码首页信息
     *
     * @param $nodeId
     * @return bool
     */
    public function qrCodes($map)
    {

        $map   = $this->getMapByFilter($map);
        $model = M('tchannel');
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page     = new Page($mapcount, 6); // 实例化分页类 传入总记录数和每页显示的记录数
        $show     = $Page->show(); // 分页显示输出

        $list = $model->where($map)->order('status asc,add_time DESC')->limit(
                        $Page->firstRow . ',' . $Page->listRows
                )->select();

        $mod          = M('tmarketing_info');
        $marketActive = D('MarketActive');

        if ($list) {
            foreach ($list as $k => $v) {
                $alreadySelected = D('SelectBatches')->getBindedChannelTime(
                        $v['node_id'],
                        $v['id']
                );

                if (!empty($alreadySelected) && is_array($alreadySelected)) {
                    foreach ($alreadySelected as $key => $value) {
                        $query                            = $mod->where(
                                array(
                                        'node_id' => $v['node_id'],
                                        'id'      => $value['batch_id'],

                                )
                        )->find();
                        $alreadySelected[$key]['info']    = $query;
                        $alreadySelected[$key]['editUrl'] = $marketActive->getEditUrl(
                                $value['batch_id']
                        );
                    }

                }
                $list[$k]['binded']     = $alreadySelected;
                $store_id               = $v['store_id'];
                $row                    = M('tstore_info')->field('store_name')->where(
                                array(
                                        'store_id' => $store_id
                                )
                        )->find();
                $list[$k]['store_name'] = $row['store_name'];
            }
        }
        return array( $list, $show );
    }
    public function getMapByFilter($map)
    {
        $data = array();

        $cName = I ('c_name', null, 'mysql_real_escape_string');
        if (isset($cName) && $cName != '') {
            $map['tchannel.name'] = array(
                    'like',
                    "%{$cName}%"
            );
        }
        $addtime = I ('start_time');
        if ($addtime != '') {
            $map['tchannel.add_time'] = array(
                    'egt',
                    $addtime
            );
        }
        $addtime2 = I ('end_time');
        if ($addtime2 != '') {
            $map['tchannel.end_time'] = array(
                    'elt',
                    $addtime2
            );
        }
        return $map;
    }

    public function getActivityId($channel_id)
    {

        $model   = M('tbatch_channel');
        $batchId = $model->where(
                array(
                        'channel_id' => $channel_id
                )
        )->field('id')->select();

        return $batchId;
    }


}
