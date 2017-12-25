<?php

/**
 * @2015/01/16
 */
class StaffManagementAction extends BaseAction {
    //public $_authAccessMap = '*';
    public $ReportManagement;
    public $ReportDate='';
    public $companyList='';
    public function _initialize() {
        parent::_initialize();
        $this->ReportManagement = D('ReportManagement');
        if(C('gansu.node_id')!=$this->nodeId){
            $this->error("该功能只对部分商户开放！");
        }
        $this->companyList=array('润滑油','天然气','重油','其它');
        $this->ReportDate=array(
                '2014'=>'2014',
                '2015'=>'2015',
                '2016'=>'2016',
                '2017'=>'2017',
                '2018'=>'2018'
        );
    }
    public function index() {
        $map=array();
        if(I('custom_number')){
            $map['custom_number']=I('custom_number');
            $this->assign('custom_number',I('custom_number'));
        }
        if(I('name')){
            $name=I('name');
            $map['name']=array('like','%'.$name.'%');
            $this->assign('name',I('name'));
        }
        if(I('company_id')){
            $map['company_id']=I('company_id');
            $this->assign('company_id',I('company_id'));
        }
        $data = $_REQUEST;
        $companyList=$this->ReportManagement->companyName();
        $memberInfo=$this->ReportManagement->getMemberInfo($map,$data);
        $this->assign('reportFlag','open2');
        $this->assign('companyList',$companyList);
        $this->assign('memberList',$memberInfo['list']);
        $this->assign('page', $memberInfo['show']);
        $this->display();
    }
    public function reportSet() {
        $reportName=$this->ReportManagement->getReportName();
        $this->assign('reportName',$reportName);
        $this->assign('reportSetFlag','open2');
        $this->display();
    }
    public function reportSetValue() {
        $reportId=I('report_id');
        if(empty($reportId)){
            $this->error('缺少必要参数');
        }
        if(I('year')){
            $year=I('year');
        }else{
            $year=date('Y');
        }
        $this->initializationFiveYear($reportId);
        $checkReportIdStatus=$this->ReportManagement->checkReportId($reportId);
        if($checkReportIdStatus===false){
            $this->error('该报表类型不存在！');
        }

        $reportSetValue=$this->ReportManagement->reportSetValue($year);
        $reportSetTotal=$this->ReportManagement->getReportSetTotal($year);
        $this->assign('reportSetTotal',$reportSetTotal);
        $this->assign('reportSetValue',$reportSetValue);
        $this->assign('report_id',$reportId);
        $this->assign('reportDate',$this->ReportDate);
        $this->assign('year',$year);
        $this->assign('reportSetFlag','open2');
        $this->display();
    }
    public function reportSetValueSave() {
        $postData=I('post.');
        if($postData){
            if(!$postData['year']){
                $this->error("缺少必要参数！");
            }
            if(!$postData['report_id']){
                $this->error("缺少必要参数！");
            }
            $companyData=json_decode($postData['company'],true);
            M()->startTrans();
            foreach($companyData as $key=>$val){
                if($val[1] || $val[2]){
                    $resStatus=$this->ReportManagement->reportSetSave($val[0],$val[1],$val[2],$postData['year'],$postData['report_id']);
                    if($resStatus===false){
                        M()->rollback();
                        $this->error("保存失败！");
                    }
                }
            }
            M()->commit();
            $this->success("保存成功！");
        }
    }
    public function salesReport() {
        $map=array();
        $startTime=I('start_time');
        if($startTime){
            $map['add_time'][]=array('EGT', $startTime."000000");
            $this->assign('start_time',$startTime);
        }
        $endTime=I('end_time');
        if($endTime){
            $map['add_time'][]=array('ELT', $endTime."235959");
            $this->assign('end_time',$endTime);
        }
        $data = $_REQUEST;
        $companyReport=$this->ReportManagement->getSalesReportImportTrace($map,$data);
        $this->assign('companyReport',$companyReport['list']);
        $this->assign('page',$companyReport['show']);
        $this->assign('salesReportFlag','open2');
        $this->display();
    }
    /*
     * 删除员工
     */
    public function memberDel($id){
        if(!$id){
            $this->error('缺少必要参数！');
        }
        M()->startTrans();
        $res=$this->ReportManagement->memberDel($id);
        if($res===false){
            M()->rollback();
            $this->error("删除员工失败！");
        }
        M()->commit();
        $this->success("删除员工成功！");
    }
    /*
     * 员工编辑
     */
    public function memberEdit(){
        $id=I('id');
        if(!$id){
            $this->error("缺少必要参数！");
        }
        $getOneMember=$this->ReportManagement->getMemberOneById($id);
        if($getOneMember===false){
            $this->error("当前员工不存在！");
        }
        $this->assign('getOneMember',$getOneMember);
        $companyList=$this->ReportManagement->companyName();
        $this->assign('companyList',$companyList);
        $this->display();
    }
    /*
     *保存修改的员工
     */
    public function memberEditSave(){
        $data=I('post.');
        if(!$data['id'] || !$data['mobile'] || !$data['name'] || !$data['company_id']){
            $this->error("缺少必要参数！");
        }
        $updateMember=$this->ReportManagement->memberEdit($data);
        if($updateMember===false){
            $this->error("修改失败！");
        }
        $this->success("修改成功！");
    }
    public function memberSet(){
        $id=I('id');
        if(!$id){
            $this->error("缺少必要参数！");
        }
        $companyList=$this->ReportManagement->companyNameArr();
        $getReportType=$this->ReportManagement->getReportType();
        $dataCompanyList=$this->ReportManagement->getMemberAuthById($id);
        if($dataCompanyList !=false){
            $this->assign('dataCompanyList',json_decode($dataCompanyList['company_id'],true));
        }
        $this->assign('getReportType',$getReportType);
        $this->assign('reportType','1');
        $this->assign('id',$id);
        $this->assign('companyList',$companyList);
        $this->display();
    }
    /*
     * 权限设置保存
     */
    public function memberSetSave(){
        $companyId=I('company_id');
        $reportId=I('report_id');
        $memberId=I('member_id');
        if(!$memberId){
            $this->error("缺少必要参数！");
        }
        $memberInfo=$this->ReportManagement->isMember($memberId);
        if($memberInfo===false){
            $this->error("该员工不存在");
        }
        $data=array();
        if ($companyId && $companyId !="null" && !empty($companyId[0])) {
            $data['company_id']=json_encode($companyId);
        }else{
            $data['company_id']='';
        }
        if ($reportId && $reportId !="null" && !empty($reportId[0])) {
            $data['report_id']=json_encode($reportId);
        }else{
            $data['report_id']='';
        }
        $status=$this->ReportManagement->memberAuthSave($memberId,$data);
        if($status===false){
            $this->error('保存员工数据范围失败！');
        }
        $this->success("保存成功！");
    }
    public function addMember(){
        $postData=I('post.');
        if($postData){
            //单个添加
            if($postData['addMemberType']=='0'){
                if(empty($postData['company_id']) || empty($postData['name']) || empty($postData['mobile'])){
                    $this->error("所属机构或者姓名或者手机号码不能为空");
                }
                $checkMember=$this->ReportManagement->checkMemberByMobile($postData['mobile']);
                if($checkMember===false){
                    $this->error("该手机号码已经是员工");
                }
                $memberData=array(
                    'custom_number'=>$postData['custom_number'],
                    'company_id'=>$postData['company_id'],
                    'name'=>$postData['name'],
                    'mobile'=>$postData['mobile'],
                );
                $memberAddStatus=$this->ReportManagement->memberAdd($memberData);
                if($memberAddStatus===false){
                    $this->error("添加员工失败！");
                }
                $this->success("添加员工成功！");
            }else{
                //批量添加
                $this->addMemberUpload();
            }
        }
        $companyList=$this->ReportManagement->companyName();
        $this->assign('companyList',$companyList);
        $this->display();
    }
    public function addReport(){
        $this->display();
    }
    public function addReportSave(){
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1024;
        $upload->allowExts = array("xls");
        $upload->savePath = APP_PATH . '/Upload/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $errormsg = $upload->getErrorMsg();
            $this->error($errormsg);
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $fileUrl = $info[0]['savepath'] . $info[0]['savename'];
        }
        $dataInfo = $this->read($fileUrl);
        $companyList=$this->companyList;
        foreach($dataInfo as $key=>$data){
            if($key>=2){
                if(in_array(trim($data[0]),$companyList)){
                    if(trim($data[0])==$companyList[0]){
                        $data[12]="1000002";
                    }elseif(trim($data[0])==$companyList[1]){
                        $data[12]="1000000";
                    }elseif(trim($data[0])==$companyList[2]){
                        $data[12]="1000001";
                    }elseif(trim($data[0])==$companyList[3]){
                        $data[12]="1000003";
                    }
                }
                $reportData=array(
                        'company_name'=>trim($data[0]),
                        'year_salse'=>trim($data[1]),
                        'year_retail'=>trim($data[2]),
                        'year_rate'=>trim($data[3]),
                        'year_sales_complete'=>trim($data[4]),
                        'year_retail_complete'=>trim($data[5]),
                        'total_month'=>trim($data[6]),
                        'month_retail'=>trim($data[7]),
                        'month_retail_rate'=>trim($data[8]),
                        'day_sales'=>trim($data[9]),
                        'day_retail'=>trim($data[10]),
                        'day_retail_rate'=>trim($data[11]),
                        'company_id'=>trim($data[12]),
                        'date'=>date('Ymd', strtotime($this->excelTime($data[13]))),
                        'add_time'=>date('YmdHis')
                );
                $addCompanyReportStatus=$this->ReportManagement->addCompanyReport($reportData);
                if($addCompanyReportStatus===false){
                    M()->rollback();
                    $this->error("第".$key."行插入失败！");
                }
                $itemsStatus=$this->ReportManagement->addCompanyItems($reportData);
                if($itemsStatus===false){
                    M()->rollback();
                    $this->error("第".$key."行插入失败！");
                }
                $addCompanyReportId[]=$addCompanyReportStatus;
            }
        }
        $addCompanyReportDown=array(
                'add_time'=>date('YmdHis'),
                'report_id'=>json_encode($addCompanyReportId),
                'user_name'=>$this->user_name
        );
        $reportDown=$this->ReportManagement->addCompanyReportDown($addCompanyReportDown);
        if($reportDown===false){
            M()->rollback();
            $this->error("生成下载记录失败！");
        }
        M()->commit();
        $num=$key-1;
        $this->success( "导入成功，共导入{$num}个报表！");
    }
    public function downReport(){
        $id=I('id');
        if(!$id){
            $this->error("缺少必要参数！");
        }
        $list =$this->ReportManagement->downReport($id);
        $keyName = array(
                'id' => 'id',
                'company_name' => '分公司',
                'year_salse' => '销售',
                'year_retail' => '其中：零售',
                'year_rate' => '零售率',
                'year_sales_complete' => '销售完成计划率',
                'year_retail_complete' => '零售完成计划率',
                'total_month' => '合计月',
                'month_retail' => '其中：零售',
                'month_retail_rate' => '零售率',
                'day_sales' => '销售(日)',
                'day_retail' => '零售（日）',
                'day_retail_rate' => '零售率（日）',
                'company_id' => '分公司代码',
                'date' => '日期',
                'add_time'=>'导入日期'
        );
        $fileName = date('Y-m-d') . '-销售报表.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $cj_title = "分公司,销售,其中：销售,零售率%,销售完成计划率,零售完成计划率,合计（月）,其中：零售,零售率%,销售,其中：零售,零售率%,分公司代码,日期,导入时间\r\n";
        $cj_title = iconv('utf-8', 'gbk', $cj_title);
        echo $cj_title;
        foreach ($list as $v) {
            $line="{$v['company_name']}"."\t".','.$v['year_salse'].','.$v['year_retail'].','.$v['year_rate'].','.$v['year_sales_complete'].','.$v['year_retail_complete'].','.$v['total_month'].','.$v['month_retail'].','.$v['month_retail_rate'].','.$v['day_sales'].','.$v['day_retail'].','.$v['day_retail_rate'].','.'0'.$v['company_id'].','.$v['date'].','.$v['add_time']."\r\n";
            $line = iconv('utf-8', 'gbk', $line);
            echo $line;
        }
        exit;
    }
    public function addMemberUpload(){
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1024;
        $upload->allowExts = array("csv");
        $upload->savePath = APP_PATH . '/Upload/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $errormsg = $upload->getErrorMsg();
            $this->error($errormsg);
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $fileUrl = $info[0]['savepath'] . $info[0]['savename'];
        }
        if (($handle = fopen($fileUrl, "rw")) !== FALSE) {
            $row = 0;
            M()->startTrans();
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) { // 读取csv文件
                ++ $row;
                if ($row == 1) {
                    continue;
                }
                $data = utf8Array($data);
                // 校验各个字段
                $companyId = trim($data[0]);
                if (empty($companyId)) {
                    M()->rollback();
                    $this->error("第".$row."行的分公司代码为空！");
                }
                $checkCode=$this->ReportManagement->checkCompanyId($companyId);
                if($checkCode===false){
                    M()->rollback();
                    $this->error("第".$row."行的分公司代码不存在！");
                }
                $checkMember=$this->ReportManagement->checkMemberByMobile($data[3]);
                if($checkMember===false){
                    M()->rollback();
                    $this->error("该手机号码已经是员工");
                }
                //插入导入数据
                $reportData=array(
                        'company_id'=>$companyId,
                        'custom_number'=>$data[1],
                        'name'=>$data[2],
                        'mobile'=>$data[3],
                        'add_time'=>date('YmdHis')
                );
                $memberAddStatus=$this->ReportManagement->memberAdd($reportData);
                if($memberAddStatus===false){
                    M()->rollback();
                    $this->error("第".$row."行插入失败！");
                }
            }
            fclose($handle);
            M()->commit();
            $num=$row-1;
            $this->success( "导入成功，共导入{$num}个报表！");
        }
        fclose($handle);
        unlink($fileUrl);
        $this->error('系统出错');
    }
    /*
     * 初始化近五年的销售任务
     */
    public function initializationFiveYear($reportId){
        $listInfo=$this->ReportManagement->findCompanySetDate($reportId);
        if($listInfo){
            return false;
        }
        $companyList=$this->ReportManagement->companyList();
        M()->startTrans();
        foreach($companyList as $key=>$val){
            $reportDate=$this->ReportDate;
            foreach($reportDate as $valDate){
                $resAddStatus=$this->ReportManagement->addCompanySetByDate($val['code'],$valDate,$reportId);
                if($resAddStatus===false){
                    M()->rollback();
                    return false;
                }
            }
        }
        M()->commit();
    }
    public function read($filename,$encode='utf-8'){
        import('@.Vendor.PHPExcel', '', '.php');
        $objReader = PHPExcel_IOFactory::createReader('Excel5');
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($filename);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
        $excelData = array();
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $excelData[$row][] =(string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
        }
        return $excelData;
    }
    public function excelTime($date, $time = false) {
        if(function_exists('GregorianToJD')){
            if (is_numeric($date)) {
                $jd = GregorianToJD( 1, 1, 1970 );
                $gregorian = JDToGregorian( $jd + intval ( $date ) - 25569 );
                $date = explode( '/', $gregorian );
                $date_str = str_pad( $date [2], 4, '0', STR_PAD_LEFT )
                        . str_pad( $date [0], 2, '0', STR_PAD_LEFT )
                        . str_pad( $date [1], 2, '0', STR_PAD_LEFT )
                        . ($time ? " 00:00:00" : '');
                return $date_str;
            }
        }else{
            $date=$date>25568?$date+1:25569;
            /*There was a bug if Converting date before 1-1-1970 (tstamp 0)*/
            $ofs=(70 * 365 + 17+2) * 86400;
            $date =  date("Ymd",($date * 86400) - $ofs).($time ? " 00:00:00" : '');
        }
        return $date;
    }
    private function exportExcel($data, $fileName) {
        import('@.Vendor.PHPExcel', '', '.php');
        // 实例化
        $objPHPExcel = new PHPExcel();
        // 设置文档属性
        $objPHPExcel->getProperties()
                ->setCreator("wangcaio2o")
                ->setLastModifiedBy("report")
                ->setTitle("report")
                ->setSubject("report")
                ->setDescription("report")
                ->setKeywords("report")
                ->setCategory("report");

        // 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
        $objSheet = $objPHPExcel->getActiveSheet();
        // 获得array数据的key
        $col_arr = array_keys($data[0]);
        // 预定的标题数组
        $tmp_arr = array(
                'id' => 'id',
                'company_name' => '分公司',
                'year_salse' => '销售',
                'year_retail' => '其中：零售',
                'year_rate' => '零售率',
                'year_sales_complete' => '销售完成计划率',
                'year_retail_complete' => '零售完成计划率',
                'total_month' => '合计月',
                'month_retail' => '其中：零售',
                'month_retail_rate' => '零售率',
                'day_sales' => '销售(日)',
                'day_retail' => '零售（日）',
                'day_retail_rate' => '零售率（日）',
                'company_id' => '分公司代码',
                'date' => '日期'
        );
        $exc_arr = array(
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
                '14' => 'N');
        // 获得数据库列名和excel列名的对应数组 并写入excel的表头
        foreach ($col_arr as $k => &$v) {
            $v = $tmp_arr[$v];
            $objSheet->setCellValueExplicit($exc_arr[$k + 1] . '2', $v, PHPExcel_Cell_DataType::TYPE_STRING);
            $objSheet->getStyle($exc_arr[$k + 1] . '2')
                    ->getFont()
                    ->setBold(true); // 加粗
        }
        // 写入数据
        $data_count = count($data); // 数据行数
        foreach ($data as $dk => $dv) {
            $curr_row = $dk + 3; // 首行备注置空 数组从1开始 加上表头的1行 数据是从A3开始写入
            // 每行进行循环
            $dv = array_values($dv);
            foreach ($dv as $kk => $vv) {
                $objSheet->setCellValueExplicit($exc_arr[$kk + 1] . $curr_row,
                        $vv, PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        // setCellValueExplicit 显式指定内容类型
        $objSheet->getDefaultColumnDimension()->setWidth(15); // 设置所有列默认宽度
        $objSheet->setTitle('甘肃石油-导入数据下载数据');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $filename = "$fileName";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
        exit();
    }
    public function down_xls($data, $keynames,$oneway, $name='dataxls') {
        $xls[] = "<html><meta http-equiv=content-type content=\"text/html; charset=UTF-8\"><body><table border='1'>";
        $xls[] = "<tr><td>ID</td><td>" . implode("</td><td>", array_values($keynames)) . '</td></tr>';
        foreach($data As $o) {
            $line = array(++$index);
            foreach($keynames AS $k=>$v) {
                $line[] = $o[$k];
            }
            $xls[] = '<tr><td>'. implode("</td><td>", $line) . '</td></tr>';
        }
        if($oneway)  $xls[] = '<tr><td colspan="'.count($line).'" align="center">'.$oneway.'</td></tr>';
        $xls[] = '</table></body></html>';
        $xls = join("\r\n", $xls);
        header("Content-type:application/vnd.ms-excel");
        header('Content-Disposition: attachment; filename="'.$name.'.xls"');
        die(mb_convert_encoding($xls,'UTF-8','UTF-8'));
    }

    public function whiteList()
    {
        $this->assign('whiteListFlag','open2');
        $mapcount=M('tfb_gansu_white_import')->count();
        import('ORG.Util.Page');// 导入分页类
        $Page = new Page($mapcount, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();// 分页显示输出
        $whiteInfoList = M('tfb_gansu_white_import')
            ->order('add_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $whiteListNum=M('tfb_phone')->where(array('node_id'=>$this->node_id,'batch_id'=>C('gssy.batch_id')))->count();
        $this->assign('whitenumber', $whiteListNum);
        $this->assign('whiteInfoList',$whiteInfoList);
        $this->assign('page',$show);
        $this->display();
    }

    public function whiteListUpload($operation)
    {
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 150;
        $upload->allowExts = array(
            "txt");
        $upload->savePath = APP_PATH . '/Upload/'; // 设置附件
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        if (! $upload->upload()) { // 上传错误提示错误信息
            $errormsg = $upload->getErrorMsg();
            $this->error($errormsg);
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            $fileUrl = $info[0]['savepath'] . $info[0]['savename'];
        }

        //记录文件信息
        if($info[0]['size']==0)
            $this->error('文件内容为空，请检查文件');
        $whiteImport=array('total_num'=>0,'succ_num'=>0,'fail_num'=>0,'add_time'=>date('YmdHis'),'path'=>$fileUrl,'operation'=>$operation);
        $importId=M('tfb_gansu_white_import')->add($whiteImport);
        if($importId === false){
            $this->error('文件上传失败！请重试');
        }

        $myfile = fopen("$fileUrl", "r") or die("Unable to open file!");
        $row=0;
        $batch_id=C('gssy.batch_id');
        $succNum=0;
        $failNum=0;
        $errData = ""; // 数据有误
        $errorMsg = '';

        while(!feof($myfile)) {

            if($row++ > 10000){
                $errorMsg = '上传文件不能超过10000行！';
                break;
            }

            $phone = fgets($myfile);
            if (preg_match("/^1[73458]{1}\d{9}$/", trim($phone)) != 1) {
                $failNum++;
                if (empty($errData)) {
                    $errData = $row;
                } else {
                    $errData .= "、" . $row;
                }
                continue;
            }

            $whiteList = array('node_id' => $this->node_id, 'batch_id' => $batch_id,'batch_type'=>'3', 'mobile' => $phone,'operation_type'=>$operation, 'import_id'=>$importId);
            $result = M('tfb_gansu_import_detail')->add($whiteList);
            if ($result) {
                $succNum++;
            } else {
                $failNum++;
                continue;
            }
        }
        fclose($myfile);

        if (! empty($errData)) {
            if (empty($errMsg)) {
                $errorMsg = '文件中的';
            }

            if( strlen($errData) > 300 )

                $errData = mb_substr($errData,0,300,'utf-8')."...";

            $errorMsg.= $errData . "行数据操作失败，错误原因：手机号码错误;请校正后重新上传";

        }

        if($errorMsg != ''){
            M('tfb_gansu_white_import')->delete($importId);
            M('tfb_gansu_import_detail')->where(array("import_id"=>$importId))->delete();
            @unlink($fileUrl);
            $this->error($errorMsg,null,true);
        }

        $importData=array('total_num'=>$row,'succ_num'=>$succNum,'fail_num'=>$failNum);
        $flag=M('tfb_gansu_white_import')->where(array('id'=>$importId))->save($importData);
        if($flag === false){
            log_write('tfb_gansu_white_import update error!');
        }

        $this->success("导入成功，共导入{$succNum}个会员.",null,true);
    }








}