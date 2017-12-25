<?php

class WithdrawService extends Action {

    public function _initialize() {
    }

    /**
     * 提领券信息
     */
    public function getWithdrawInfo($condition) {
        $goodsInfo = M('tbarcode_trace b ')->field(
            "a.goods_type, a.goods_image, a.goods_id, a.goods_name,a.goods_desc,b.trans_time, b.req_seq, b.begin_time, b.end_time, b.status, b.use_status,b.assist_number,b.mms_title,b.mms_text,b.barcode_bmp, b.node_id, a.online_verify_flag, t.ecshop_sku_id, t.ecshop_sku_desc")
            ->join("tgoods_info a ON a.batch_no=b.batch_no")
            ->join('ttg_order_info_ex t on t.code_trace = b.request_id')
            ->where($condition)
            ->find();
        if ($goodsInfo['status'] == '1') {
            $goodsInfo['use_status'] = '0';
        }
        $goodsInfo['verifyTime'] = dateformat($goodsInfo['begin_time'], 'Y-m-d') .
             '至';
        $goodsInfo['verifyTime'] .= dateformat($goodsInfo['end_time'], 'Y-m-d');
        return $goodsInfo;
    }

    public function _bar_resize($data, $other) {
        $im = $this->_img_resize($data, 3);
        if ($im !== false) {
            ob_start();
            switch ($other) {
                case 'gif':
                    imagegif($im);
                    break;
                case 'jpg':
                case 'jpeg':
                    imagejpeg($im);
                    break;
                case 'png':
                    imagepng($im);
                    break;
                case 'wbmp':
                    imagewbmp($im);
                    break;
                default:
                    return false;
                    break;
            }
            imagedestroy($im);
            $new_img = ob_get_contents();
            ob_end_clean();
            return $new_img;
        } else {
            return false;
        }
    }

    public function _img_resize($data, $fdbs) {
        // Resize
        $source = imagecreatefromstring($data);
        $s_white_x = 0; //
        $s_white_y = 0; //
        $s_w = imagesx($source); // 原图宽度
        $new_img_width = ($s_w) * $fdbs;
        $new_img_height = $new_img_width;
        
        // 新的偏移量
        $d_white_x = ($new_img_width - $s_w * $fdbs) / 2;
        $d_white_y = $d_white_x;
        
        // Load
        $thumb = imagecreate($new_img_width, $new_img_height);
        
        imagecopyresized($thumb, $source, $d_white_x, $d_white_y, $s_white_x, 
            $s_white_y, $s_w * $fdbs, $s_w * $fdbs, $s_w, $s_w);
        return $thumb;
    }
}
