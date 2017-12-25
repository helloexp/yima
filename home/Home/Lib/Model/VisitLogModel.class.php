<?php

/**
 * visit log model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/8/04
 */
class VisitLogModel extends BaseModel {
    protected $tableName = 'tvisit_log'; // 默认对应数据库名称

    /**
     * 记录log
     *
     * @author Jeff Liu<liuwy@imageco.comc.cn>
     * @param string $node_id
     * @param string $visit_page
     * @param string $visit_page_title
     * @param string $log_info
     * @param string $from_source
     *
     * @return bool
     */
    public function log($node_id = '', $visit_page = '', $visit_page_title = '', 
        $log_info = '', $from_source = '') {
        $insertData = $this->buildInsertData($node_id, $visit_page, 
            $visit_page_title, $log_info, $from_source);
        return $this->addLog($insertData);
    }

    public function logByAction($visitPage, $title, $logInfo) {
        return $this->log('', $visitPage, $title, $logInfo, '');
    }

    /**
     * 添加新日志
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param array $insertData 插入数据
     * @return bool
     */
    public static function addLog($insertData) {
        $return = M('tvisit_log')->add($insertData);
        return $return;
    }

    /**
     *
     * @param $where
     * @return mixed
     */
    public function getNodeInfo($where) {
        return $this->getData('tnode_info', $where, BaseModel::SELECT_TYPE_ONE);
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
     * @param string $nodeId 机构号
     * @param string $visit_page 访问页面url
     * @param string $visitPageTitle 访问页面title
     * @param string $logInfo log信息
     * @param string $fromSource 来源页
     * @return array
     *
     */
    public function buildInsertData($nodeId = '', $visit_page = '', 
        $visitPageTitle = '', $logInfo = '', $fromSource = '') {
        if (empty($nodeId)) {
            $nodeId = I('request.node_id', '');
            if (empty($nodeId)) {
                $nodeId = session('userSessInfo.node_id');
            }
        }
        
        if (! empty($nodeId)) {
            $where = array(
                'node_id' => $nodeId);
            $nodeInfo = $this->getNodeInfo($where);
            $nodeName = isset($nodeInfo['node_name']) ? $nodeInfo['node_name'] : '';
            $clientId = isset($nodeInfo['client_id']) ? $nodeInfo['client_id'] : '';
        } else {
            $nodeId = 0;
            $nodeName = '';
            $clientId = '';
        }
        $cookieId = $this->getCookieId();
        $visitId = $this->getVisitId();
        
        if (empty($visit_page)) {
            $visit_page = I('request.visit_page', '');
        }
        if (empty($visitPageTitle)) {
            $visitPageTitle = I('request.visit_page_title', '');
        }
        if (empty($logInfo)) {
            $logInfo = I('request.log_info', '');
        }
        if (empty($fromSource)) {
            $fromSource = I('server.HTTP_REFERER', '');
        }
        $visit_page = htmlspecialchars_decode($visit_page);
        $visit_page = strip_tags($visit_page);
        $fromSource = htmlspecialchars_decode($fromSource);
        $fromSource = strip_tags($fromSource);
        
        $insertData = array(
            'node_id' => $nodeId, 
            'node_name' => $nodeName, 
            'client_id' => $clientId, 
            'visit_id' => $visitId, 
            'cookie_id' => $cookieId, 
            'visit_page' => $visit_page, 
            'visit_page_title' => $visitPageTitle, 
            'from_source' => $fromSource, 
            'log_info' => $logInfo, 
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
    public function getFieldByCondition($where, $orderBy) {
        $return = M('tvisit_log')->where($where)
            ->order($orderBy)
            ->field('log_id')
            ->select();
        return $return;
    }
}