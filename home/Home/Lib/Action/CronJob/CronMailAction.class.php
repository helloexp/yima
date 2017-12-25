<?php

/**
 * 线上提领,邮件通知. Author: Zhaobl Date: 2015/10/20
 */
class CronMailAction extends Action {

    private $model;

    function mailInform() {
        $delivery = C('sawanjingu.node_id'); // 订单发货商户号
        $petname = C('sawanjingu.send_mail'); // 收件人1
        $cc = C('sawanjingu.send_mail2'); // 收件人2
        
        if (empty($this->model)) {
            $this->model = D('TonlineGetOrder');
        }
        $status = $this->model->index($delivery);
        
        if ($status) {
            $mailContent['petname'] = $petname;
            $mailContent['CC'] = $cc;
            $mailContent['test_title'] = '卡券线上提领申请';
            $mailContent['text_content'] = '您的卡券“萨湾金谷大米（卡券名称）”已被线上提领。请尽快登陆旺财，在“卡券管理-卡券数据-明细数据-线上提领明细”查看并发货。';
            $flag = to_email($mailContent); // 发送邮件，成功返回2,
            
            if ($flag == 2) {
                echo "发送成功";
            } else {
                echo "发送失败";
            }
        }
    }

    public function chanJiFb() {
        $weekArray = array(
            '日', 
            '一', 
            '二', 
            '三', 
            '四', 
            '五', 
            '六');
        $chDate = date('Y年m月d日', strtotime('1 days ago'));
        $chWeek = $weekArray[date('w', strtotime('-1 days'))];
        $emailContent = array();
        $emailContent['petname'] = 'lish@imageco.com.cn';
        $emailContent['CC'] = 'fuyao@imageco.com.cn';
        $emailContent['test_title'] = '【翼码提示】' . $chDate . '的订单列表已产出，请及时处理。';
        
        $yesterdayDate = date('Ymd', strtotime('1 days ago')) . '000000';
        $endDate = date('Ymd') . '000000';
        $where = " ttoi.node_id = '00029535' AND ttoi.pay_status = '2' AND ttoi.add_time >= '" .
             $yesterdayDate . "' AND ttoi.add_time < '" . $endDate . "'";
        
        $field = "ttoi.order_id, CASE ttoi.pay_channel WHEN '1' THEN '支付宝' WHEN '2' THEN '银行卡' WHEN '3' THEN '微信' WHEN '4' THEN '货到付款' END pay_channel, ttoi.add_time, ttoi.pay_time, CASE ttoi.receiver_type WHEN '0' THEN '自提' WHEN '1' THEN '快递' END receiver_type, ttoi.freight, ttoi.memo, ttoi.receiver_name, tcc.province, tcc.city, tcc.town, ttoi.receiver_addr, ttoi.receiver_tel,ttoi.receiver_phone, ttoi.receiver_post, CASE ttoi.pay_channel WHEN '4' THEN '是' ELSE '否' END pay_type, ttoi.order_amt as total_goods_money, ttoi.order_amt, twu.nickname, CASE ttoi.other_type WHEN '0' THEN '标准订单' WHEN '1' THEN '订购订单' END order_type";
        
        $sql = "SELECT " . $field . " FROM ttg_order_info ttoi" .
             " LEFT JOIN tcity_code tcc ON tcc.path = ttoi.receiver_citycode ";
        $sql .= " LEFT JOIN twx_user twu ON twu.openid = ttoi.openId WHERE" .
             $where . " GROUP BY ttoi.order_id";
        
        $cols_arr = array();
        $cols_arr['order_id'] = '*:订单号';
        $cols_arr['pay_channel'] = '*:支付方式';
        $cols_arr['add_time'] = '*:下单时间';
        $cols_arr['pay_time'] = '*:付款时间';
        $cols_arr['receiver_type'] = '*:配送方式';
        $cols_arr['freight'] = '*:配送费用';
        
        $cols_arr['shop_name'] = '*:来源店铺编号';
        $cols_arr['memo'] = '*:客户备注';
        $cols_arr['receiver_name'] = '*:收货人姓名';
        $cols_arr['province'] = '*:收货地址省份';
        $cols_arr['city'] = '*:收货地址城市';
        
        $cols_arr['town'] = '*:收货地址区/县';
        $cols_arr['receiver_addr'] = '*:收货详细地址';
        $cols_arr['receiver_tel'] = '*:收货人固定电话';
        $cols_arr['email'] = '*:电子邮箱';
        $cols_arr['receiver_phone'] = '*:收货人移动电话';
        
        $cols_arr['receiver_post'] = '*:邮编';
        $cols_arr['pay_type'] = '*:货到付款';
        $cols_arr['invoice'] = '*:是否开发票';
        $cols_arr['invoice_title'] = '*:发票抬头';
        $cols_arr['invoice_tag'] = '*:税金(非开票总额)';
        
        $cols_arr['preferential_scheme'] = '*:优惠方案';
        $cols_arr['order_discount_amount'] = '*:订单优惠金额';
        $cols_arr['goods_discount_amount'] = '*:商品优惠金额';
        $cols_arr['discount'] = '*:折扣';
        $cols_arr['interger'] = '*:返点积分';
        
        $cols_arr['total_goods_money'] = '*:商品总额';
        $cols_arr['order_amt'] = '*:订单总额';
        $cols_arr['buyer_name'] = '*:买家会员名';
        $cols_arr['order_type'] = '*:订单类型';
        $cols_arr['saler_memo'] = '*:商家备注';
        
        $cols_arr['goods_weight'] = '*:商品重量';
        $cols_arr['invoice_num'] = '*:发票号';
        $cols_arr['cycle_buy'] = '*:周期购';
        
        set_magic_quotes_runtime(0);
        $data = M()->query($sql);
        $orderCount = count($data);
        $emailContent['text_content'] = "<html>
            <head></head>
            <body>
            <p>{$chDate}（星期{$chWeek}），翼码旺财为您卖出{$orderCount}笔订单。</p>
            <p>附件为昨日订单列表，请及时处理。</p>
            <br /><br /><br />
            <img src='http://test.wangcaio2o.com/Home/Public/Image/mailLogo.gif' />
            <p>翼码信息 中国电子凭证行业领导者 & 专业运营服务商</p>
            </body>
            </html>";
        if (empty($data)) {
            $emailContent['text_content'] = date('Y-m-d') . '暂无订单产生。';
        } else {
            $contents = '
                <?xml version="1.0" encoding="utf8"?>
                <?mso-application progid="Excel.Sheet"?>
                <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
                 xmlns:o="urn:schemas-microsoft-com:office:office"
                 xmlns:x="urn:schemas-microsoft-com:office:excel"
                 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
                 xmlns:html="http://www.w3.org/TR/REC-html40">
                 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
                  <Author>IMAGECO</Author>
                  <LastAuthor>IMAGECO DEVELOP</LastAuthor>
                  <Created>' . date('YmdHis') . '</Created>
                  <Company>IMAGECO</Company>
                  <Version>11.9999</Version>
                 </DocumentProperties>
                 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
                  <WindowHeight>9450</WindowHeight>
                  <WindowWidth>16020</WindowWidth>
                  <WindowTopX>0</WindowTopX>
                  <WindowTopY>60</WindowTopY>
                  <ProtectStructure>False</ProtectStructure>
                  <ProtectWindows>False</ProtectWindows>
                 </ExcelWorkbook>
                 <Styles>
                  <Style ss:ID="Default" ss:Name="Normal">
                   <Alignment ss:Vertical="Center"/>
                   <Borders/>
                   <Font ss:FontName="宋体" x:CharSet="134" ss:Size="12"/>
                   <Interior/>
                   <NumberFormat/>
                   <Protection/>
                  </Style>
                  <Style ss:ID="top">
                   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                   <Interior ss:Color="#C0C0C0" ss:Pattern="Solid"/>
                  </Style>
                 </Styles>
                ';
            $contents .= '<Worksheet ss:Name="Sheet1">' . "\n" .
                 '<Table ss:ExpandedColumnCount="1000" ss:ExpandedRowCount="70000" x:FullColumns="1" x:FullRows="1" ss:DefaultColumnWidth="54" ss:DefaultRowHeight="14.25">' .
                 "\n";
            
            $contents .= '<Row><Cell><Data ss:Type="String">' .
                 implode("</Data></Cell>\n<Cell><Data ss:Type=\"String\">", 
                    $cols_arr) . '</Data></Cell></Row>';
            
            foreach ($data as $val) {
                array_splice($val, 6, 0, 'YZ002');
                array_splice($val, 14, 0, '');
                for ($i = 18; $i < 34; $i ++) {
                    if (($i > 17 && 26 > $i) || (29 < $i && $i < 34)) {
                        array_splice($val, $i, 0, '');
                    }
                }
                $val['add_time'] = '';
                $val['pay_time'] = '';
                
                $contents .= "\n" . '<Row><Cell><Data ss:Type="String">' . implode(
                    "</Data></Cell>\n<Cell><Data ss:Type=\"String\">", $val) .
                     '</Data></Cell></Row>';
            }
            
            $field = "ttoi.order_id, tgi.customer_no, ttoie.b_name, ttoie.ecshop_sku_desc, ttoie.goods_num, tgi.market_price, ttoie.amount";
            
            $sql = "SELECT " . $field . " FROM ttg_order_info ttoi" .
                 " LEFT JOIN tcity_code tcc ON tcc.path = ttoi.receiver_citycode";
            $sql .= " LEFT JOIN ttg_order_info_ex ttoie ON ttoie.order_id = ttoi.order_id ";
            $sql .= " LEFT JOIN tbatch_info tbi ON tbi.id = ttoie.b_id ";
            $sql .= " LEFT JOIN tgoods_info tgi ON tgi.goods_id = tbi.goods_id WHERE " .
                 $where;
            
            $cols_arr = array();
            $cols_arr['order_id'] = '*:订单号';
            $cols_arr['customer_no'] = '*:商品货号';
            $cols_arr['b_name'] = '*:商品名称';
            $cols_arr['buy_company'] = '*:购买单位';
            $cols_arr['ecshop_sku_desc'] = '*:商品规格';
            $cols_arr['goods_num'] = '*:购买数量';
            $cols_arr['goods_amt'] = '*:商品原价';
            $cols_arr['amount'] = '*:销售价';
            
            $contents .= '<Row><Cell><Data ss:Type="String">' .
                 implode("</Data></Cell>\n<Cell><Data ss:Type=\"String\">", 
                    $cols_arr) . '</Data></Cell></Row>';
            $data = array();
            $data = M()->query($sql);
            foreach ($data as $val) {
                array_splice($val, 3, 0, '');
                $contents .= "\n" . '<Row><Cell><Data ss:Type="String">' . implode(
                    "</Data></Cell>\n<Cell><Data ss:Type=\"String\">", $val) .
                     '</Data></Cell></Row>';
            }
            
            $contents .= "\n" . '</Table>
                <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
                </WorksheetOptions>
                </Worksheet>
                </Workbook>';
            
            define('TEMPS_PATH', C('DOWN_TEMP'));
            $tmpfilename = "chenji.xls";
            $tmpfilename = TEMPS_PATH . $tmpfilename;
            $fp = fopen($tmpfilename, 'wb');
            if (! $fp) {
                return false;
            }
            
            fwrite($fp, $contents);
            $contents = '';
            fclose($fp);
            
            $emailContent['add_file'] = $tmpfilename;
        }
        
        if (to_email($emailContent) == '2') {
            unlink($tmpfilename);
        }
    }
}