<?php

/**
 *
 * @author lwb Time 20150917
 */
class ListModel extends Model {
    protected $tableName = '__NONE__';
    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     * 编辑列表模板活动
     *
     * @param unknown $nodeId
     * @param unknown $mId
     */
    public function editBasicInfo($nodeId, $mId, $data) {
        $rule = array(
            'page_name' => array(
                'null' => false, 
                'maxlen_cn' => '20', 
                'name' => '页面名称'), 
            'list_name' => array(
                'null' => false, 
                'maxlen_cn' => '20', 
                'name' => '列表名称'), 
            'banner_pic' => array(
                'null' => true, 
                'maxlen' => '250', 
                'name' => 'banner图'), 
            'nav_1' => array(
                'null' => true, 
                'maxlen_cn' => '8', 
                'name' => '导航栏一'), 
            'nav_2' => array(
                'null' => true, 
                'maxlen_cn' => '8', 
                'name' => '导航栏二'), 
            'TabNav' => array(
                'null' => true, 
                'name' => '检索栏'), 
            'list_type' => array(
                'null' => false, 
                'strtype' => 'int', 
                'minval' => '0', 
                'maxval' => '1', 
                'name' => '列表样式'), 
            'introduce' => array(
                'null' => true, 
                'maxlen_cn' => '140', 
                'name' => '站点简介'), 
            'share_pic' => array(
                'null' => true, 
                'maxlen' => '250', 
                'name' => '分享图标'), 
            'list_sort' => array(
                'null' => true, 
                'name' => '列表排序'));
        $filterData = D('Verify')->verifyReqData($rule, $data);
        $mInfoModel = M('tmarketing_info');
        $readyData = array(
            'name' => $filterData['list_name'],  // 活动名称
            'status' => 1, 
            'start_time' => date('YmdHis'), 
            'end_time' => date('YmdHis', strtotime("+10 year")), 
            'add_time' => date('YmdHis'), 
            'node_id' => $nodeId, 
            'batch_type' => CommonConst::BATCH_TYPE_LIST, 
            'is_show' => '1', 
            'node_name' => $filterData['page_name'], 
            'share_pic' => $filterData['share_pic']);
        $configData = array(
            'banner_pic' => $filterData['banner_pic'], 
            'nav_1' => $filterData['nav_1'], 
            'nav_2' => $filterData['nav_2'], 
            'tab_nav' => $filterData['TabNav'], 
            'list_type' => $filterData['list_type'], 
            'share_descript' => str_replace(
                array(
                    "\r\n", 
                    "\r", 
                    "\n"), "", stripslashes($filterData['introduce'])));
        $tranDb = M();
        $tranDb->startTrans();
        $isEdit = true;
        if ($mId) { // 编辑
            $map = array(
                'id' => $mId, 
                'node_id' => $nodeId);
            $configDataSer = $mInfoModel->where($map)->getField('config_data');
            if (! $configDataSer) {
                throw_exception('参数错误');
            }
            $configDataBefore = unserialize($configDataSer);
            $configData = array_merge($configDataBefore, $configData);
            $readyData['config_data'] = serialize($configData);
            $result = $mInfoModel->where($map)->save($readyData);
        } else { // 新增
            $readyData['config_data'] = serialize($configData);
            $result = $mInfoModel->add($readyData);
            $mId = $result;
            $isEdit = false;
        }
        if (false === $result) {
            $tranDb->rollback();
            throw_exception('编辑数据错误');
        }
        $draftArr = M('tdraft')->where(
            array(
                'node_id' => $nodeId, 
                'type' => '3'))->select();
        $content = array();
        $draftId = '';
        $toBeCheckedMid = $isEdit ? $mId : ''; // 用于检验的mId,新增的草稿里mId草稿里是空的,编辑的话就是mId
        foreach ($draftArr as $oneDraft) {
            $contentArr = json_decode($oneDraft['content'], true);
            if ($contentArr['m_id'] == $toBeCheckedMid) {
                $content = $contentArr;
                $draftId = $oneDraft['id'];
                break;
            }
        }
        if (empty($content)) {
            $tranDb->rollback();
            throw_exception('找不到草稿');
        } else {
            $detail = $content['detail'];
            $listBatchModel = M('tlist_batch_list');
            $listIdArr = $listBatchModel->where(
                array(
                    'list_id' => $mId, 
                    'status' => '1'))->getField('id', true);
            if (! $listIdArr) {
                $listIdArr = array();
            }
            // 分出那些需要删除的list的id(修改状态为2)
            $leftListIdArr = array(); // 如果list_sort没有listid,表示被删除了
            foreach ($filterData['list_sort'] as $list) {
                if ($list['listid']) {
                    $leftListIdArr[] = $list['listid'];
                }
            }
            $needChangeStatusArr = array_diff($listIdArr, $leftListIdArr);
            if (! empty($needChangeStatusArr)) {
                $listRe = $listBatchModel->where(
                    array(
                        'id' => array(
                            'in', 
                            $needChangeStatusArr)))->save(
                    array(
                        'status' => '2'));
                $len = count($needChangeStatusArr);
                if ($len != $listRe) {
                    $tranDb->rollback();
                    throw_exception('列表数据错误');
                }
            }
            
            foreach ($filterData['list_sort'] as $k => $list) {
                $thisDetail = $this->getThisDetail($list, $detail);
                $tabNav = $thisDetail['TabNav'];
                $keyword = '';
                foreach ($tabNav as $value) {
                    $keyword .= implode(',', $value) . ',';
                }
                $keyword = substr($keyword, 0, - 1);
                $saveData = array(
                    'list_id' => $mId, 
                    'name' => $thisDetail['title'], 
                    'short_note' => $thisDetail['text'], 
                    'nav_id' => $thisDetail['dateSideNav'], 
                    'keyword' => $keyword, 
                    'pic' => $thisDetail['src'], 
                    'bgcolor' => $thisDetail['bgcolor'], 
                    'batch_id' => ($thisDetail['urltype'] == '-1') ? '0' : $thisDetail['rel_m_id'], 
                    'batch_type' => ($thisDetail['urltype'] == '-1') ? '0' : $thisDetail['rel_batch_type'], 
                    'url' => $thisDetail['url'], 
                    'node_id' => $nodeId, 
                    'add_time' => date('YmdHis'), 
                    'list_sort_id' => ($k + 1), 
                    'addtime_show_flag' => '1', 
                    'status' => '1');
                if ($list['listid']) {
                    if (in_array($list['listid'], $listIdArr)) {
                        $listRe = $listBatchModel->where(
                            array(
                                'id' => $list['listid']))->save($saveData); // 修改列表
                    } else {
                        $tranDb->rollback();
                        throw_exception('列表数据错误');
                    }
                } else { // 新增列表
                    $listRe = $listBatchModel->add($saveData); // 修改列表
                }
                if (false === $listRe) {
                    $tranDb->rollback();
                    throw_exception('存储列表数据错误');
                }
            }
            $delRe = M('tdraft')->where(
                array(
                    'id' => $draftId, 
                    'node_id' => $nodeId, 
                    'type' => CommonConst::DRAFT_TYPE_LIST))->delete();
            if (false == $delRe) {
                $tranDb->rollback();
                throw_exception('删除草稿错误');
            }
        }
        $tranDb->commit();
        return $mId;
    }

    /**
     * 添加到草稿
     *
     * @param unknown $nodeId
     * @param unknown $mId
     * @param unknown $data
     */
    public function addDraft($nodeId, $mId, $data) {
        $rule = array(
            'temp_id' => array(
                'null' => true, 
                'name' => '临时编号'), 
            'id' => array(
                'null' => true, 
                'name' => '列表id'), 
            'rel_m_id' => array(
                'null' => true, 
                'name' => '关联的活动号'), 
            'rel_batch_type' => array(
                'null' => true, 
                'name' => '关联的活动种类'), 
            'title' => array(
                'null' => false, 
                'maxlen_cn' => '20', 
                'name' => '标题'), 
            'text' => array(
                'null' => false, 
                'maxlen_cn' => '120', 
                'name' => '摘要'), 
            'src' => array(
                'null' => true, 
                'name' => '图标'), 
            'url' => array(
                'null' => false, 
                'name' => '跳转链接'), 
            'urltype' => array(
                'null' => false, 
                'name' => '跳转链接类型'), 
            'dateSideNav' => array(
                'null' => true, 
                'name' => '导航栏'), 
            'TabNav' => array(
                'null' => true, 
                'name' => '检索栏'), 
            'bgcolor' => array(
                'null' => true, 
                'name' => '背景色'));
        try {
            $data = D('Verify')->verifyReqData($rule, $data);
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
        $da['m_id'] = $mId;
        $da['detail'][0] = $data;
        $jsData = json_encode($da);
        $map = array(
            'node_id' => $nodeId, 
            'type' => CommonConst::DRAFT_TYPE_LIST);
        $draftArr = M('tdraft')->where($map)->select();
        $content = array();
        $draftId = '';
        foreach ($draftArr as $oneDraft) {
            $contentArr = json_decode($oneDraft['content'], true);
            if ($contentArr['m_id'] == $mId) {
                $content = $contentArr;
                $draftId = $oneDraft['id'];
                break;
            }
        }
        if (! empty($content)) {
            $detail = $content['detail'];
            $isEdit = false;
            foreach ($detail as $k => $listDetail) {
                if ($data['id'] && $listDetail['id'] == $data['id'] || empty(
                    $data['id']) && $data['temp_id'] &&
                     $listDetail['temp_id'] == $data['temp_id']) {
                    $detail[$k] = $data;
                    $isEdit = true;
                }
            }
            if ($isEdit == false) {
                array_unshift($detail, $data);
            }
            $content['detail'] = $detail;
            $jsData = json_encode($content);
        } else {
            throw_exception('未找到草稿');
        }
        $readyData = array(
            'node_id' => $nodeId, 
            'content' => $jsData, 
            'add_time' => date('Y-m-d H:i:s'), 
            'type' => '3'); // 表示是列表模板的草稿
                            
        // 编辑草稿
        $re = M('tdraft')->where(array(
            'id' => $draftId))->save($readyData);
        if (false === $re) {
            throw_exception('编辑草稿失败');
        }
    }

    /**
     * 获取channelId,没有的话会创建
     *
     * @param unknown $nodeId
     * @param unknown $channelType
     * @param unknown $channelSnsType
     * @param unknown $channelName
     * @return Ambigous <mixed, NULL, unknown, multitype:Ambigous <unknown,
     *         string> unknown , boolean, string>
     */
    public function getChannelId($nodeId, $channelType, $channelSnsType, 
        $channelName) {
        $cid = M('tchannel')->where(
            array(
                'node_id' => $nodeId, 
                'type' => $channelType, 
                'sns_type' => $channelSnsType))->getField('id');
        if (! $cid) { // 不存在则添加渠道
                      // 营销活动不存在则新增
            $channel_arr = array(
                'name' => $channelName, 
                'type' => $channelType, 
                'sns_type' => $channelSnsType, 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $nodeId);
            $cid = M('tchannel')->add($channel_arr);
            if (! $cid) {
                throw_exception('添加' . $channelName . '失败');
            }
        }
        return $cid;
    }

    /**
     * 获取草稿内容（如果没有创建草稿）
     *
     * @param unknown $nodeId
     * @param unknown $mId
     * @return array('data' => 主数据,'listData' => 列表数据);
     */
    public function getListData($nodeId, $mId) {
        $draftArr = M('tdraft')->where(
            array(
                'node_id' => $nodeId, 
                'type' => CommonConst::DRAFT_TYPE_LIST))->select();
        $content = array();
        foreach ($draftArr as $oneDraft) {
            $contentArr = json_decode($oneDraft['content'], true);
            if ($mId == $contentArr['m_id']) {
                $content = $contentArr;
                break;
            }
        }
        $mInfo = array();
        if ($mId) {
            $mInfo = M('tmarketing_info')->where(
                array(
                    'node_id' => $nodeId, 
                    'id' => $mId))->find();
            if (! $mInfo) {
                throw_exception('数据错误');
            }
        }
        if (empty($content)) {
            if ($mId) {
                $listInfo = M('tlist_batch_list')->where(
                    array(
                        'list_id' => $mId, 
                        'node_id' => $nodeId, 
                        'status' => '1'))
                    ->order('list_sort_id asc')
                    ->select();
                $detail = array();
                foreach ($listInfo as $value) {
                    $keyword = explode(',', $value['keyword']);
                    $detail[] = array(
                        'temp_id' => '', 
                        'id' => $value['id'], 
                        'rel_m_id' => $value['batch_id'], 
                        'rel_batch_type' => $value['batch_type'], 
                        'title' => $value['name'], 
                        'text' => $value['short_note'], 
                        'src' => $value['pic'], 
                        'url' => $value['url'], 
                        'urltype' => $value['batch_id'] ? '4' : '-1', 
                        'dateSideNav' => $value['nav_id'], 
                        'TabNav' => array(
                            $keyword), 
                        'bgcolor' => $value['bgcolor']);
                }
                $content = array(
                    'm_id' => $mId, 
                    'detail' => $detail);
            } else {
                $content = array(
                    'm_id' => '', 
                    'detail' => array());
            }
            $draftData = array(
                'node_id' => $nodeId, 
                'content' => json_encode($content), 
                'add_time' => date('Y-m-d H:i:s'), 
                'type' => CommonConst::DRAFT_TYPE_LIST);
            M('tdraft')->add($draftData);
        }
        $list = $content['detail'];
        $data = array();
        $listData = array();
        if (! empty($mInfo)) {
            $configData = unserialize($mInfo['config_data']);
            $data['page_name'] = $mInfo['node_name'];
            $data['list_name'] = $mInfo['name'];
            $data['share_pic'] = $mInfo['share_pic'];
            $data['introduce'] = $configData['share_descript'];
            $data['list_type'] = $configData['list_type'];
            $listData['banner'] = $configData['banner_pic'];
            $listData['dateSideNav'] = array(
                array(
                    'id' => '1', 
                    'title' => $configData['nav_1']), 
                array(
                    'id' => '2', 
                    'title' => $configData['nav_2']));
            $listData['TabNav'] = $configData['tab_nav'];
            $listData['list'] = array(
                'type' => $configData['list_type'], 
                'list' => $list);
        } else {
            $nodeInfo = M('tnode_info')->where(
                array(
                    'node_id' => $this->node_id))
                ->field(
                array(
                    'node_short_name', 
                    'head_photo'))
                ->find();
            $data['page_name'] = $nodeInfo['node_short_name'];
            $data['list_name'] = '';
            $data['share_pic'] = $nodeInfo['head_photo'] ? get_upload_url(
                $nodeInfo['head_photo']) : '__PUBLIC__/Image/wap-logo-wc.png';
            $data['introduce'] = '';
            $data['list_type'] = '1';
            $listData['banner'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                 '/Label/Image/activitylist/banner.png';
            $listData['dateSideNav'] = array(
                array(
                    'id' => '1', 
                    'title' => '导航栏一'), 
                array(
                    'id' => '2', 
                    'title' => '导航栏二'));
            $listData['TabNav'] = array(
                array(
                    'id' => '1', 
                    'title' => '检索样例1', 
                    'list' => array(
                        '关键词1', 
                        '关键词2', 
                        '关键词3')), 
                array(
                    'id' => '2', 
                    'title' => '检索样例2', 
                    'list' => array(
                        '关键词4', 
                        '关键词5', 
                        '关键词6', 
                        '关键词7', 
                        '关键词8')));
            $listData['list'] = array(
                'type' => '1', 
                'list' => $list);
        }
        return array(
            'data' => $data, 
            'listData' => $listData);
    }

    /**
     * 删除某个列表的草稿
     *
     * @param unknown $nodeId
     * @param unknown $mId
     * @param unknown $tempId
     * @param unknown $listId
     */
    public function delListDraft($nodeId, $mId, $tempId, $listId) {
        $markId = $tempId;
        $idType = 'temp_id';
        if ($listId) {
            $idType = 'id';
            $markId = $listId;
        }
        $result = M('tdraft')->where(
            array(
                'node_id' => $nodeId, 
                'type' => CommonConst::DRAFT_TYPE_LIST))->select();
        $content = array();
        $draftId = '';
        foreach ($result as $draft) {
            $thisContent = json_decode($draft['content'], true);
            if ($thisContent['m_id'] == $mId) {
                $content = $thisContent;
                $draftId = $draft['id'];
                break;
            }
        }
        if (empty($content)) {
            throw_exception('操作有误');
        }
        $detail = $content['detail'];
        $newDetail = array(); // 把原来存在于detail的与传过来的id或者tempid一样的元素去除掉
        foreach ($detail as $v) {
            if ($v[$idType] != $markId) {
                $newDetail[] = $v;
            }
        }
        $content['detail'] = $newDetail;
        $contentJson = json_encode($content);
        $re = M('tdraft')->where(
            array(
                'id' => $draftId, 
                'node_id' => $nodeId, 
                'type' => CommonConst::DRAFT_TYPE_LIST))->save(
            array(
                'content' => $contentJson));
        if ($re == false) {
            throw_exception('删除错误');
        }
    }

    /**
     * 清除草稿
     *
     * @param unknown $nodeId
     */
    public function delDraft($nodeId, $mId) {
        $draftModel = M('tdraft');
        $result = $draftModel->where(
            array(
                'node_id' => $nodeId, 
                'type' => CommonConst::DRAFT_TYPE_LIST))->select();
        foreach ($result as $draft) {
            $content = json_decode($draft['content'], true);
            if ($content['m_id'] == $mId) {
                $draftModel->where(
                    array(
                        'id' => $draft['id']))->delete();
                break;
            }
        }
    }

    public function getThisDetail($list, $detail) {
        $confirm_id = 'temp_id';
        $useId = 'tempid';
        if ($list['listid']) {
            $confirm_id = 'id';
            $useId = 'listid';
        }
        $thisDetail = '';
        foreach ($detail as $k => $v) {
            if ($v[$confirm_id] == $list[$useId]) {
                $thisDetail = $v;
                break;
            }
        }
        return $thisDetail;
    }
}