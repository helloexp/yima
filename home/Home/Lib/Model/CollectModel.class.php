<?php
class CollectModel extends BaseModel
{
    protected $tableName = '__NONE__';
    /**
     * 查询所有自定义采集字段信息
     * @param $node_id
     *
     * @return mixed|string
     */
    public function collectInfo($node_id,$firstRow,$listRow)
    {
        $model = M('tcollect_question_field');
        $result = $model->where("node_id = $node_id")->limit($firstRow,$listRow)->select();

        foreach($result as $k=>&$v){
            if($v['value_list'] != ''){
                $whe['field_id'] = $v['id'];
                //$cn = M('tmember_attribute_stat')->where($whe)->order('member_cnt')->getField('member_cnt');
                //$v['count'] = $cn;//count为采集项中所有选项的会员数最小值 如果不为0 则代表本采集项中所有选项都有会员数
                // 当时判断的是如果采集项中每个选项都有会员数 则不出现修改两字 后来觉得不对 因为增加选项也不可以了 所以页面上把此判断去掉
                $list[] = explode('|',$v['value_list']);
                foreach(explode('|',$v['value_list']) as $kk=>$vv){
                    $arr = explode(':',$vv);
                    $v['value_lists'][$arr[0]][0] = $arr[1];
                    $data['node_id'] = $node_id;
                    $data['field_id'] = $v['id'];
                    $data['field_name'] = $arr[1];
                    $count = M('tmember_attribute_stat')->field('member_cnt')->where($data)->select();
                    $v['value_lists'][$arr[0]][1] = $count[0]['member_cnt'];//选择该选项的会员数
                    $v['value_lists'][$arr[0]][2] = $arr[0];//选项值 该选项在表中的下标
                }
            }
        }

        return $result? $result : '';
    }


    /**
     * @param $node_id
     *
     * @return int //返回总条数
     */
    public function totalCount($node_id)
    {
        $model = M('tcollect_question_field');
        $count = $model->where("node_id = $node_id")->count();
        return $count? $count : 0;
    }

    /**
     * 查询最后一条自定义采集信息返回name字段的数值
     * @param $node_id
     *
     * @return int|mixed
     */
    public function lastCount($node_id)
    {
        $lastCount = M('tcollect_question_field')
                ->where(array('node_id'=>$node_id))
                ->order('add_time DESC')->getField('name');

        $lastCount = array_pop(explode('_',$lastCount));
        return $lastCount ? $lastCount : 0;
    }

    /**
     * 查询一共有多少条自定义字段
     * @param $node_id
     *
     * @return mixed
     */
    public function getCount($node_id)
    {
        $fielsCount = M('tcollect_question_field')->where(array('node_id'=>$node_id))->count();
        return $fielsCount;
    }


    /**
     * @param $name //html名
     * @param $text //选项名
     * @param $content //选项内容
     * @param $node_id //node_Id
     *
     * @return bool
     */
    public function addCollectField($name,$text,$content,$node_id)
    {
        M()->startTrans();
        $valueList = '';//'值:名|值:名  的格式
        $saveData = array();

        foreach($content as $key=>$val){
            if($val != ''){
                $valueList .= $key.':'.$val.'|';
            }
        }
        $saveData['text'] = $text;
        $saveData['name'] = $name;
        $saveData['value_list'] = substr($valueList, 0 , strlen($valueList)-1); //-1是为了去掉最后一个竖杠
        $saveData['type'] = '1';
        $saveData['is_base_field'] = '0';
        $saveData['node_id'] = $node_id;
        $saveData['add_time'] = getTime('1');
        $result = M('tcollect_question_field')->add($saveData);

        $temp = 1;//测量是否出错
        foreach($content as $k=>$v){
            if($v){
                $data['node_id'] = $node_id;
                $data['field_id'] = $result;
                $data['field_value'] = $k;
                $data['field_name'] = $v;
                $data['member_cnt'] = 0;
                $status = M('tmember_attribute_stat')->add($data);
                if(!$status){
                    $temp = 0;
                }
            }
        }
        if($temp){
            M()->commit();
            return true;
        }else{
            M()->rollback();
            return false;
        }

    }

    /**
     * @param $id
     * @param $content
     * @param $node_id
     *
     * @return bool
     */
    public function editCollectField($id,$content,$node_id)
    {
        $fieldModel = M('tcollect_question_field');
        M()->startTrans();

        $result = $fieldModel->where("id = $id")->field('value_list,id')->find();//原信息

        if(!$result['value_list']){
            return false;
        }

        $info = $this->formatCustomFieldArray($result['value_list']);//原数据

        $newList = array();

        //第一环 上传数据和原数据两数组相比 如果值相同 代表选项未改变 则取原来的Key 并删除元素
        foreach($info as $k=>$v){
            foreach($content as $kk=>$vv){
                if($v==$vv){
                    $newList[$k] = $vv;
                    unset($info[$k]);
                    unset($info[$kk]);
                    unset($content[$kk]);
                }
            }
        }

        //第二环  再比 如果键相同 代表选项改了 则取现在的值
        if(!$info){
            foreach($info as $k=>$v){
                foreach($content as $kk=>$vv){
                    if($k == $kk){
                        $newList[$k] = $vv;
                    }else{
                        $newList[] = $vv;
                    }
                }
            }
        }

        //第三环 剩下的都是新增的 加上
        foreach($content as $k=>$v){
            $newList[] = $v;
        }

        $valueList = '';//'值:名|值:名  的格式
        $whe = '';

        if(count($newList)){
            foreach($newList as $key=>$val){
                if($val != ''){
                    $valueList .= $key.':'.$val.'|';
                    $whe .= " AND field_name !='$val' ";
                }
            }
            //不在新选项里的字段 统统删掉 这条sql行不通 所以下面拆两步走
            //$sql = "delete from `tmember_attribute_stat` where id in
            //(SELECT id FROM `tmember_attribute_stat` WHERE node_id='$node_id' AND field_id= ".$result['id']." $whe)";

            $statModel = M('tmember_attribute_stat');

            $getDelId = $statModel->where("node_id='$node_id' AND field_id= ".$result['id'].$whe)
                    ->getField('id',true);
            if($getDelId){
                $res = $statModel->where(array('id' => array('in', $getDelId)))->delete();
                if(!$res){
                    M()->rollback();
                    return false;
                }
            }

            $getName = $statModel->where("node_id='$node_id' AND field_id= ".$result['id'])
                    ->getField('field_name',true);

            foreach($newList as $k=>$v){
                foreach($getName as $kk=>$vv){
                    if($vv == $v){
                        unset($newList[$k]);
                    }
                }
            }

            $temp = 1;//测量是否出错
            foreach($newList as $kkk=>$vvv){
                if($vvv){
                    $data['node_id'] = $node_id;
                    $data['field_id'] = $result['id'];
                    $data['field_value'] = $kkk;
                    $data['field_name'] = $vvv;
                    $data['member_cnt'] = 0;
                    $status = $statModel->add($data);
                    if(!$status){
                        M()->rollback();
                        return false;
                    }
                }
            }
        }

        $saveData = array();
        $saveData['id'] = $id;
        $saveData['value_list'] = substr($valueList, 0 , strlen($valueList)-1); //-1是为了去掉最后一个竖杠

        //修改
        $result = $fieldModel->save($saveData);
        if($result == false){
            M()->rollback();
            return false;
        }

        M()->commit();
        return true;
    }

    /**
     * @param $fieldVal //自定义采集字段
     *
     * @return array 返回数组
     */
    public function formatCustomFieldArray($fieldVal){
        $result = array();
        $value_list = explode('|',$fieldVal);
        foreach($value_list as $val){
            $tempArray = explode(':', $val);
            $result[$tempArray[0]] = $tempArray[1];
        }
        return $result;
    }


}