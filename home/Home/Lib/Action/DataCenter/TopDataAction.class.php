<?php

/**
 * top数据
 */
class TopDataAction extends BaseAction {
    protected $clickSum = array(); // 统计量
    protected $year = '';

    public function _initialize(){
        parent::_initialize();
        $this->year = I('post.year');
        $this->assign('year',$this->year);
    }
    // 渠道数据
    public function index() {
        $tab = I('get.tab','0');
        switch ($tab) {
            case '0':
                self::getVisitChannelTop();
                break;
            case '1':
                self::getSendChannelTop();
                break;
            case '2':
                self::getMemberChannelTop();
                break;
            case '3':
                self::getRecentlyChannelTop();
                break;
            default:
                die('参数错误!');
                break;
        }
        $this->assign('tab',$tab);
        $this->assign('clickSum',$this->clickSum);
        $this->display();
    }
    // 活动数据
    public function batchData() {
        $tab = I('get.tab','0');
        switch ($tab) {
            case '0':
                self::getVisitTop();
                break;
            case '1':
                self::getSendTop();
                break;
            case '2':
                self::getMemberTop();
                break;
            case '3':
                self::getCjTop();
                break;
            case '4':
                self::getRecentlyTop();
                break;
            default:
                die('参数错误!');
                break;
        }
        $this->assign('tab',$tab);
        $this->assign('clickSum',$this->clickSum);
        $this->display();
    }
    
    // 卡券数据
    public function goodsData() {
        $tab = I('get.tab','0');
        switch ($tab) {
            case '0':
                self::getVisitCardTop();
                break;
            case '1':
                self::getSendCardTop();
                break;
            case '2':
                self::getBestCardTop();
                break;
            default:
                die('参数错误!');
                break;
        }
        $this->assign('tab',$tab);
        $this->assign('clickSum',$this->clickSum);
        $this->display();
    }

    /***************以下是私有方法*****************/
    
    private function getVisitTop(){
        // 访问量最高的10个活动
        $map['node_id'] = $this->nodeId;
        if($this->year){
            $map['add_time'][] = array('EGT',$this->year.'0000000000'); 
            $map['add_time'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M('tmarketing_info')->field('name,click_count')
            ->where($map)
            ->order('click_count desc')
            ->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    private function getSendTop(){
        // 卡券发送量最高的10个活动
        $map['node_id'] = $this->nodeId;
        if($this->year){
            $map['add_time'][] = array('EGT',$this->year.'0000000000'); 
            $map['add_time'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M('tmarketing_info')->field('name,send_count')
            ->where($map)
            ->order('send_count desc')
            ->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    private function getMemberTop(){
        // 获得粉丝数最高的10个活动
        $map['a.node_id']  = $this->nodeId;
        $map['a.batch_id'] = array('NEQ',0);
        if($this->year){
            $map['a.add_time'][] = array('EGT',$this->year.'0000000000'); 
            $map['a.add_time'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M()->table('tmember_info a')
            ->field('b.name,count(*) as counts')
            ->join('tmarketing_info b on a.batch_id=b.id')
            ->where($map)
            ->group('a.batch_id')->order('counts desc')
            ->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    private function getCjTop(){
        $map['node_id'] = $this->nodeId;
        if($this->year){
            $map['add_time'][] = array('EGT',$this->year.'0000000000'); 
            $map['add_time'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M('tmarketing_info')->field('name,cj_count')
                ->where($map)
                ->order('cj_count desc')->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    private function getRecentlyTop(){
        $map['node_id'] = $this->nodeId;
        if($this->year){
            $map['add_time'][] = array('EGT',$this->year.'0000000000'); 
            $map['add_time'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M('tmarketing_info')->field('name,click_count')
                ->where($map)
                ->order('id desc')->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    private function getVisitChannelTop(){
        // 访问量最高的10个渠道
        $map['node_id'] = $this->nodeId;
        $map['type']    = array('in','1,2,3');
        if($this->year){
            $map['add_time'][] = array('EGT',$this->year.'0000000000'); 
            $map['add_time'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M('tchannel')->field('name,click_count')
            ->where($map)
            ->order('click_count desc')
            ->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    private function getSendChannelTop(){
        // 卡券发送量最高的10个渠道
        $map['node_id'] = $this->nodeId;
        $map['type']    = array('in','1,2,3');
        if($this->year){
            $map['add_time'][] = array('EGT',$this->year.'0000000000'); 
            $map['add_time'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M('tchannel')->field('name,send_count')
            ->where($map)
            ->order('send_count desc')
            ->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    private function getMemberChannelTop(){
        // 获得粉丝数最高的10个渠道
        $map['a.node_id']    = $this->nodeId;
        $map['a.channel_id'] = array('NEQ',0);
        $map['b.type']       = array('in','1,2,3');
        if($this->year){
            $map['a.add_time'][] = array('EGT',$this->year.'0000000000'); 
            $map['a.add_time'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M()->table('tmember_info a')
            ->field('b.name,count(*) as counts')
            ->join('tchannel b on a.channel_id=b.id')
            ->where($map)
            ->group('a.channel_id')->order('counts desc')
            ->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    private function getRecentlyChannelTop(){
        $map['a.node_id']    = $this->nodeId;
        $map['a.channel_id'] = array('NEQ',0);
        $map['b.type']       = array('in','1,2,3');
        if($this->year){
            $map['a.add_time'][] = array('EGT',$this->year.'0000000000'); 
            $map['a.add_time'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M()->table('tbatch_channel a')
                ->field('b.name,b.click_count')
                ->join('tchannel b on a.channel_id=b.id')
                ->where($map)
                ->order('a.add_time desc')
                ->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    private function getVisitCardTop(){
        // 访问量最高的10个渠道
        $map['a.node_id']  = $this->nodeId;
        $map['a.batch_no'] = array('NEQ','');
        $map['a.goods_id'] = array('NEQ','');
        if($this->year){
            $map['a.trans_date'][] = array('EGT',$this->year.'0000000000'); 
            $map['a.trans_date'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M()->table('tpos_day_count a')
                ->field('c.goods_name,sum(a.verify_num) as sums')
                ->join('tgoods_info c on a.goods_id=c.goods_id')
                ->where($map)->group('a.goods_id')
                ->order('sums desc')->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    private function getSendCardTop(){
        // 卡券发送量最高的10个渠道
        $map['a.node_id']  = $this->nodeId;
        $map['a.batch_no'] = array('NEQ','');
        $map['a.goods_id'] = array('NEQ','');
        if($this->year){
            $map['a.trans_date'][] = array('EGT',$this->year.'0000000000'); 
            $map['a.trans_date'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M()->table('tpos_day_count a')
                ->field('c.goods_name,sum(a.send_num) as sums')
                ->join('tgoods_info c on a.goods_id=c.goods_id')
                ->where($map)->group('a.goods_id')
                ->order('sums desc')->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    private function getBestCardTop(){
        // 最好的10个卡券
        $map['a.node_id']  = $this->nodeId;
        $map['a.batch_no'] = array('NEQ','');
        $map['a.goods_id'] = array('NEQ','');
        if($this->year){
            $map['a.trans_date'][] = array('EGT',$this->year.'0000000000'); 
            $map['a.trans_date'][] = array('ELT',$this->year.'1231235959'); 
        }
        $result = M()->table('tpos_day_count a')
                ->field('c.goods_name,(sum(a.send_num) + sum(a.verify_num))as sums ')
                ->join('tgoods_info c on a.goods_id=c.goods_id')
                ->where($map)->group('a.goods_id')
                ->order('sums desc')->limit(10)->select();
        $this->clickSum = self::getDataForJs($result);
    }
    // 将数组变成页面js需要的饼图/柱状图格式
    private function getDataForJs($infos){
        $pieRetArr = array(); 
        $columnRetArr = array();
        if (!empty($infos)) {
            $categoriesArr = $yArr = array();
            foreach ($infos as $key => $info) {
                $values = array_values($info);
                $pieStr = '[';
                foreach ($values as $k=>$v) {
                    if($k%2){
                        $values[$k] = intval($v);
                        $yArr[]     = '{y:'.$values[$k].',color:colors['.$key.']}';
                    }else{
                        $values[$k] = '\''.$v.'\'';
                        $categoriesArr[] = $values[$k];
                    }
                }
                $pieStr .= implode($values, ',');
                $pieStr .= ']';
                $pieRetArr[] = $pieStr;
            }
            $columnRetArr = array(
                'categories' => '['.implode($categoriesArr, ',').']',
                'y'          => '['.implode($yArr, ',').']'
                );
        }else{
            $columnRetArr = array(
                'categories' => '[]',
                'y'          => '[]'
                );
        }
        $clickSum = array(
            'pie'    => '['.implode($pieRetArr, ',').']',//饼图数据
            'column' => $columnRetArr, // 柱状图数据
            );
        return $clickSum;
    }
}
