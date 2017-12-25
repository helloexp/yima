<?php

class wapLoginAction {

    private $appId = '';
    private $key = '';
            
    public function __construct(){
        $this->appId =  C('CMPAY.appId');   //生产环境
        $this->key = C('CMPAY.sendKey');   //生产环境
    }
    public function index() {
        $pageId = I('pageId');
        if(empty($pageId)){
            echo "view error";
            die;
        }
        $postStr = file_get_contents('php://input');
        log_write('http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"]);
      
        log_write("CREDTENTIAL:" . $_REQUEST['CREDTENTIAL']);
        log_write("SIGN_DATA:" . $_REQUEST['SIGN_DATA']);
        
        $phone = $this->getPhone($_REQUEST['CREDTENTIAL'],  $_REQUEST['SIGN_DATA'], $_REQUEST['SIGN_TYPE']);
        if ($phone) {
            log_write("getPhone:" . $phone);
            //登录信息赋值
            session("groupPhone", $phone);
            session('cc_node_id', C('CMPAY.nodeId'));
            $userId = addMemberByO2o($phone, C('CMPAY.nodeId'));
            session('store_mem_id' . $this->node_id, array('user_id' => $userId));
            
            header('Location: ' .
                     "http://test.wangcaio2o.com/index.php?g=Label&m=PageView&a=index&page_id={$pageId}");
        } else {
            log_write("checkSign error:");
            echo "checkSign error";
        }
    }

    private function getSendSign($requestData) {
        return base64_encode(
            pack('H*', hash_hmac('SHA1', $requestData, $this->key)));
    }

    private function getPhone($CREDTENTIAL, $SIGN_DATA, $SIGN_TYPE) {
        $ssoArr = explode(",", $CREDTENTIAL);
        log_write("ssoArr:" . print_r($ssoArr, true));
        $getSignKeyUrl =  C('CMPAY.getSignKeyUrl'); 
        $requestData = "<HEAD><TXNCD>2208000</TXNCD><MBLNO></MBLNO><SESSIONID></SESSIONID><PLAT>99</PLAT><UA>default</UA><VERSION>default</VERSION><PLUGINVER></PLUGINVER><NETTYPE></NETTYPE><MCID>default</MCID><MAC>default</MAC><IMEI>default</IMEI><IMSI>default</IMSI><SOURCE>default</SOURCE><DEVID>" .
             $this->appId . "</DEVID><SERLNO>" . date('YmdHis') .
             rand(100000, 999999) .
             "</SERLNO></HEAD><BODY><CREDTENTIAL>$CREDTENTIAL</CREDTENTIAL><SIGN_DATA>$SIGN_DATA</SIGN_DATA><SIGN_TYPE>$SIGN_TYPE</SIGN_TYPE></BODY>";
        $msg = '<?xml version="1.0" encoding="UTF-8" ?><ROOT>' . $requestData .
             '<SIGNATURE>' . $this->getSendSign($requestData) .
             '</SIGNATURE></ROOT>';
        log_write("send to :" . $getSignKeyUrl);
        log_write("send data :" . $msg);
        $error = '';
        $result = httpPost($getSignKeyUrl, $msg, $error,
            array(
                'TIMEOUT' => 5));
        log_write("resp data :" . $result);
        /*
         * <?xml version="1.0"
         * encoding="UTF-8"?><ROOT><HEAD><RSPCD>000000</RSPCD><LOGID>CCLIMCA4MCA112000000095268</LOGID><SESSIONID/></HEAD><BODY><CREDENTIALKEY>c1&amp;(c1^}jl</CREDENTIALKEY></BODY></ROOT>
         */
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml] error');
        $xml = new Xml();
        $xml_arr = $xml->parse($result);
        $CREDENTIALKEY = "";
        if ($xml_arr['ROOT']['HEAD']['RSPCD'] === '000000') {
            $CREDENTIALKEY = $xml_arr['ROOT']['BODY']['MBL_NO'];
            return $CREDENTIALKEY;
        } else {
            log_write("parse xml error :" . $result);
            return false;
        }
    }
}
