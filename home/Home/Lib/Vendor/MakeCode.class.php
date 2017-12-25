<?php
// 生成二维码
class MakeCode {
    // 生成下载二维码
    public function MakeCodeImg($url = '', $is_down = false, $type = '', $log_dir = '', 
        $filename = '', $color = '', $ap_arr = '', $saveandprint = '') {
        if (empty($url))
            exit();
        
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $size_arr = C('SIZE_TYPE_ARR');
        empty($type) ? $size = 4 : $size = $size_arr[$type];
        empty($filename) ? $filename = time() . ".png" : $filename .= '.png';
        if (! empty($log_dir)) {
            $fileExist = file_exists($log_dir);
            if (! $fileExist) {
                $log_dir = '';
            }
        }
        if ($is_down === true) {
            $saveandprint = is_bool($saveandprint) ? $saveandprint : true;
            $code = QRcode::png($url, $filename, '0', $size, $margin = 2, 
                $saveandprint, $log_dir, $color, $ap_arr);
        } else {
            $code = QRcode::png($url, false, '0', $size, $margin = 2, 
                $saveandprint = false, $log_dir, $color, $ap_arr);
        }
    }
}
