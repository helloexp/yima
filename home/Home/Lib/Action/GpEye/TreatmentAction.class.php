<?

class TreatmentAction extends GpBaseAction
{


    private $recordModel = null;


    public function _initialize()
    {
        parent::_initialize();

        import("@.Model.GpTreatmentRecordModel");

        $this->recordModel = new GpTreatmentRecordModel();

    }


    public function index()
    {
        $treatprocess = [0 => '体验期', 1 => '恢复期', 2 => '保养期'];
        $techList     = $this->recordModel->getTechnicanList();
        $time = date('Y-m-d H:i:s');
        $this->assign('techlist', $techList);
        $this->assign('treatprocess', $treatprocess);
        $this->assign('time',$time);
        $this->assign('eyesightArr', C('eyesight_arr'));
        $this->assign('processArr', C('process_arr'));
        $this->display('Treatment/index');
    }


    public function getInfo()
    {

        $name = trim(I("name", ''));
        $type = I("type", '');
        $map  = [];
        if ($name != '') {
            $map['name'] = ['like', "%{$name}%"];
        }

        if ($type != '') {
            $map['type'] = $type;
        }

        $list = $this->recordModel->getCustomerList($map);
        $list = ['list' => $list];
        if ( ! $list) {
            $list = [];
        }
        $this->success($list, null, true);
    }


    /**
     * 获取客户详细信息
     */
    public function customerInfo()
    {
        $id  = I('id');
        $map = [];
        if ($id) {
            $map['a.id'] = $id;
        }
        $info = $this->recordModel->getCustomerBaseInfo($map);

        $basejson = json_decode($info['customer_info'], true);

        $baseInfo = [
            'id'                => $info['id'],
            'type'              => $info['type'],
            'name'              => $info['name'],
            'sex'               => $info['sex'],
            'age'               => $info['age'],
            'school'            => $info['school'],
            'age'               => $info['age'],
            'school'            => $info['school'],
            'classes'           => $info['classes'],
            'address'           => $info['address'],
            'storename'         => $info['store_short_name'],
            'address'           => $info['address'],
            'techname'          => $info['techname'],
            'address'           => $info['address'],
            'come_time'         => $info['come_time'],
            'level'             => $info['visual_level'],
            'treatment_process' => $info['treatment_process'],
            'process_txt'       => $info['process_txt'],
            'ext_info'          => $info['customer_info'],
            'xieshi'            => $info['xieshi'],
            'source'            => $info['source'],
            'household'         => $info['household_reg'],
            'thistechid'        => $info['technician_id']
        ];

        $map       = [
            'd.customer_id' => $id
        ];
        $treatlist = (array)$this->recordModel->getTreatmentInfo($map);
        $return    = ['baseInfo' => $baseInfo, 'treatList' => $treatlist];
        $this->success($return, null, true);
    }


    /**
     * 保存治疗记录
     */
    public function saveRecord()
    {
        $cid = I('cid');
        $map = [];
        if ($cid) {
            $map['a.id'] = $cid;
        }
        $cinfo = $this->recordModel->getCustomerBaseInfo($map);
        if ( ! $cinfo) {
            $this->error('error!customer not found!');
        }

        //变更治疗进程
        $treatprocess = I('treatprocess', 0, 'intval');
//        if ($treatprocess < $cinfo['treatment_process']) {
//            $this->error('治疗进程有误！');
//        }

        M()->startTrans();
        if($treatprocess != $cinfo['treatment_process'])
        {$result = $this->recordModel->chgCustomProcess($cid, $treatprocess);
        if ($result === false) {
            M()->rollback();
            $this->error('error!try agin');
        }}



        $flag = I('flag', 0, 'intval');
        $eyes = ['eye_degree' => ['-', '-', '-', '-', '-', '-']];
        if ($flag == 1) {
            $nlefteye  = I('nlefteye');
            $nrighteye = I('nrighteye');
            $neyes     = I('neyes');
            $clefteye  = I('clefteye');
            $crighteye = I('crighteye');
            $ceyes     = I('ceyes');
            $eyes      = ['eye_degree' => [$nlefteye, $nrighteye, $neyes, $clefteye, $crighteye, $ceyes]];
        }

        $data = [
            'merchant_id'       => $cinfo['merchant_id'],
            'customer_id'       => I('cid'),
            'technician_id'     => I('techid'),
            'memo'              => I('treatmemo'),
            'treatment_process' => $treatprocess,
            'vision_info'       => $eyes,
            'treatment_time'    => I('treattime', '', 'date_clean'),
            'treatment_num'     =>date('YmdHis').mt_rand(001,999),
            'user_id'           => $this->userId
        ];
        $data['memo']=str_replace(array("\r\n", "\r", "\n"), "",$data['memo']);
        $recordId = $this->recordModel->addTreatmentRecord($data);

        if ($recordId === false) {
            M()->rollback();
            $this->error('error!try agin');
        }
        M()->commit();
        $techname=M('tfb_gp_treatment_record a')->join('tfb_gp_technician b on a.technician_id=b.id')->where(array('a.id'=>$recordId))->getField('b.name');
        $num=M('tfb_gp_treatment_record')->where(array('customer_id'=>$cid))->count();
        $openids=D('GpTreatmentRecord')->getopenid($map);
        if($openids)
        { $wx_send = D('WeiXinSend', 'Service');
            $wx_send->init($this->node_id);
            $url = U("GpEye/EyeWap/customerFeedback",
                array(
                    "k" =>$num,"id"=>$recordId),false,false,true);
            $data = array(

                "keyword1" => array(
                    'value' => '葆瞳视力恢复',
                    'color' => '#173177'),
                "keyword2" => array(
                    'value' => I('treattime'),
                    'color' => '#173177'));


            foreach($openids as $k=>$v){
            $wx_send->templateSend(
                $v['openid'], $this->nodeId, 4, $url, $data);}

//        $map = [
//            'd.id' => $recordId,
//        ];
        //$treatlist = $this->recordModel->getTreatmentInfo($map);
        $this->success('success', null, true);

    }

}
}



