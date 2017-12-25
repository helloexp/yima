<?php

/**
 * Class VisitLogAction
 */
class VisitLogAction extends Action {

    /**
     *
     * @var VisitLogModel
     */
    private $VisitLogModel;

    public function _initialize() {
        $this->VisitLogModel = D('VisitLog');
    }

    /**
     * 记录log
     *
     * @author Jeff Liu<liuwy@imageco.comc.cn>
     * @param string $node_id
     * @param string $visit_page
     * @param string $visit_page_title
     * @param string $log_info
     * @param string $from_source
     */
    public function log($node_id = '', $visit_page = '', $visit_page_title = '', 
        $log_info = '', $from_source = '') {
        // header('Access-Control-Allow-Origin:*');
        $this->VisitLogModel->log($node_id, $visit_page, $visit_page_title, 
            $log_info, $from_source);
        $callback = isset($_GET['callback']) ? $_GET['callback'] : '';
        echo $callback . '("success");';
        exit();
    }
}