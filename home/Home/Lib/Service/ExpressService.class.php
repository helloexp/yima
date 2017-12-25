<?php

class ExpressService {

    public $orderNum;

    public $nodeID;

    public $isExit;

    public $bookOrderId;

    public function index($orderNum, $nodeID, $isExit = 0, $expCom, $expNum, 
        $type = '0', $bookOrderId = '0') {
        $this->orderNum = $orderNum;
        $this->nodeID = $nodeID;
        $this->isExit = $isExit;
        $this->bookOrderId = $bookOrderId;
        
        $expressCountArray = F('express');
        $expNum = str_replace(' ', '', $expNum);
        if (empty($expressCountArray) ||
             date('Ymd') != $expressCountArray['date']) {
            $this->_createCacheFile();
            $result = $this->_kuaidi100Api($expCom, $expNum, $type);
        } elseif ($expressCountArray['kuaidi100']['count'] > 10) {
            if ($expressCountArray['kuaidi100']['count'] == 150) {
                $this->_sendmail();
            }
            $result = $this->_kuaidi100Api($expCom, $expNum, $type);
        } else {
            $result = $this->_kuaidi250Express($expCom, $expNum, $type);
        }
        $result = array(
            'error' => '0');
        return $result;
    }

    /**
     * 初始化缓存文件
     */
    private function _createCacheFile() {
        $initializationArray = array(
            'date' => date('Ymd'), 
            'kuaidi100' => array(
                'count' => 2000));
        
        F('express', $initializationArray);
    }

    private function _kuaidi100Api($expCom, $expNum, $type) {
        $htmlAPI = array(
            'shunfeng' => 'shunfeng', 
            'youzhengguonei' => 'youzhengguonei', 
            'ems' => 'ems', 
            'emsen' => 'emsen', 
            'youzhengguoji' => 'youzhengguoji', 
            'shentong' => 'shentong', 
            'shunfengen' => 'shunfengen', 
            'yuantong' => 'yuantong', 
            'yunda' => 'yunda', 
            'zhongtong' => 'zhongtong');
        if (in_array($expCom, $htmlAPI)) {
            $url = 'http://www.kuaidi100.com/query?id=1&type=' . $expCom .
                 '&postid=' . $expNum . '&valicode=&temp=0.07631751801818609';
        } else {
            $this->_saveApiTimes('kuaidi100');
            $systemParamModel = M('TsystemParam');
            $APIKey = $systemParamModel->where(
                array(
                    'param_name' => 'KUAIDI100_QUERY_KEY'))->getfield(
                'param_value');
            $url = 'http://api.kuaidi100.com/api?id=' . $APIKey .
                 '&valicode=io&com=' . $expCom . '&nu=' . $expNum .
                 '&show=0&muti=1&order=desc';
        }
        log_write($url);
        $content = httpPost($url, '', '', 
            array(
                'METHOD' => 'get'));
        $arr = json_decode($content, TRUE);
        if ($arr['state'] == '3') {
            $status = '1';
        } elseif ($arr['state'] == '2' || $arr['state'] == '4' ||
             $arr['state'] == '6') {
            $status = '2';
        } else {
            $status = '0';
        }
        $this->_insertDataToTable($arr['data'], $status, $content, $type);
        return $arr['data'];
    }

    private function _kuaidi250Express($expCom, $expNum, $type) {
        $url = 'http://kd250.com/api_blank.html?com=' . $expCom . '&nu=' .
             $expNum;
        $content = httpPost($url, '', '', 
            array(
                'METHOD' => 'get'));
        $date = '/<td class="row1">(.*)<\/td><td class="(status status-first|status )">&nbsp;<\/td>/';
        $location = '/<td>(.*)<\/td>/';
        if (preg_match('签收', $content)) {
            $status = '1';
        } else {
            $status = '0';
        }
        preg_match_all($date, $content, $dateMaches);
        preg_match_all($location, $content, $locationMatches);
        $result = array();
        foreach ($dateMaches[1] as $key => $val) {
            $result[$key]['time'] = $val;
            $result[$key]['context'] = $locationMatches[1][$key];
        }
        $this->_insertDataToTable($result, $status, 'zhuaqu', $type);
        return $result;
    }

    private function _saveApiTimes($apiName) {
        $initializationArray = F('express');
        $initializationArray[$apiName]['count'] = $initializationArray[$apiName]['count'] -
             1;
        F('express', $initializationArray);
    }

    private function _insertDataToTable($data, $tatus, $content, $type = '0') {
        $tableData = array();
        $tableData['express_content'] = json_encode($data);
        $tableData['status'] = $tatus;
        $tableData['check_time'] = date('YmdHi');
        $tableData['return_content'] = $content;
        $tableData['type'] = $type;
        
        $orderExpressInfoModel = M('torder_express_info');
        
        if ($type == '1' && $tatus == '1') {
            M('tonline_get_order')->where(
                array(
                    'req_seq' => $this->orderNum, 
                    'node_id' => $this->nodeID))->save(
                array(
                    'delivery_status' => '3'));
        }
        
        $expressInfoCondition = array(
            'order_id' => $this->orderNum, 
            'node_id' => $this->nodeID);
        if ($this->isExit == 0) {
            $tableData['node_id'] = $this->nodeID;
            $tableData['order_id'] = $this->orderNum;
            if ($this->bookOrderId != '0') {
                $tableData['book_order_id'] = $this->bookOrderId;
            }
            $orderExpressInfoModel->add($tableData);
        } else {
            if ($this->bookOrderId != '0') {
                $expressInfoCondition['book_order_id'] = $this->bookOrderId;
            }
            $orderExpressInfoModel->where($expressInfoCondition)->save(
                $tableData);
        }
    }

    private function _sendmail() {
        $mailContent = array();
        $mailContent['subject'] = '快递接口报警，请检查备用API接口没有错误';
        $mailContent['content'] = '快递100API接口次数剩余150次，请检查备用接口抓取数据是否正确！并更改间隔时间';
        $mailContent['email'] = 'liujl@imageco.com.cn';
        send_mail($mailContent);
        $mailContent['email'] = 'wangsong@imageco.com.cn';
        $result = send_mail($mailContent);
        $mailContent['email'] = 'duyf@imageco.com.cn';
        send_mail($mailContent);
    }
}
