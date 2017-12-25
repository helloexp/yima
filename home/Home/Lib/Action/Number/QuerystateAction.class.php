<?php

/**
 * 百度直达号 状态查询
 */
class QuerystateAction extends Action {

    const NUMBER_STATUS = 1;

    private $getStatusUrl = "https://openapi.baidu.com/rest/2.0/devapi/v1/lightapp/query/status/get";

    public $token;

    public function _initialize() {
    /**
     * 以下获取百度token的账号（生产、测试环境基于同一个帐号），为了不影响生产环境，测试完成，关闭该功能 $newgettoken =
     * D('Getbaidutoken','Service'); $this->token=$newgettoken->gettoken();
     */
    }

    public function jquery() {
        // echo '1';
        $model = M('tbd_wail');
        $map = array(
            'status' => self::NUMBER_STATUS, 
            'add_time' => array(
                'exp', 
                "is not null"), 
            'app_id' => array(
                'exp', 
                "is not null"));
        $list = $model->where($map)->select();
        if ($list) {
            foreach ($list as $v) {
                $url = $this->getStatusUrl . "?access_token=" . $this->token .
                     "&app_id=" . $v['app_id'];
                $res = $this->send($url);
                dump($res);
                echo "<br/>";
                if ($res['status'] != '' && $res['msg'] != '') {
                    if ($res['status'] == '4') {
                        $outcome = $model->where(
                            array(
                                'id' => $v['id']))->save(
                            array(
                                'status' => '2'));
                    } else if ($res['status'] == '3' || $res['status'] == '7' ||
                         $res['status'] == '9') {
                        $outcome = $model->where(
                            array(
                                'id' => $v['id']))->save(
                            array(
                                'status' => '3'));
                        
                        $this->send_news($v['node_id']);
                    }
                }
                @sleep(1);
            }
        }
    }

    /**
     * 描述：下线关键词 说明：此方法是临时为旺财商户下线一个直达号所用，不可随意调用
     */
    public function bxiaxian() {
        $url = "https://openapi.baidu.com/rest/2.0/devapi/v1/lightapp/agent/offline";
        $data = array(
            'access_token' => $this->token, 
            'offline_app_id' => '4775182');
        // 'offline_app_id'=>'955556655',
        
        $res = $this->send($url, 1, $data);
        dump($res);
    }

    /**
     * 描述：修改关键词 说明：此方法是临时为旺财商户下线一个直达号所用，不可随意调用
     */
    public function bwcxian() {
        $url = "https://openapi.baidu.com/rest/2.0/devapi/v1/lightapp/agent/modify/queryinfo";
        $data = array(
            'access_token' => $this->token, 
            'modify_app_id' => '4775182', 
            'query' => '光伏云');
        // 'offline_app_id'=>'955556655',
        
        $res = $this->send($url, 1, $data);
        dump($res);
    }

    public function send($url, $type = 0, $data = '') {
        $url = urldecode($url);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, $type);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // https
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // https
        $data = curl_exec($curl);
        curl_close($curl);
        
        Log::write("百度直达号请求：" . $url);
        
        $arr = @json_decode($data, true);
        Log::write("百度直达号返回：" . print_r($arr, true));
        
        return $arr;
    }

    public function add_news() {
        $data = array(
            'title' => "直达号审请未通过审核！！！", 
            'content' => "<pre>尊敬的用户，您提交的直达号关键字申请<b>未通过审核</b>。
		 <br/>
          可能由于以下原因造成：
             1、页面不美观
             2、内容不丰富
             3、微官网中出现无法点击的按钮，或无法打开的链接
             4、关键字属于品类名词
             5、微官网内容出现违反国家相关规定的内容

          请及时调整您的微官网或旺财小店，并重新提交申请。谢谢！</pre>", 
            'add_time' => date('YmdHis'), 
            'msg_type' => '0');
        
        $add_ = M("tmessage_news")->add($data);
        
        if ($add_) {
            
            return $add_;
        } else {
            return false;
        }
    }

    public function send_news($node_id) {
        $sql = "select id from tmessage_news where title like '%直达号审请未通过审核%' ORDER BY id DESC LIMIT 1";
        $news_ = M()->query($sql);
        $news_id = $news_['0']['id'];
        
        if ($news_id == '') {
            $news_id = $this->add_news();
        }
        
        $data = array(
            "message_id" => $news_id, 
            "node_id" => $node_id, 
            "send_status" => '0', 
            "status" => '0', 
            "add_time" => date('YmdHis'));
        
        $send_ = M("tmessage_recored")->add($data);
    }
}