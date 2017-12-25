<?php 
/**
 * 多乐互动数据中心
 */
class DataCenterAction extends MarketBaseAction{

	public function _initialize() {
        parent::_initialize();
    }

    /**
     * [beforeCheckAuth 提前校验权限]
     */
    public function beforeCheckAuth(){
    	// 跳过系统权限校验
    	$this->_authAccessMap = '*';
        $this->assign('amember_counts', self::getMembers());
        $this->assign('asend_num',      self::getCardPv(true));
        $this->assign('averify_num',    self::getCardPv(false));
        $this->assign('ahd_count',      self::getBatchChart());
    }

	public function index(){
        $requestInfo = I('post.'); // 获取请求数据
        self::getMarketPvUv($requestInfo);
    	$this->display();
    }
    public function Top5(){
        $year = I('post.year');
        $type = I('post.type','1');
        $clickSum = [];
        switch ($type) {
            case '1':
                $clickSum = self::getVisitTop($year);
                break;
            case '2':
                $clickSum = self::getSendTop($year);
                break;
            case '3':
                $clickSum = self::getMemberTop($year);
                break;
            case '4':
                $clickSum = self::getCjTop($year);
                break;
            default:
                die('参数错误!');
                break;
        }
        $this->assign('clickSum',$clickSum);
        $this->assign('year',$year);
        $this->assign('type',$type);
        $this->display();
    }
    public function Lateral(){
        $requestInfo = I('post.'); // 获取请求数据
        self::getBTraceStat($requestInfo,I('get.down'));
        $this->display();
    }
    public function comrank(){
        $requestInfo = I('request.'); // 获取请求数据
        self::getMarketTop($requestInfo,I('get.down'));
        $this->display();
    }
    /**************逻辑处理*****************/
    // 获取粉丝总量
    private function getMembers(){
        return M('tmember_info')->where(['node_id' => $this->nodeId])->count();
    }
    // 获取卡券发放数/验证数
    private function getCardPv($flag){
        $field = 'SUM(send_num) as send_num,sum(verify_num) as verify_num';
        $result = M('tpos_day_count')->field($field)->where(['node_id'=>$this->nodeId])->select();
        if(!empty($result)){
            return $flag ? $result[0]['send_num'] : $result[0]['verify_num'];
        }
        return 0;
    }
    // 营销互动人次
    private function getBatchChart(){
        return M('tmarketing_info')->where(['node_id' => $this->nodeId])->sum('cj_count');
    }
    // 获取PV/UV
    private function getMarketPvUv($requestInfo){
        $map = array();
        $map['node_id'] = $this->nodeId;
        // 按日、周、月查询
        $dayType = get_val($requestInfo,'day_type');
        $dayGroup = $dayType == '2' ? "%Y-%m" : "%Y-%m-%d";
        // 活动类型
        $batchType = get_val($requestInfo,'batch_type');
        $batchTypeArr = self::getBatchType();
        if($batchType){
            $map['batch_type'] =  $batchType;
        }else{
            $map['batch_type'] =  array('in',implode(array_keys($batchTypeArr), ','));
        }
        // 时间条件
        $beginTime = get_val($requestInfo,'begin_time');
        $endTime   = get_val($requestInfo,'end_time');
        $beginTime = $beginTime ? $beginTime : date('Ymd',strtotime("-30 days"));
        $endTime   = $endTime ? $endTime : date('Ymd');
        $map['day'][] = array('EGT',$beginTime);
        $map['day'][] = array('ELT',$endTime);

        $field = "SUM(click_count) AS  pv,SUM(uv_count) AS uv,DATE_FORMAT(day,'{$dayGroup}') AS durhour";
        $group = "DATE_FORMAT(day,'{$dayGroup}')";
        $list  = M('tdaystat')->field($field)->where($map)->group($group)->order('id DESC')->select();

        $this->assign('list', $list);
        $this->assign('pvuv',self::dealRes($list));
        $this->assign('batch_type_arr', $batchTypeArr);
        $this->assign('day_type', $dayType);
        $this->assign('batch_type', $batchType);
        $this->assign('begin_time', $beginTime);
        $this->assign('end_time', $endTime);

    }
    private function getVisitTop($year){
        // 访问量最高的10个活动
        $map['node_id'] = $this->nodeId;
        if($year){
            $map['add_time'][] = array('EGT',$year.'0000000000');
            $map['add_time'][] = array('ELT',$year.'1231235959');
        }
        $result = M('tmarketing_info')->field('name,click_count')
            ->where($map)
            ->order('click_count desc')
            ->limit(10)->select();
        return self::getDataForJs($result);
    }
    private function getSendTop($year){
        // 卡券发送量最高的10个活动
        $map['node_id'] = $this->nodeId;
        if($year){
            $map['add_time'][] = array('EGT',$year.'0000000000');
            $map['add_time'][] = array('ELT',$year.'1231235959');
        }
        $result = M('tmarketing_info')->field('name,send_count')
            ->where($map)
            ->order('send_count desc')
            ->limit(10)->select();
        return self::getDataForJs($result);
    }
    private function getMemberTop($year){
        // 获得粉丝数最高的10个活动
        $map['a.node_id']  = $this->nodeId;
        $map['a.batch_id'] = array('NEQ',0);
        if($year){
            $map['a.add_time'][] = array('EGT',$year.'0000000000');
            $map['a.add_time'][] = array('ELT',$year.'1231235959');
        }
        $result = M()->table('tmember_info a')
            ->field('b.name,count(*) as counts')
            ->join('tmarketing_info b on a.batch_id=b.id')
            ->where($map)
            ->group('a.batch_id')->order('counts desc')
            ->limit(10)->select();
        return self::getDataForJs($result);
    }
    private function getCjTop($year){
        $map['node_id'] = $this->nodeId;
        if($year){
            $map['add_time'][] = array('EGT',$year.'0000000000');
            $map['add_time'][] = array('ELT',$year.'1231235959');
        }
        $result = M('tmarketing_info')->field('name,cj_count')
            ->where($map)
            ->order('cj_count desc')->limit(10)->select();
        return self::getDataForJs($result);
    }
    // 获取营销活动排名
    private function getMarketTop($requestInfo,$down){
        // 获取活动类型
        $batchType = get_val($requestInfo,'batchType');
        $batchTypeArr = self::getBatchType();
        $batchTypeStr = $batchType ? $batchType : implode(array_keys($batchTypeArr), ',');
        // 获取查询年份
        $year = get_val($requestInfo,'year');
        $betYear = $year ? $year.'0101000000 AND '.$year.'1231235959' : '20110101000000 AND 20161231235959';
        // 获取排序准则
        $sort = get_val($requestInfo,'sort','1');
        $orderby = 'click_count';
        switch ($sort) {
            case '3':
                $orderby = 'cj_count';
                break;
            case '4':
                $orderby = 'verify_count';
                break;
            default:
                break;
        }
        // 查询条件
        $map = "where a.node_id='".$this->nodeId."' and a.click_count !=0 and a.batch_type in (".$batchTypeStr.") AND (a.start_time BETWEEN ".$betYear." OR a.end_time BETWEEN ".$betYear.')';
        // 子查询
        $sonTable = "(SELECT
                        a.id
                    FROM
                        tmarketing_info a
                        {$map}
                    GROUP BY a.id) t";
        // 结果集数量
        $count = M()->query("SELECT COUNT(*) AS cnt FROM {$sonTable} LIMIT 1")[0]['cnt'];
        import('ORG.Util.Page'); // 导入分页类
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        // 查询域
        $field = "a.click_count,
            a.cj_count,
            a.send_count AS verify_count,
            a.id,
            a.name,
            a.batch_type";
        $limit = $down ? "" : "LIMIT {$Page->firstRow},{$Page->listRows}";
        $sql = "SELECT
                    {$field}
                FROM
                    tmarketing_info a
                    {$map}
                GROUP BY
                    a.id
                ORDER BY
                    {$orderby} DESC
                {$limit}";
        $list = M()->query($sql);
        if(!empty($list)){
            foreach ($list as $k => $v) {
                $list[$k]['batch_type'] = C('BATCH_TYPE_NAME')[$v['batch_type']];
            }
        }
        if($down){
            $cols_arr = array(
                'name'         => '活动名称',
                'batch_type'   => '活动类型',
                'click_count'  => '访问量',
                'cj_count'     => '互动人次',
                'verify_count' => '转换人次'
            );
            parent::csv_lead("活动综合排名".$year,$cols_arr,$list);
        }

        $this->assign('batchTypeArr', $batchTypeArr);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('list', $list);
        $this->assign('year', $year);
        $this->assign('batchType', $batchType);
        $this->assign('sort', $sort);
    }
    private function getBTraceStat($requestInfo,$down){
        $map = array();
        $map['node_id'] = $this->nodeId;
        // 时间条件
        $beginTime = get_val($requestInfo,'begin_time');
        $endTime   = get_val($requestInfo,'end_time');
        $beginTime = $beginTime ? $beginTime : date('Ymd',strtotime("-90 days"));
        $endTime   = $endTime ? $endTime : date('Ymd');
        $map['trans_date'][] = array('EGT',$beginTime);
        $map['trans_date'][] = array('ELT',$endTime);
        // 查询字段
        $field = "SUM(pv) AS pv,SUM(uv) AS uv,concat(hours,':00-',hours,':59') AS durhour";
        $result = M('tbatch_trace_hour_stat')->field($field)
            ->where($map)->group('hours')->order('hours ASC')->select();
        if($down){
            $cols_arr = array(
                'durhour' => '小时',
                'pv'      => 'pv',
                'uv'      => 'uv',
            );
            parent::csv_lead("营销活动峰值",$cols_arr,$result);
        }
        $this->assign('begin_time',$beginTime);
        $this->assign('end_time',$endTime);
        $this->assign('day_type',get_val($requestInfo,'day_type','1'));
        $this->assign('list',$result);
        $this->assign('pvuv',self::dealRes($result));
    }
    private function dealRes($result){
        $pv_arr = array();
        $uv_arr = array();
        $sv_arr = array();
        $hr_arr = array();
        if(!empty($result)){
            foreach ($result as $v) {
                $hr_arr[] = "'" . $v['durhour'] . "'";
                $pv_arr[] = get_val($v,'pv','');
                $uv_arr[] = get_val($v,'uv','');
                $sv_arr[] = get_val($v,'sv','');
            }
        }
        return array(
            'pv' => '['.implode(',', $pv_arr).']',
            'uv' => '['.implode(',', $uv_arr).']',
            'sv' => '['.implode(',', $sv_arr).']',
            'hr' => '['.implode(',', $hr_arr).']',
        );
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














