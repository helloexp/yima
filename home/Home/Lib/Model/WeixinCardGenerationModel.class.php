<?php
/**
 * zhaobl
 * use: index.php?g=Weixin&m=Weixin&a=cardGeneration
 * Date: 2016/1/26
 */
class WeixinCardGenerationModel extends Model
{
    protected $tableName = '__NONE__';
    public function cardGenerationInfo($node_id,$batch_desc='',$goods_name='',$print_text='',$status='',$limitStart='',$limitEnd='')
    {
        if($batch_desc != ''){
            $data['m.batch_desc'] = array('like',"%$batch_desc%");
        }
        if($goods_name != ''){
            $data['g.goods_name'] = array('like',"%$goods_name%");
        }
        if($print_text != ''){
            $data['m.print_text'] = array('like',"%$print_text%");
        }
        if($status != ''){
            $data['m.status'] = $status;
        }

        $data['m.node_id'] = $node_id;
        $model = M('tbatch_import');


        if($limitStart == '' && $limitEnd == ''){
            $result = $model->alias('m')
                    ->join('inner join tbatch_info i on i.id = m.b_id')
                    ->join('inner join tgoods_info g on g.goods_id=i.goods_id')
                    ->join('inner join twx_card_type w on w.card_id=i.card_id')
                    ->join('left join twx_card_stat t on t.b_id=i.id')
                    ->field('m.batch_id,m.batch_desc,g.goods_name,w.date_begin_timestamp,w.date_end_timestamp,m.total_count,m.succ_num,m.status,m.print_text,m.file_name,t.get_stat_num')
                    ->where($data)->count();
        }else{

            $result = $model->alias('m')
                    ->join('inner join tbatch_info i on i.id = m.b_id')
                    ->join('inner join tgoods_info g on g.goods_id=i.goods_id')
                    ->join('inner join twx_card_type w on w.card_id=i.card_id')
                    ->join('left join twx_card_stat t on t.b_id=i.id')
                    ->field('m.batch_id,m.batch_desc,g.goods_name,w.date_begin_timestamp,w.date_end_timestamp,m.total_count,m.succ_num,m.status,m.print_text,m.file_name,t.get_stat_num')
                    ->where($data)->limit($limitStart, $limitEnd)->select();
        }


        return $result ? $result : false;
    }

    public function weixinCardBatachGeneration($node_id,$user_id,$batchname,$goods_id,$card_id,$goodsNo,$validate_type,$description)
    {
        $model = new Model();
        $model->startTrans();//开启事务

        $goodsData = $this->getGoodsInfo($goods_id);//获取该商品数据
        $twxData = $this->getTwxInfo($goods_id);//获取该商品的卡券信息
        if(!$goodsData || !$twxData){
            return false;
        }

        $oneYear = time()+315360000;//十年之后的时间戳
        $infoData = array();
        $infoData['batch_no'] = $goodsData['batch_no'];
        $infoData['batch_short_name'] = $goodsData['goods_name'];
        $infoData['batch_name'] = $goodsData['goods_name'];
        $infoData['node_id'] = $node_id;
        $infoData['user_id'] = $user_id;
        $infoData['batch_class'] = $goodsData['goods_type'];
        $infoData['validate_type'] = 0;
        $infoData['begin_time'] = getTime(1);
        $infoData['end_time'] = getTime(1,$oneYear);
        $infoData['verify_begin_date'] = getTime(1);
        $infoData['verify_end_date'] = getTime(1,$oneYear);
        $infoData['verify_begin_type'] = $twxData['date_type'];
        $infoData['verify_end_type'] = getTime(1,$twxData['date_end_timestamp']);
        $infoData['add_time'] = getTime(1);
        $infoData['check_status'] = 0;
        $infoData['hall_top_time'] = 0;
        $infoData['is_delete'] = 0;
        $infoData['goods_id'] = $goods_id;
        $infoData['card_id'] = $card_id;
        $infoData['storage_num'] = 0;

        $id = $model->table('tbatch_info')->add($infoData);

        $importData = array();
        $importData['batch_desc'] = $batchname;
        $importData['batch_no'] = $goodsData['batch_no'];
        $importData['total_count'] = $goodsNo;
        $importData['trans_type'] = $validate_type;
        $importData['print_text'] = $description;
        $importData['user_id'] = $user_id;
        $importData['node_id'] = $node_id;
        $importData['status'] = 0;
        $importData['add_time'] = getTime(1);
        $importData['send_begin_time'] = getTime(1);
        $importData['data_from'] = 2;
        $importData['validate_times'] = 1;
        $importData['check_status'] = 0;
        $importData['b_id'] = $id;

        $result = $model->table('tbatch_import')->add($importData);

        if ($result){
            $model->commit();
        }else{
            $model->rollback();
            $result = 0;
        }
        return $result;
    }

    /**
     * @param $goods_id
     * 传入goods_id 返回该条商品数据
     * @return mixed
     */
    public function getGoodsInfo($goods_id)
    {
        $goodsInfo = M('tgoods_info');
        $data['goods_id'] = $goods_id;
        $result = $goodsInfo->where($data)->find();
        return $result? $result : '';
    }

    /**
     * @param $goods_id
     * 传入goods_id 返回该条商品的卡券数据
     * @return mixed
     */
    public function getTwxInfo($goods_id)
    {
        $twx = M('twx_card_type');
        $data['goods_id'] = $goods_id;
        $result = $twx->where($data)->find();
        return $result? $result : '';
    }

    /**
     * 根据晓华给的sql取出领取信息
     * @param $node_id
     * @param $batch_id
     */
    public function getReceiveList($node_id,$batch_id)
    {
        $model = M('twx_assist_number');
        $data['a.node_id'] = $node_id;
        $data['a.relation_id'] = $batch_id;
        $data['a.status'] = '2';
        $result = $model->alias('a')
                    ->join('LEFT join tbarcode_trace b on a.assist_number = b.assist_number')
                    ->join('LEFT join twx_user c ON b.node_id = c.node_id AND b.wx_open_id  = c.openid')
                    ->join('LEFT join tbatch_info d ON b.b_id = d.id')
                    ->field('d.batch_short_name,b.begin_time,b.end_time,b.wx_open_id,c.nickname,b.trans_time')
                    ->where($data)->select();
        return $result? $result : '';
    }

}