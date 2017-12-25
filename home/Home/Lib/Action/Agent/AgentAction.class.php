<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2016/5/23
 * Time: 15:06
 */
class AgentAction extends BaseAction{
    public $_authAccessMap = '*';
    public function __construct()
    {
        parent::__construct();
        //从子商户切换回来的时候
        $agentInfo = session('agentInfo');
        if($agentInfo){
            session('userSessInfo',$agentInfo);
            session('agentInfo',null);
            $this->node_id = $agentInfo['node_id'];
        }

        $this-> setApi();
    }

    /**
     * 基本信息
     */
    public function index(){

//        $sslcertPath = realpath('./Home/Upload/'.$this->node_id.'/'.$_FILES['img']['name']);
//        $data['sslkey']  = '@'.$sslcertPath;
//        $data['fileName']  = $_FILES['img']['name'];


        /*$data = [
            'sslkey'     => '@'.realpath('./Home/Upload/_user.png')
//            'as'    => 'rrr'
        ];

        var_dump($data);

        $return = Api::post('agent/agent/add/merchants',$data);*/

        //概况页数据
//        $return = Api::get('api/Agent/account',['node_id'=>'00004488']);
//        $return = Api::get('api/Agent/achievement',['node_id'=>$this->node_id]);
//        Api::apiReturn($return);
//        var_dump($return);
    }
    /**
     * 商户信息
     */
    public function nodeAccount()
    {
        $data = [
            'node_id'   => $this->node_id,
            'chargeUrl' => C('YZ_RECHARGE_URL')."&node_id=".$this->nodeId."&name=".$this->userInfo['user_name']."&token=".$this->userInfo['token']
        ];
        $return = Api::get('agent/account',$data);
        Api::apiReturn($return);
    }

    /**
     * 代理商的业绩
     */
    public function achievement()
    {
        $return = Api::get('agent/achievement',['node_id'=>'00004488']);
        Api::apiReturn($return);
    }
    /*
     * 我的商户
     */
    public function myMerchants()
    {
        $return = Api::get('agent/agent/merchants',['node_id'=>'00026652']);
        //        $return = Api::get('agent/agent/merchants',['node_id'=>$this->node_id]);
        Api::apiReturn($return);
    }

    /**
     * 去签约商户
     */
    public function signNode()
    {
        $clien_id = I('get.clien_id','1345');
        $return = Api::get('agent/survey/nodeinfo/toclient',['clien_id'=>$clien_id]);

        Api::apiReturn($return);

    }

    /**
     * 开通业务
     */
    public function toOpenService(){
        //改变SESSION中的值为子商户的
        $nodeId = I('get.node_id','00004488');         //子商户的node_id
        $subMerchant = Api::get('agent/agent/sub/merchant',['node_id'=>$nodeId]);

        $userInfo = $this->userInfo;
        $data = [
            'user_id' => $subMerchant['user_id'],
            'node_id' => $nodeId,
            'user_name' => $subMerchant['user_name'],
            'name' => $subMerchant['user_name'],
            'add_time' => $subMerchant['add_time'],
            'node_short_name' => $subMerchant['node_short_name'],
            'last_time' => $subMerchant['last_time'],
            'token' => $userInfo['token']
        ];
        //子商户的
        session('userSessInfo',$data);
        //代理商的
        session('agentInfo',$userInfo);

        $return = [
            'code'      => 0,
            'data'      => [],
            'msg'       => ''
        ];
        Api::apiReturn(json_encode($return));

    }

    /**
     * 授权管理
     */
    public function authorize()
    {
        $child_node_id = I('get.node_id ','00004488');
        
        $data = [
                'node_id'       => $this->node_id,
                'child_node_id' => $child_node_id
        ];

        $return = Api::get('agent/agent/give/authority',$data);
        Api::apiReturn($return);
    }
    /**
     * 新增商户
     */
    public function addMerchantss()
    {
        $image = $_FILES;
        $postData = I('post.');
        $data = [
            'node_id'       =>'00004488',
            'clien_id'      => $postData['clien_id'],
            'node_name'     => $postData['node_name'],
        ];

        if($image && $postData['contract_desc']) {         //已签约的商户
            if ($_FILES['img']['error'] == 0) {

                $dirName = './Home/Upload/' . $this->node_id . '/'.date('Y').'/'.date('m').'/'.date('d').'/';
                if(!is_dir($dirName)){
                    mkdir($dirName,777,true);
                }
                move_uploaded_file(
                        $_FILES["img"]["tmp_name"],
                        $dirName. $_FILES["img"]["name"]
                );
                //老旺财那上传后的路径
                $sslcertPath      = realpath($dirName. $_FILES['img']['name']);
                $data['sslkey']   = '@' . $sslcertPath;
                $data['file_name'] = $_FILES['img']['name'];
                $data['contract_desc'] = $postData['contract_desc'];
                $data['child_node_id'] = $postData['child_node_id'];
            }
        }
        $return = Api::post('agent/agent/add/merchants',$data);
        Api::apiReturn($return);
    }

    /**
     * 获取当前商户下的子商户
     */
    public function getChildNode(){
        $return = Api::get('agent/agent/get/chile/node',['node_id','00026652']);
//        $return = Api::post('agent/agent/get/chile/node',['node_id',$this->node_id]);
        Api::apiReturn($return);
    }

    /**
     * 开通业务
     */
    public function openingService(){
        $this->display();
    }
    




}