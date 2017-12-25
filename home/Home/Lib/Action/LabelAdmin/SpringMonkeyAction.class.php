<?php

/**
 * 金猴闹春活动
 * 
 * @author WangYan
 */
class SpringMonkeyAction extends BaseAction {

    public $m_id = '';

    public function _initialize() {
        parent::_initialize();
        // 引入常量类
        import('@.Vendor.CommonConst') or die('CommonConst is not found!');
    }

    /**
     * 提前校验权限
     */
    public function beforeCheckAuth() {
        // 设置首页访问权限
        $this->_authAccessMap = '*';
    }

    /**
     * 在系统权限校验之后，再校验
     */
    public function afterCheckAuth() {
    
    }

    public function setActBasicInfo() {
        if (IS_POST) {
            // post参数校验
            $this->runVDT('LabelAdmin.SpringMonkeyVDT.setActBasicInfo');
            // 保存数据
            $retInfo = D('SpringMonkey')->saveMarketInfo(I('post.'), 
                $this->nodeInfo);
            // 判断保存结果
            if (! $retInfo['status']) {
                $this->error($retInfo['error']);
            }
            $this->success(array('id' => $retInfo['id'], 'msg' => '提交成功'), '', true);
        }
        // 获取请求参数
        $requestInfo = I('get.');
        // 获取活动信息
        $marketInfo = D('MarketCommon')->getMarketInfo($this->nodeId, 
            get_val($requestInfo,'m_id'));
        // 处理活动信息
        $retInfo = D('SpringMonkey')->handleMarketInfo($marketInfo, 
            $this->nodeInfo);
        // 处理其他信息，及因收费导致的编辑限制
        $retInfo = D('SpringMonkey')->handleOthers($retInfo);

        $m_id = get_val($retInfo, 'id');
        $this->assign('m_id', $m_id);
        $this->assign('retInfo', $retInfo);
        $this->display();
    }

    public function setActConfig() {
        if (IS_POST) {
            // post参数校验
            $this->runVDT('LabelAdmin.SpringMonkeyVDT.setActConfig');
            // 保存数据
            $retInfo = D('SpringMonkey')->saveActConfig(I('post.'), 
                $this->nodeInfo);
            // 判断保存结果
            if (! $retInfo['status']) {
                $this->error($retInfo['error']);
            }
            $this->success('提交成功');
        }
        // 获取请求参数
        $requestInfo = I('get.');
        // 获取活动信息
        $marketInfo = D('MarketCommon')->getMarketInfo($this->nodeId, 
            get_val($requestInfo,'m_id'));
        // 校验活动信息是否存在
        ! empty($marketInfo) or $this->error('营销活动不存在！');
        // 处理活动信息
        $retInfo = D('SpringMonkey')->handleConfigInfo($marketInfo, 
            $this->nodeInfo);
        // 处理其他信息
        $retInfo = D('SpringMonkey')->handleOtherConfig($retInfo, $requestInfo);
        $this->assign('retInfo', $retInfo);
        //微信授权的帮助链接
        $wxsqHelp = U('Home/Help/helpDetails', array('newsId' => C('wxsq_help_id'), 'classId' => C('wxsq_help_class_id')));
        $this->assign('wxsqHelp', $wxsqHelp);
        $this->display();
    }

    public function setPrize() {
        if (IS_POST) {
            // 获取请求参数
            $requestInfo = I('request.');
            // 保存数据
            $retInfo = D('SpringMonkey')->saveActPrize($requestInfo, $this->nodeInfo);
            // 判断保存结果
            if (! $retInfo['status']) {
                $this->error($retInfo['error']);
            }
            // 顺便发布到多乐互动专用渠道上
            $bchId = D('MarketCommon')->chPublish($this->nodeId,get_val($requestInfo,'m_id'));
            if($bchId === false){
                $this->error('发布到渠道失败');
            }
            $this->success('提交成功','',array('bchId'=>$bchId));
        }
        // 获取请求参数
        $requestInfo = I('get.');
        // 获取活动信息
        $marketInfo = D('MarketCommon')->getMarketInfo($this->nodeId, 
            get_val($requestInfo,'m_id'));
        // 校验活动信息是否存在
        ! empty($marketInfo) or $this->error('营销活动不存在！');
        // 处理活动信息
        $retInfo = D('SpringMonkey')->handlePrizeInfo($marketInfo, 
            $this->nodeInfo);
        // 处理其他信息
        $retInfo = D('SpringMonkey')->handleOtherPrize($retInfo, $requestInfo);

        $m_id = get_val($retInfo, 'id');
        $this->assign('m_id', $m_id);

        $this->assign('retInfo', $retInfo);
        $this->assign('jp_arr', $retInfo['jp_array']);
        $this->assign('cj_rule_arr', $retInfo['cj_rule_arr']);
        $this->assign('cj_cate_arr', $retInfo['cj_cate_arr']);
        $this->display();
    }
    /**
     * [addAward 添加奖品]
     */
    public function addAward() {
        // 获取请求参数
        $requestInfo = I('get.');
        // 如果没有b_id表示添加奖品
        if (! $requestInfo['b_id']) {
            $url = U('Common/SelectJp/addToPrizeItem', 
                [
                    'm_id' => $requestInfo['m_id'], 
                    'prizeCateId' => $requestInfo['prizeCateId']]);
            $param = array(
                'next_step'    => urlencode($url),
                'availableTab' => '1,2,4', 
                'availableSourceType' => '0,1'
                );
            $this->redirect('Common/SelectJp/indexNew', $param);
        }
    }
    /**
     * 发布活动
     */
    public function publish() {
        // 获取请求参数
        $requestInfo = I('get.');
        // 获取活动信息
        $marketInfo = D('MarketCommon')->getMarketInfo($this->nodeId, 
            get_val($requestInfo,'m_id'));
        // 校验活动信息是否存在
        ! empty($marketInfo) or $this->error('营销活动不存在！');
        // 跳转参数
        $param = [
            'batch_id' => $requestInfo['m_id'], 
            'batch_type' => CommonConst::BATCH_TYPE_SPRINGMONKEY, 
            'isReEdit' => I('isReEdit', '1')];
        $this->redirect('LabelAdmin/BindChannel/index', $param);
    }
    /**
     * [editStatus 编辑活动状态]
     * @return [type] [json]
     */
    public function editStatus() {
        // 获取请求参数
        $requestInfo = I('post.');
        // 处理其他信息
        $retInfo = D('SpringMonkey')->changeStatus($requestInfo, 
            $this->nodeInfo);
        if (! $retInfo['status']) {
            $this->error($retInfo['error']);
        }
        $this->success('修改成功');
    }

}