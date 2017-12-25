<?php

/**
 * 列表模板 更新时间 2015/2/27 11:16
 */
class ListBatchAction extends MyBaseAction {

    public function index() {
        if ($this->batch_type != '8')
            $this->error('错误活动类型！');
        
        $id = $this->id;
        $batch_id = $this->batch_id;
        
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 活动
        $row = D('ListBatch')->getMarketingInfo($batch_id);
        if (! $row) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '活动不存在！', 
                    'errorSoftTxt' => '您的访问地址出错啦~'));
        }
        $page = I('page');
        $page = empty($page) ? 1 : (int) $page;
        $defaultNav = $this->getDefaultNav($row['nav_1'], $row['nav_2']);
        $sideNav = I('side_nav', $defaultNav);
        $defaultKeyword = json_encode(
            array(
                '', 
                ''));
        $keyword = I('keyword', $defaultKeyword, '');
        session('activity_map' . $batch_id, 
            array(
                'side_nav' => $sideNav, 
                'keyword' => $keyword));
        $list = D('ListBatch')->getList($row['id'], $page);
        $next_url = U('ListBatch/ajaxGetPage', 
            array(
                'id' => $id, 
                'page' => $page + 1));
        $this->assign('id', $id);
        $this->assign('next_url', $next_url);
        $this->assign('side_nav', $sideNav);
        $this->assign('keyword', $keyword);
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('row', $row);
        $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig();
        $shareArr = array(
            'config' => $wx_share_config, 
            'link' => U('index', array(
                'id' => $this->id), '', '', TRUE), 
            'title' => $row['name'], 
            'desc' => $row['share_descript'], 
            'imgUrl' => get_upload_url($row['share_pic']));
        $this->assign('shareData', $shareArr);
        $this->display(); // 输出模板
    }

    public function ajaxGetPage() {
        $mId = $this->batch_id;
        $page = I('page');
        $list = D('ListBatch')->getList($mId, $page);
        $listType = D('ListBatch')->getListTypeById($mId);
        $row['list_type'] = $listType;
        $this->assign('list', $list);
        $this->assign('row', $row);
        $html = $this->fetch('page');
        echo $html;
        exit();
    }

    private function getDefaultNav($nav1, $nav2) {
        $navKey = '';
        if ($nav1) {
            $navKey = 1;
        }
        if ($nav2) {
            $navKey = 2;
        }
        if ($nav1 && $nav2) {
            $navKey = 1;
        }
        return $navKey;
    }
}