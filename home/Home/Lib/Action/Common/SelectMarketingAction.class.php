<?php

/**
 * 营销活动选择 2014年9月1日14:13:46 @kk
 */
class SelectMarketingAction extends BaseAction {

    public $menu = array();

    public $map_string = ' 1=1 ';

    public $other_field = '';

    public $tpl_notice = '';

    public function _initialize() {
        parent::_initialize();
    }

    public function return_commission() {
        $this->menu = array(
            '常见营销' => array(
                '2' => '抽奖', 
                '3' => '市场调研', 
                '10' => '有奖问答', 
                '20' => '投票'), 
            '商品销售活动' => array(
                '26' => '闪购', 
                '27' => '码上买'));
        
        // 非商品销售的营销活动必须要有奖品才能做全民营销的活动
        $batch_type = $this->_get_batch_type();
        if ($batch_type != 26 && $batch_type != 27) {
            $this->other_field .= ', (select count(*) from tcj_batch b where b.batch_id = a.id) as prize_count';
            // $this->map_string .= ' and exists(select * from tcj_batch b where
            // b.batch_id = a.id) ';
        }
        
        $this->tpl_notice = '注意：未配置抽奖的活动无法启用全民营销';
        $this->index();
    }

    public function _get_batch_type() {
        static $batch_type = null;
        if ($batch_type != '')
            return $batch_type;
        
        $batch_type = I('batch_type', '', 'intval');
        if ($batch_type == '') {
            foreach ($this->menu as $v) {
                foreach ($v as $k => $vv) {
                    $batch_type = $k;
                    break (2);
                }
            }
        }
        return $batch_type;
    }

    public function index() {
        $batch_type = $this->_get_batch_type();
        
        $batch_type_name = C('BATCH_TYPE_NAME.' . $batch_type);
        
        $map['a.node_id'] = $this->nodeId;
        $map['a.batch_type'] = $batch_type;
        $map['_string'] .= $this->map_string;
        
        $totalNum = M()->table('tmarketing_info a')
            ->where($map)
            ->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 10);
        $Page->setConfig('theme', 
            '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
        $pageShow = $Page->show();
        $queryData = M()->table('tmarketing_info a')
            ->field(
            "a.id batch_id, a.name, a.start_time, a.end_time" .
                 $this->other_field)
            ->where($map)
            ->order("a.id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $this->assign('page', $pageShow);
        $this->assign('queryList', $queryData);
        $this->assign('menu_list', $this->menu);
        $this->assign('batch_type', $batch_type);
        $this->assign('batch_type_name', $batch_type_name);
        $this->assign('notice', $this->tpl_notice);
        $this->display('index');
    }
}
