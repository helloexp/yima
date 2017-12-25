<?php

class PosAction extends Action {

    public function doActive() {
        // 获取所有的待激活的pos
        $poslist = M('tpos_info')->where(
            array(
                'is_activated' => '0', 
                'pos_status' => '5'))->select();
        $baseUrl = C('ONLINE_POS_ACTIVE');
        if (! empty($poslist)) {
            foreach ($poslist as $k => $v) {
                $url = $baseUrl . $v['pos_id'];
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                $output = curl_exec($ch);
                curl_close($ch);
                log_write($output);
                $result = json_decode($output, true);
                if ($result['retCode'] == '0000' || $result['retCode'] == '1000') {
                    M('tpos_info')->where(
                        array(
                            'id' => $v['id']))->save(
                        array(
                            'is_activated' => '1', 
                            'pos_status' => '0'));
                } elseif ($result['retCode'] == '1002') {
                    // 不做处理，继续激活
                } else {
                    M('tstore_info')->where(
                        array(
                            'store_id' => $v['store_id']))->save(
                        array(
                            'status' => '1'));
                    M('tpos_info')->where(
                        array(
                            'id' => $v['id']))->save(
                        array(
                            'is_activated' => '0', 
                            'pos_status' => '0'));
                }
            }
        }
    }
}








