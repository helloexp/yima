<?php

class CrontabAction extends Action {
    
    // public $_authAccessMap = '*';
    public $file_path = "";

    public $host = 'ftp.example.org';

    public $usr = 'example_user';

    public $pwd = 'example_password';

    public function _initialize() {
        $this->file_path = "/www/wangcai_new/Home/Public/Image/Wyhb/excel/";
        /*
         * if($this->getIP()!=='127.0.0.1') { die("非法访问！"); }
         */
    }

    private function getIP() {
        static $realip;
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $realip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }

    public function index() {
        $map = array();
        $map['a.status'] = 1;
        $updatetime = date("Ymd", strtotime("-1 day"));
        $map['a.update_time'] = array(
            "like", 
            "{$updatetime}%");
        $list = M()->table('tfb_yhb_node_info a')
            ->where($map)
            ->field('a.message')
            ->select();
        log_write("总计有" . count($list) . "个商户更新挂机短信", "", "YHB");
        $pub_file = date('ymd') . '0001.xls';
        $title_arr = array(
            '*短信内容');
        $day_cash_arr = $list;
        $ret_arr = $this->download_excel($day_cash_arr, $title_arr, $pub_file);
        
        sleep(2);
        /* ftp上传 */
        log_write("开启ftp连接", "", "YHB");
        // FTP access parameters
        $host = $this->host;
        $usr = $this->usr;
        $pwd = $this->pwd;
        // file to move:
        $local_file = $this->file_path . $pub_file;
        $ftp_path = '/data/example.txt';
        // connect to FTP server (port 21)
        $conn_id = ftp_connect($host, 21) or die("Cannot connect to host");
        // send access parameters
        ftp_login($conn_id, $usr, $pwd) or die("Cannot login");
        // turn on passive mode transfers (some servers need this)
        // ftp_pasv ($conn_id, true);
        // perform file upload
        $upload = ftp_put($conn_id, $ftp_path, $local_file, FTP_ASCII);
        // check upload status:
        print (! $upload) ? 'Cannot upload' : 'Upload complete';
        print "\n";
        /*
         * * Chmod the file (just as example)
         */
        // If you are using PHP4 then you need to use this code:
        // (because the "ftp_chmod" command is just available in PHP5+)
        if (! function_exists('ftp_chmod')) {

            function ftp_chmod($ftp_stream, $mode, $filename) {
                return ftp_site($ftp_stream, 
                    sprintf('CHMOD %o %s', $mode, $filename));
            }
        }
        // try to chmod the new file to 666 (writeable)
        if (ftp_chmod($conn_id, 0666, $ftp_path) !== false) {
            print $ftp_path . " chmoded successfully to 666\n";
        } else {
            print "could not chmod $file\n";
        }
        // close the FTP stream
        ftp_close($conn_id);
        
        log_write("任务执行结束", "", "YHB");
    }

    /**
     * 数据导成excel $data 原始数据 array $col_arr 中文表头 array
     */
    private function download_excel($data, $col_arr, $file) {
        require_once (dirname(__FILE__) . '/../../../Lib/Vendor/PHPExcel.php');
        
        // 实例化
        $objPHPExcel = new PHPExcel();
        // 设置文档属性
        $objPHPExcel->getProperties()
            ->setCreator("wangcaio2o")
            ->setLastModifiedBy("wangcaio2o")
            ->setTitle("wangcaio2o download")
            ->setSubject("wangcaio2o download")
            ->setDescription("wangcaio2o download")
            ->setKeywords("wangcaio2o download")
            ->setCategory("wangcaio2o download file");
        
        // 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
        $objSheet = $objPHPExcel->getActiveSheet();
        // 列名中转数组
        $tmp_arr = array(
            '1' => 'A', 
            '2' => 'B', 
            '3' => 'C', 
            '4' => 'D', 
            '5' => 'E', 
            '6' => 'F', 
            '7' => 'G', 
            '8' => 'H', 
            '9' => 'I', 
            '10' => 'J', 
            '11' => 'K', 
            '12' => 'L', 
            '13' => 'M', 
            '14' => 'N', 
            '15' => 'O', 
            '16' => 'P', 
            '17' => 'Q', 
            '18' => 'R', 
            '19' => 'S', 
            '20' => 'T', 
            '21' => 'U', 
            '22' => 'V', 
            '23' => 'W', 
            '24' => 'X', 
            '25' => 'Y', 
            '26' => 'Z', 
            '27' => 'AA', 
            '28' => 'AB', 
            '29' => 'AC', 
            '30' => 'AD', 
            '31' => 'AE', 
            '32' => 'AF', 
            '33' => 'AG', 
            '34' => 'AH', 
            '35' => 'AI', 
            '36' => 'AJ', 
            '37' => 'AK', 
            '38' => 'AL', 
            '39' => 'AM', 
            '40' => 'AN', 
            '41' => 'AO', 
            '42' => 'AP', 
            '43' => 'AQ', 
            '44' => 'AR', 
            '45' => 'AS', 
            '46' => 'AT', 
            '47' => 'AU', 
            '48' => 'AV', 
            '49' => 'AW', 
            '50' => 'AX', 
            '51' => 'AY', 
            '52' => 'AZ');
        
        $col_count = count($col_arr);
        if ($col_count > 52) {
            $ret_arr = array(
                'ret_code' => '9001', 
                'ret_text' => '列数不得超过52');
            return $ret_arr;
        }
        $col_tmp = array();
        $i = 1;
        // 获得数据库列名和excel列名的对应数组 并写入excel的表头
        foreach ($col_arr as $k => $v) {
            $col_tmp[$k] = $tmp_arr[$i];
            $objSheet->setCellValueExplicit($tmp_arr[$i] . '2', $v, 
                PHPExcel_Cell_DataType::TYPE_STRING);
            $i ++;
        }
        
        // 写入数据
        $data_count = count($data); // 数据行数
        echo ($data_count);
        foreach ($data as $dk => $dv) {
            $curr_row = $dk + 3; // 首行备注置空 数组从1开始 加上表头的1行 数据是从A3开始写入
                                 // 每行进行循环
            $dv = array_values($dv);
            foreach ($dv as $kk => $vv) {
                $objSheet->setCellValueExplicit($col_tmp[$kk] . $curr_row, $vv, 
                    PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        
        // setCellValueExplicit 显式指定内容类型
        $objSheet->getDefaultColumnDimension()->setWidth(15); // 设置所有列默认宽度
        $objSheet->setTitle('Simple');
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        // 保存为文件
        $filename = $this->file_path . $file;
        $objWriter->save($filename);
        return array(
            'ret_code' => '0000', 
            'ret_text' => '保存成功');
        // 浏览器弹框 自定义保存路径
        // $filename = "tmp1.xlsx";
        // header('Content-Type:
        // application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-Disposition: attachment;filename="'.$filename.'"');
        // header('Cache-Control: max-age=0');
        // $objWriter->save('php://output');
        // exit;
    }
}
