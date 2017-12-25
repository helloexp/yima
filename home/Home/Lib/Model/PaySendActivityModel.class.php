<?php

/**
 * 付满送活动
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/8/26
 */
class PaySendActivityModel extends BaseModel {
    protected $tableName = '__NONE__';
    /**
     * 获得tvisiting_card的相关数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getVisitingCard($where, $field) {
        return $this->getData('tvisiting_card', $where, 
            BaseModel::SELECT_TYPE_FIELD, $field);
    }

    /**
     * 获得抽奖规则相关数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getCjRule($where, $field) {
        return $this->getData('tcj_rule', $where, BaseModel::SELECT_TYPE_ONE, 
            $field);
    }

    /**
     * 获得访问id
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return mixed|string
     */
    public static function getVisitId() {
        $visitId = cookie('visitId');
        if (empty($visitId)) {
            $clientIp = get_client_ip();
            $visitId = md5(
                $_SERVER['HTTP_USER_AGENT'] . ':' . $clientIp . ':' .
                     microtime(true));
            cookie('visitId', $visitId);
        }
        
        return $visitId;
    }

    public static function getCookieId() {
        $cookieId = cookie('cookieId');
        $clientIp = get_client_ip();
        if (empty($cookieId)) {
            $cookieId = md5(
                $_SERVER['HTTP_USER_AGENT'] . ':' . $clientIp . ':' .
                     microtime(true));
            cookie('cookieId', $cookieId, time() + 157680000); // 5年有效
        }
        return $cookieId;
    }

    /**
     * 构建插入的数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param string $node_id
     *
     * @param string $visit_page
     * @param string $visit_page_title
     * @param string $log_info
     * @param string $from_source
     *
     * @return array
     *
     */
    public function buildInsertData($node_id = '', $visit_page = '', 
        $visit_page_title = '', $log_info = '', $from_source = '') {
        if (empty($node_id)) {
            $node_id = I('request.node_id', '');
            if (empty($node_id)) {
                $node_id = session('userSessInfo.node_id');
            }
        }
        
        if (! empty($node_id)) {
            $where = array(
                'node_id' => $node_id);
            $nodeInfo = $this->getNodeInfo($where);
            $node_name = isset($nodeInfo['node_name']) ? $nodeInfo['node_name'] : '';
            $client_id = isset($nodeInfo['client_id']) ? $nodeInfo['client_id'] : '';
        } else {
            $node_id = 0;
            $node_name = '';
            $client_id = '';
        }
        $cookie_id = $this->getCookieId();
        $visit_id = $this->getVisitId();
        
        if (empty($visit_page)) {
            $visit_page = I('request.visit_page', '');
        }
        if (empty($visit_page_title)) {
            $visit_page_title = I('request.visit_page_title', '');
        }
        if (empty($log_info)) {
            $log_info = I('request.log_info', '');
        }
        if (empty($from_source)) {
            $from_source = I('server.HTTP_REFERER', '');
        }
        $visit_page = htmlspecialchars_decode($visit_page);
        $visit_page = strip_tags($visit_page);
        $from_source = htmlspecialchars_decode($from_source);
        $from_source = strip_tags($from_source);
        
        $insertData = array(
            'node_id' => $node_id, 
            'node_name' => $node_name, 
            'client_id' => $client_id, 
            'visit_id' => $visit_id, 
            'cookie_id' => $cookie_id, 
            'visit_page' => $visit_page, 
            'visit_page_title' => $visit_page_title, 
            'from_source' => $from_source, 
            'log_info' => $log_info, 
            'add_time' => date('YmdHis', microtime(true)), 
            'client_ip' => get_client_ip());
        return $insertData;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @return mixed
     */
    public function getCount($where) {
        return M('tvisit_log')->where($where)->count();
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @return mixed
     */
    public function getList($where, $orderBy) {
        $return = M('tvisit_log')->where($where)
            ->order($orderBy)
            ->select();
        return $return;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $where
     * @return mixed
     */
    public function getField($where, $orderBy) {
        $return = M('tvisit_log')->where($where)
            ->order($orderBy)
            ->field('log_id')
            ->select();
        return $return;
    }
}