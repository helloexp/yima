<?php

/*北京光平治疗记录model*/

class GpTreatmentRecordModel extends GpBaseModel
{

    protected $tableName = 'tfb_gp_treatment_record';
    protected $_map = [];
    protected $_map1=[];

    public function _initialize(){
        parent::_initialize();

        if ($this->limit) {
            $this->_map = array('merchant_id' => $this->merchant_id);
            if($this->new_role_id==202) {$this->_map1 = array('merchant_id' => $this->merchant_id);}
            if($this->new_role_id==203) {$this->_map1 = array('merchant_id' => $this->merchant_id,'user_id'=>$this->user_id);}
        }
    }

    /**
     * 查询客户治疗记录
     * @param $map
     *
     * @return mixed
     */
    public function myTreatmentRecord($map)
    {
        $mapcount=M()->table('tfb_gp_treatment_record tr')->join('tfb_gp_technician tt on tt.id=tr.technician_id')->join('tfb_gp_customer tc on tc.id=tr.customer_id')->join('tuser_info ti on ti.user_id=tr.user_id')->field('tr.*,tt.name,tc.treatment_process,ti.true_name')->where($map)->order('tr.treatment_time desc')->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();// 分页显示输出
        $record = M()->table('tfb_gp_treatment_record tr')->join('tfb_gp_technician tt on tt.id=tr.technician_id')->join('tfb_gp_customer tc on tc.id=tr.customer_id')->join('tuser_info ti on ti.user_id=tr.user_id')->field('tr.*,tt.name,tc.treatment_process,ti.true_name')->where($map)->order('tr.treatment_time desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        return array('record'=>$record,'page'=>$show);
    }


    public function getCustomerList($map)
    {
        $list = M()->table('tfb_gp_customer')->where(array_merge($this->_map,$map))->field("id,name,sex")->select();
        foreach ($list as &$info) {
            $info['sex'] = $info['sex'] == 0 ? '男' : '女';
        }

        return $list;

    }


    public function myTreatmentCount($map)
    {
        $count = M()->table('tfb_gp_treatment_record tr')->join('tfb_gp_technician tt on tt.id=tr.technician_id')->join('tfb_gp_customer tc on tc.id=tr.customer_id')->field('tr.*,tt.name,tc.treatment_process')->where($map)->count();

        return $count;
    }


    /**
     * 查询客户列表
     * @param $map
     *
     * @return mixed
     */
    public function getCustomerInfo($map)
    {
        if($this->limit){
            $map = array_merge($map, ['a.merchant_id' => $this->merchant_id]);
        }
        $list = M()->table('tfb_gp_customer a')->join('tfb_gp_merchant b on a.merchant_id=b.id')->join('tfb_gp_technician c on a.technician_id=c.id')->join('tfb_gp_treatment_record d on a.id=d.customer_id')->field("a.*,b.store_name,c.name")->where($map)->select();

        return $list;
    }


    /**
     * 查询客户基本信息
     * @param $map
     *
     * @return mixed
     */
    public function getCustomerBaseInfo($map)
    {
        if($this->limit){
            $map = array_merge($map, ['a.merchant_id' => $this->merchant_id]);
        }
        $info = M()->table('tfb_gp_customer a')->join('tfb_gp_merchant b on a.merchant_id=b.id')->join('tfb_gp_technician c on a.technician_id=c.id')->join('tfb_gp_customer_login d on a.mobile=d.mobile')->field("a.*,b.store_short_name,c.name techname,d.openid")->where($map)->find();
        if($info){
            $info['sex'] = $info['sex'] == 0 ? '男' : '女';
            $info['visual_level'] = C('visual_level_arr.'.$info['visual_level']);
            $info['process_txt'] = C('process_arr.'.$info['treatment_process']);
            $info['class_type'] = C('class_type_arr.'.$info['class_type']);
            $info['class_que'] = C('class_que_arr.'.$info['class_que']);
            $info['come_time'] = dateformat($info['come_time'], 'Y-m-d H:i:s');
            $info['customer_info'] = json_decode($info['customer_info'], true);
            if($info['other_source'])
            $info['customer_info']['other'] = json_decode($info['other_source'], true);
            else $info['customer_info']['other']=[];
            if($info['customer_info']['other']['du'])
                $info['xieshi']= C('du.'.$info['customer_info']['other']['du']);
            $info['customer_info']['class_type'] = C('class_type_arr.'.$info['customer_info']['class_type']);
            $info['customer_info']['class_que'] = C('class_que_arr.'.$info['customer_info']['class_que']);
            $info['household_reg']=M('tcity_code')->field('province,city')->where(array('path'=>$info['household_reg']))->find();
            if(in_array($info['household_reg']['province'],['北京','重庆','天津','上海']))
                $info['household_reg']['province']='';
            else if($info['household_reg']['province']=='新疆')
                $info['household_reg']['province']='新疆自治区';
            else $info['household_reg']['province'].='省';
                $info['source']= C('sources.'.$info['source']);
            if($info['source']=='其他')
                if($info['customer_info']['other']['so'])
            $info['source']=$info['customer_info']['other']['so'];
            $info['customer_info']['s_type'] = $info['customer_info']['s_type'];
            $eyesCondition=['远视','斜视','近视','散光','弱视'];
            foreach($info['customer_info']['s_type'] as $i=>$k) $info['customer_info']['s_type'][$i] =                             $eyesCondition[$k];
           

        }

        return $info;
    }


    /**
     * 查询可操作的技师
     * @return mixed
     */
    public function getTechnicanList()
    {
        $map['status']='0';
        $list = M()->table('tfb_gp_technician')->where(array_merge($this->_map1,$map))->getField('id,name');
        return $list;

    }


    /**
     * 查询治疗记录
     * @param $map
     *
     * @return mixed
     */
    public function getTreatmentInfo($map)
    {

        if($this->limit){
            $map = array_merge($map, ['d.merchant_id' => $this->merchant_id]);
        }
        $list = $this->alias('d')->join('tfb_gp_merchant b on d.merchant_id=b.id')->join('tfb_gp_technician c on d.technician_id=c.id')->join('tuser_info e on e.user_id = d.user_id')->field("b.store_name,c.name techname, e.true_name opuser, d.*")->where($map)->order('d.treatment_time desc')->select();
        foreach ($list as &$info) {
            $info['vision_info']    = json_decode($info['vision_info'], true);
            $info['treatment_time'] = dateformat($info['treatment_time'], 'y-m-d H:i');

            //treatment_process
            $info['process_txt'] = C('process_arr.'.$info['treatment_process']);
        }
        return $list;
    }


    public function TreatmentRecord($map)
    {
        $record = M()->table('tfb_gp_treatment_record tr')->join('tfb_gp_technician tt on tt.id=tr.technician_id')->join('tfb_gp_customer tc on tc.id=tr.customer_id')->join('tfb_gp_merchant tm on tm.id=tr.merchant_id')->join('tuser_info e on e.user_id = tr.user_id')->join("tcity_code l on l.path=tc.household_reg")->field('tr.*,tt.name as t_name,tc.treatment_process,tc.source,tc.other_source,tc.name,tm.store_short_name,e.true_name,l.province_code,l.province,l.city')->where($map)->order('tr.add_time desc')->select();

        return $record;
    }


    /**
     * @param $cid  会员id
     * @param $data 治疗数据
     *              添加治疗记录
     */
    public function addTreatmentRecord($data)
    {
        $data['add_time']    = date('YmdHis');
        $data['feed_status'] = '0';
        $data['vision_info'] = json_encode($data['vision_info']);

        $recordId = $this->add($data);

        return $recordId;
    }

    public function chgCustomProcess($cid, $process){
        $map = [
            'id' => $cid
        ];
        if($this->limit){
            $map = array_merge($map, ['merchant_id' => $this->merchant_id]);
        }
        $flag = M('tfb_gp_customer')->where($map)->save(['treatment_process'=>$process]);
        return $flag !== false;
    }
    //获取单次治疗记录
    public function selectTreatmentInfo($map){
        $record = M()->table('tfb_gp_treatment_record tr')->join('tfb_gp_technician tt on tt.id=tr.technician_id')->join('tfb_gp_customer tc on tc.id=tr.customer_id')->join('tuser_info ti on ti.user_id=tr.user_id')->field('tr.*,tt.name,ti.true_name')->where($map)->order('tr.treatment_time desc')->find();
        return $record;
    }
    /**
     * 查询客户治疗记录(所有)
     * @param $map
     *
     * @return mixed
     */
    public function myTreatmentRecordAll($map)
    {
        $record = M()->table('tfb_gp_treatment_record tr')->join('tfb_gp_technician tt on tt.id=tr.technician_id')->join('tfb_gp_customer tc on tc.id=tr.customer_id')->join('tuser_info ti on ti.user_id=tr.user_id')->field('tr.*,tt.name,tc.treatment_process,ti.true_name')->where($map)->order('tr.treatment_time desc')->select();

        return $record;
    }

    public function getopenid($map){
        $info = M()->table('tfb_gp_customer a')->join('tfb_gp_merchant b on a.merchant_id=b.id')->join('tfb_gp_technician c on a.technician_id=c.id')->join('tfb_gp_customer_login d on a.mobile=d.mobile')->field("d.openid")->where($map)->select();
        return $info;
    }
}
