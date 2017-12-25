<?php

class TbarcodeTraceModel extends BaseModel {

    /**
     *
     * @param array $condition
     * @return array
     */
    public function getCodeArray($condition) {
        $goodsList = M()->table('tbarcode_trace b ')
            ->join("tgoods_info a ON a.batch_no=b.batch_no")
            ->field(
            "b.*, a.goods_type, a.goods_discount, a.goods_amt, a.goods_name,a.goods_image,a.status as goods_status")
            ->where($condition)
            ->order("b.use_status ASC, b.id DESC")
            ->select();
        
        foreach ($goodsList as $k => $v) {
            if ($v['status'] == '1') {
                $goodsList[$k]['use_status'] = 0;
            }
            // 过期设置状态为3
            if (($goodsList[$k]['end_time'] < date('YmdHis')) &&
                 $goodsList[$k]['use_status'] == '0') {
                $goodsList[$k]['use_status'] = 3;
            }
        }
        return $goodsList;
    }

    /**
     * 通过Seq码查询商品
     */
    public function getGoodsInfoBySeq($seq) {
        $goodsInfo =M()->table("tbarcode_trace b")->field(
            "a.goods_id, a.node_id, b.use_status, a.online_verify_flag, b.trans_time, b.req_seq, b.begin_time, b.end_time,b.assist_number,b.mms_title,b.mms_text,b.barcode_bmp, b.status, bi.verify_end_type, bi.verify_begin_type, bi.verify_begin_date, bi.verify_end_date")
            ->join("tgoods_info a ON a.batch_no=b.batch_no")
            ->join("tbatch_info bi ON bi.id = b.b_id")
            ->join('ttg_order_info_ex t on t.code_trace = b.request_id')
            ->where(array(
            'b.req_seq' => $seq))
            ->find();
        
        if ($goodsInfo['verify_begin_type'] == '0' &&
             $goodsInfo['verify_end_type'] == '0') {
            $goodsInfo['end_time'] = $goodsInfo['verify_end_date'];
        } elseif ($goodsInfo['verify_begin_type'] == '0' &&
             $goodsInfo['verify_end_type'] == '1') {
            $str = "{$goodsInfo['verify_begin_date']} +{$goodsInfo['verify_end_date']} days";
            $goodsInfo['end_time'] = date('Ymd', strtotime($str)) . '235959';
        } elseif ($goodsInfo['verify_begin_type'] == '1' &&
             $goodsInfo['verify_end_type'] == '1') {
            $str = "{$goodsInfo['trans_time']} +{$goodsInfo['verify_end_date']} days";
            $goodsInfo['end_time'] = date('Ymd', strtotime($str)) . '235959';
        }
        
        return $goodsInfo;
    }

    /**
     * 获取提交状态名称
     *
     * @param $type 获取的类型 格式 $type='0,1,3'
     */
    public function getSendStatus($type = null) {
        $sendStatus = array(
            '0' => '发送中', 
            '1' => '发送成功，递送手机未知', 
            '2' => '发送失败', 
            '3' => '送达手机成功', 
            '4' => '送达手机失败');
        if (! is_null($type)) {
            $type = array_flip(explode(',', $type));
            $sendStatus = array_intersect_key($sendStatus, $type);
        }
        return $sendStatus;
    }

    public function updatePhone($where, $phone) {
        return $this->table('tbarcode_trace')
            ->where($where)
            ->save(array(
            'phone_no' => $phone));
    }
}
