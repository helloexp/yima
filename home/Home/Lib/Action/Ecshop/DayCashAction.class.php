<?php
// �����ʼ��սᷢ��
class DayCashAction extends BaseAction {

    protected $file_path = "/tmp/";

    public function _initialize() {
        if (C('LOG_PATH'))
            C('LOG_PATH', C('LOG_PATH') . "DAYCASH_"); // ���¶���Ŀ־Ŀ¼
    }

    /* ��ں��� */
    public function index() {
    }

    public function day_cash() {
        $where_time = date('Ymd') . "000000";
        // $where_time = '20150228000000';
        $acount_type_pub = iconv('GBK', 'UTF8', '���жԹ��˻�');
        $acount_type_pri = iconv('GBK', 'UTF8', '���ж�˽�˻�');
        $this->log("start day_cash " . $where_time);
        // update
        $sql = "UPDATE tnode_cash_trace 
				SET trans_status = '9'
				WHERE trans_type = '2' and  trans_status = '0' and  add_time <'" .
             $where_time . "'";
        $rs = M()->execute($sql);
        if ($rs === false) {
            $this->log("update tnode_cash_trace error sql[" . M()->_sql());
            return;
        }
        // ��˽ 0001.xls �Թ� 0002.xls
        // ��˽
        $pri_file = date('ymd') . '0001.xls';
        $title_arr = array(
            '*�̻���ˮ��', 
            '*�տ�˻�����', 
            '*�տ��������', 
            '*�տ����', 
            '*�տ�˺�', 
            '*���(Ԫ)', 
            '�Ƿ������', 
            '��������֧��ȫ��', 
            '�տ֤������', 
            '�տ֤������', 
            '�տ�ֻ�����', 
            'ʡ', 
            '��', 
            '��ע');
        $day_cash_arr = M()->table("tnode_cash_trace t")->join(
            'LEFT JOIN tnode_cash c ON t.node_id = c.node_id')
            ->where(
            "t.add_time <'" . $where_time .
                 "' and trans_type = '2' and t.trans_status = '9' and c.bank_type = '0'")
            ->field(
            "lpad(t.id,'9','0'), '" . $acount_type_pri .
             "', c.account_bank, c.account_name ,c.account_no, sum(t.cash_money), '' as is_pass_ok,c.account_bank_ex")
            ->group('t.node_id')
            ->select();
        $pri_count = count($day_cash_arr);
        $this->log("generate private file sql :" . M()->_sql());
        $this->log(
            "generate private file retarr " . print_r($day_cash_arr, true));
        $this->log("generate private file retarr count :" . $pri_count);
        $ret_arr = $this->download_excel($day_cash_arr, $title_arr, $pri_file);
        $this->log("generate private file retarr " . print_r($ret_arr, true));
        // �Թ�
        $pub_file = date('ymd') . '0002.xls';
        if ($pri_file == 0)
            $pub_file = date('ymd') . '0001.xls';
        $title_arr = array(
            '*�̻���ˮ��', 
            '*�տ�˻�����', 
            '*�տ��������', 
            '*��������֧��ȫ��', 
            '*�տ����', 
            '*�տ�˺�', 
            '*���(Ԫ)', 
            '�տ�ֻ�����', 
            'ʡ', 
            '��', 
            '��ע');
        $day_cash_arr = M()->table("tnode_cash_trace t")->join(
            'LEFT JOIN tnode_cash c ON t.node_id = c.node_id')
            ->where(
            "t.add_time <'" . $where_time .
                 "' and trans_type = '2' and t.trans_status = '9' and c.bank_type = '1'")
            ->field(
            "lpad(t.id,'9','0') ,'" . $acount_type_pub .
             "', c.account_bank, c.account_bank_ex,c.account_name ,c.account_no, sum(t.cash_money)")
            ->group('t.node_id')
            ->select();
        $pub_count = count($day_cash_arr);
        $this->log("generate public file sql :" . M()->_sql());
        $this->log(
            "generate public file retarr " . print_r($day_cash_arr, true));
        $this->log("generate public file retarr count :" . $pub_count);
        $ret_arr = $this->download_excel($day_cash_arr, $title_arr, $pub_file);
        $this->log("generate public file retarr " . print_r($ret_arr, true));
        // update
        $sql = "UPDATE tnode_cash_trace 
				SET trans_status = '1' , deal_time = '" .
             date('YmdHis') . "'
				WHERE trans_status = '9' and  trans_type = '2' and  add_time <'" .
             $where_time . "'";
        $rs = M()->execute($sql);
        if ($rs === false) {
            $this->log("update tnode_cash_trace2 error sql[" . M()->_sql());
            return;
        }
        // �����ʼ�1
        if (($pub_count > 0) || ($pri_count > 0)) {
            $content['petname'] = 'zengc@imageco.com.cn';
            $content['CC'] = 'zhengxh@imageco.com.cn';
            
            $content['test_title'] = iconv("gbk", "utf8", 
                date('Ymd') . "����excel");
            $content['text_content'] = iconv("gbk", "utf8", 
                "���ʼ��и�����������" . date('Ymd') .
                     "֮ǰ�ĶԹ��Ͷ�˽�˻������ּ�¼(0001�Ƕ�˽�ļ���0002�ǶԹ��ļ�)");
            $content['add_file'] = array();
            if ($pri_count > 0)
                $content['add_file'][] = $this->file_path . $pri_file;
            if ($pub_count > 0)
                $content['add_file'][] = $this->file_path . $pub_file;
                // $content['add_file'] = $this->file_path.$pub_file;
                // $content['add_file'] =
                // array($this->file_path.$pub_file,$this->file_path.$pri_file);
            $rs = to_email($content);
            $this->log(
                "send mail to " . $content['petname'] . " CC to " .
                     $content['CC'] . " result[" . $rs . "]");
        }
    }

    /**
     * ���ݵ���excel $data ԭʼ���� array $col_arr ���ı�ͷ array
     */
    private function download_excel($data, $col_arr, $file) {
        require_once (dirname(__FILE__) . '/../../../Lib/Vendor/PHPExcel.php');
        
        // ʵ����
        $objPHPExcel = new PHPExcel();
        // �����ĵ�����
        $objPHPExcel->getProperties()
            ->setCreator("wangcaio2o")
            ->setLastModifiedBy("wangcaio2o")
            ->setTitle("wangcaio2o download")
            ->setSubject("wangcaio2o download")
            ->setDescription("wangcaio2o download")
            ->setKeywords("wangcaio2o download")
            ->setCategory("wangcaio2o download file");
        
        // ������һ��������
        $objPHPExcel->setActiveSheetIndex(0);
        $objSheet = $objPHPExcel->getActiveSheet();
        // ������ת����
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
                'ret_text' => '�������ó���52');
            return $ret_arr;
        }
        $col_tmp = array();
        $i = 1;
        // ������ݿ�������excel�����Ķ�Ӧ���� ��д��excel�ı�ͷ
        foreach ($col_arr as $k => $v) {
            $col_tmp[$k] = $tmp_arr[$i];
            $objSheet->setCellValueExplicit($tmp_arr[$i] . '2', 
                iconv("GBK", "UTF-8", $v), PHPExcel_Cell_DataType::TYPE_STRING);
            $i ++;
        }
        
        // д������
        $data_count = count($data); // ��������
        echo ($data_count);
        foreach ($data as $dk => $dv) {
            $curr_row = $dk + 3; // ���б�ע�ÿ� �����1��ʼ ���ϱ�ͷ��1��
                                 // �����Ǵ�A3��ʼд��
                                 // ÿ�н���ѭ��
            $dv = array_values($dv);
            foreach ($dv as $kk => $vv) {
                $objSheet->setCellValueExplicit($col_tmp[$kk] . $curr_row, $vv, 
                    PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        
        // setCellValueExplicit ��ʽָ����������
        $objSheet->getDefaultColumnDimension()->setWidth(15); // ����������Ĭ�Ͽ��
        $objSheet->setTitle('Simple');
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        // ����Ϊ�ļ�
        $filename = $this->file_path . $file;
        $objWriter->save($filename);
        return array(
            'ret_code' => '0000', 
            'ret_text' => '����ɹ�');
        // ��������� �Զ��屣��·��
        // $filename = "tmp1.xlsx";
        // header('Content-Type:
        // application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-Disposition: attachment;filename="'.$filename.'"');
        // header('Cache-Control: max-age=0');
        // $objWriter->save('php://output');
        // exit;
    }
    
    // ��¼��־
    protected function log($msg, $level = Log::INFO) {
        // trace('Log.'.$level.':'.$msg);
        Log::write($msg, '[' . _APP_PID_ . ']' . $level);
    }
}
