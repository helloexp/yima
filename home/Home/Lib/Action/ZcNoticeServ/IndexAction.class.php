<?php

/* 主动通知接口 */
class IndexAction extends Action {
    public $ReqArr;
    public $transType;

    public $responseType;

    public $channel_id;

    public $b_id;

    public $store_id;

    public $pos_node_id;

    public $phone;

    public function index() {
        $reqXml = file_get_contents('php://input');
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml]导入失败');
        $xml = new Xml();
        $this->log($reqXml, 'REQUEST');
        $this->ReqArr = $xml->parse($reqXml);
        $this->transType = $xml->getRootName();
        $this->pingAnCheck($reqXml);
        if ($this->transType == 'VerifySyncReq') { // 验证同步
            $this->responseType = 'VerifySyncRes';
            $verify_info = $this->ReqArr['VerifySyncReq'];
            $this->verifysync($verify_info);
        } else if ($this->transType == 'VerifyInfoSyncReq') { // 验证数据同步
            $this->responseType = 'VerifyInfoSyncRes';
            $verify_info = $this->ReqArr['VerifyInfoSyncReq'];
            $this->verifysync($verify_info);
        } else if ($this->transType == 'DeliverReportSyncReq') { // 递送同步
            $this->responseType = 'DeliverReportSyncRes';
            $this->deleverreportsync();
        } else if ($this->transType == 'NodeSyncReq') { // 机构同步
            $this->responseType = 'NodeSyncRes';
            $this->nodesync();
        } else if ($this->transType == 'PosSyncReq') { // 终端同步
            $this->responseType = 'PosSyncRes';
            $this->possync();
        } else if ($this->transType == 'StoreSyncReq') { // 门店同步
            $this->responseType = 'StoreSyncRes';
            $this->storesync();
        } else if ($this->transType == 'DeliverInfoSyncReq') { // 发码同步
            $this->responseType = 'DeliverInfoSyncRes';
            $this->deliverinfosync();
        } else {
            $this->responseType = 'ErrorRes';
            $this->notifyreturn('1000');
        }
    }

    private function pingAnCheck($reqXml) {
        $nodeId = '';
        switch($this->transType){
            case 'VerifySyncReq':
            case 'VerifyInfoSyncReq':
            case 'DeliverReportSyncReq':
            case 'DeliverInfoSyncReq':
                $nodeId = $this->ReqArr[$this->transType]['ISSPID'];
                break;
            case 'PosSyncReq':
            case 'StoreSyncReq':
                $nodeId = $this->ReqArr[$this->transType]['NodeId'];
                break;
            default :
                return ;
        }
        if ($nodeId === C('pingan.node_id')) {
            $url =C('pingan.zc_pingan_tz');
            $timeout = '30';
            log_write("进行http,post");
            die(httpPost($url, $reqXml, $error, array('TIMEOUT' => $timeout)));
        }
    }
    // 发码同步
    private function deliverinfosync() {
        $deliver_info = $this->ReqArr['DeliverInfoSyncReq'];
        $where = "req_seq ='" . $deliver_info['SpSeq'] . "'";
        $barcode_trace = M('tbarcode_trace')->where($where)->find();
        if ($barcode_trace) {
            $this->log("流水已同步...");
            $this->notifyreturn();
        }
        // 查找tbatch_info
        $c_where = "NODE_ID ='" . $deliver_info['ISSPID'] . "' and batch_no ='" .
             $deliver_info['ActivityID'] . "'";
        $activity_info = M('TbatchInfo')->where($where)->find();
        if (! $activity_info) {
            // 未找到查找tgoods_info
            $where = "NODE_ID ='" . $deliver_info['ISSPID'] . "' and batch_no ='" .
                 $deliver_info['ActivityID'] . "'";
            $goods_info = M('TgoodsInfo')->where($where)->find();
            if (! $goods_info) {
                $this->log(print_r($barcode_trace, true));
                $this->log("旺财上未找到对应的活动号");
                $this->notifyreturn('1009');
            } else {
                $barcode_trace['goods_id'] = $goods_info['goods_id'];
            }
        } else {
            $barcode_trace['b_id'] = $activity_info['id'];
            $barcode_trace['goods_id'] = $activity_info['goods_id'];
        }
        // 开启事务
        M()->startTrans();
        // 插入流水表
        $barcode_trace['req_seq'] = $deliver_info['SpSeq'];
        $barcode_trace['sys_seq'] = $deliver_info['ReqSeq'];
        $barcode_trace['request_id'] = $deliver_info['SpSeq'];
        $barcode_trace['node_id'] = $deliver_info['ISSPID'];
        $barcode_trace['batch_no'] = $deliver_info['ActivityID'];
        $barcode_trace['phone_no'] = $deliver_info['PhoneNo'];
        $barcode_trace['trans_time'] = $deliver_info['TraceTime'];
        $barcode_trace['ret_code'] = '0000';
        $barcode_trace['ret_desc'] = '发码成功';
        $barcode_trace['trans_type'] = '0001';
        $barcode_trace['status'] = '0';
        $barcode_trace['data_from'] = 'Z'; // 支撑同步
        $barcode_trace['begin_time'] = $deliver_info['BeginTime'];
        $barcode_trace['end_time'] = $deliver_info['EndTime'];
        $barcode_trace['price'] = $deliver_info['OrgAmt'];
        $barcode_trace['pact_price'] = $deliver_info['PactPrice'];
        $barcode_trace['valid_times'] = $deliver_info['OrgTimes'];
        
        $rs = M('TbarcodeTrace')->add($barcode_trace);
        if ($rs === false) {
            M()->rollback();
            $this->log(print_r($barcode_trace, true));
            $this->log("记录流水信息[tbarcode_trace]失败");
            $this->notifyreturn('1001');
        }
        // 计入统计表
        // 第六步，记录发码统计
        $where = "NODE_ID ='" . $deliver_info['ISSPID'] . "' and BATCH_NO ='" .
             $deliver_info['ActivityID'] . "' and TRANS_DATE ='" . date('Y-m-d') .
             "' and POS_ID ='0000000000' and b_id = 0";
        $pos_day_count = M('TposDayCount')->where($where)->find();
        if (! $pos_day_count) {
            $pos_day_count['node_id'] = $deliver_info['ISSPID'];
            $pos_day_count['batch_no'] = $deliver_info['ActivityID'];
            $pos_day_count['trans_date'] = date('Y-m-d');
            $pos_day_count['send_num'] = 1;
            $pos_day_count['send_amt'] = $deliver_info['OrgAmt'];
            $pos_day_count['verify_num'] = 0;
            $pos_day_count['verify_amt'] = 0;
            $pos_day_count['cancel_num'] = 0;
            $pos_day_count['cancel_amt'] = 0;
            $pos_day_count['goods_id'] = $activity_info['goods_id']; // ?
            $pos_day_count['pos_id'] = "0000000000";
            $pos_day_count['b_id'] = 0;
            
            $rs = M('TposDayCount')->add($pos_day_count);
            if (! $rs) {
                M()->rollback();
                $this->log(print_r($pos_day_count, true));
                $this->log("记录统计信息[tpos_day_count]失败");
                $this->notifyreturn('1002');
            }
        } else {
            $new_day_count = array();
            $new_day_count['send_num'] = $pos_day_count['send_num'] + 1;
            $new_day_count['send_amt'] = $pos_day_count['send_amt'] +
                 $deliver_info['OrgAmt'];
            $rs = M('TposDayCount')->where($where)->save($new_day_count);
            if ($rs === false) {
                M()->rollback();
                $this->log(print_r($new_day_count, true));
                $this->log("更新统计信息[tpos_day_count]失败");
                $this->notifyreturn('1003');
            }
        }
        // 结束事务返回
        M()->commit(); // 提交事务
        $this->notifyreturn();
    }
    
    // 验证同步
    private function verifysync($verify_info) {
        // 二、查终端信息
        $where = "pos_id ='" . $verify_info['TerminalId'] . "'  ";
        $pos_info = M('TposInfo')->where($where)->find();
        if (! $pos_info) {
            $this->log("未找到终端[" . $verify_info['TerminalId'] . "]");
            // $this->notifyreturn();
        }
        
        $this->pos_node_id = $pos_info['node_id'];
        $this->store_id = $pos_info['store_id'];
        
        $verify_info['Amt'] = $verify_info['Amt'] / 100;
        $verify_info['CouponFee'] = $verify_info['CouponFee'] / 100;
        $verify_info['CouponRefundFee'] = $verify_info['CouponRefundFee'] / 100;
        $verify_info['McardFee'] = $verify_info['McardFee'] / 100;
        $verify_info['MdiscountFee'] = $verify_info['MdiscountFee'] / 100;
        $verify_info['McouponFee'] = $verify_info['McouponFee'] / 100;
        $verify_info['PointFee'] = $verify_info['PointFee'] / 100;
        $verify_info['DiscountFee'] = $verify_info['DiscountFee'] / 100;
		
		$verify_info['SysRetDesc'] = iconv("gbk", "utf-8", $verify_info['SysRetDesc']);

		//5002-	支付宝 5003-微信 5004-翼支付 5005-QQ钱包 5006-银联钱包 5007-和包支付	
		// 点评 平台号：5172   百度糯米 平台号：5173
        if ($verify_info['SpareField1'] == '5002') {
			$verify_info['code_type'] = '100';
		} else if ($verify_info['SpareField1'] == '5003') {
			$verify_info['code_type'] = '101';
		} else if ($verify_info['SpareField1'] == '5004') {
			$verify_info['code_type'] = '102';
		} else if ($verify_info['SpareField1'] == '5005') {
			$verify_info['code_type'] = '105';
		} else if ($verify_info['SpareField1'] == '5006') {
			$verify_info['code_type'] = '106';
		} else if ($verify_info['SpareField1'] == '5007') {
			$verify_info['code_type'] = '107';
		} else if ($verify_info['SpareField1'] == '5172') {
			$verify_info['code_type'] = '2';
		} else if ($verify_info['SpareField1'] == '5173') {
			$verify_info['code_type'] = '1';
		} 
		
		// 0204--支付 0205--退款 0206--撤销
		if ($verify_info['TransType'] == '0204') {
			$this->pay_notifyreturn($verify_info); // 支付
		} else if ($verify_info['TransType'] == '0205') {
			$this->pay_r_notifyreturn($verify_info); // 退款
		} else if ($verify_info['TransType'] == '0206') {
			$this->pay_c_notifyreturn($verify_info); // 撤销
		} else if ($verify_info['TransType'] =='0402' or $verify_info['TransType'] == '0403') { 
			$this->group_buy_notifyreturn($verify_info); // 支付
		}
            
        // zhengxh 2014-9-3
        // 调整顺序，先把条码的goods_id先查出来后，放入$this->wpos_notifyreturn($verify_info) 进行更新
        // 三、查活动信息 查GOODS_ID
        $where = "req_seq ='" . $verify_info['SpSeq'] . "'";
        $barcode_info = M('TbarcodeTrace')->where($where)->find();
        if (! $barcode_info) {
            $this->log("未找到委托流水[" . $verify_info['SpSeq'] . "]");
            // $this->notifyreturn();
        }
        $this->channel_id = $barcode_info['channel_id'];
        $this->b_id = $barcode_info['b_id'];
        $this->phone = $barcode_info['phone_no'];
        if ($this->b_id == null)
            $this->b_id = 0;
            // 微信卡券特殊处理
        if ($barcode_info['wx_open_id'] != null) {
            // 根据交易类型做处理 //判断状态是否已使用
            if (($verify_info['TransType'] == '0001') &&
                 ($verify_info['Status'] == '3')) { // 验证交易
                                                   // 核销操作
                $wx_card = D('WeiXinCard', 'Service');
                $wx_card->init_by_node_id($barcode_info['node_id']);
                $result = $wx_card->card_consume($barcode_info['goods_id'], 
                    $barcode_info['assist_number']);
                $this->log(
                    "wx_card_consume[" . $barcode_info['goods_id'] . "][" .
                         $barcode_info['assist_number'] . "][" .
                         print_r($result, true) . "]");
            }
        }
        
        $verify_info['goods_id'] = $barcode_info['goods_id'];
        // 查找是否和包数据
        $cmpayTrace = M('tbarcode_trace_exp_cmpcard')->where(
            "barcode_trace_id = {$barcode_info['id']}")->find();
        if ($cmpayTrace) {
            $cmpayService = D('CMPAY', 'Service');
            $ret = $cmpayService->cmpayUseCard(
                $verify_info['TerminalId'] . $verify_info['TerminalSeq'], 
                $barcode_info['assist_number']);
        }
        if ($pos_info['pos_type'] == '0') { // 旺财终端
             // $this->log("终端[".$verify_info['TerminalId']."]为wpos");
            $this->wpos_notifyreturn($verify_info); // 旺财终端同步处理
        }
        
        
        $this->zcpt_notifyreturn($verify_info); // 同步处理
    }

    // 渠道表验证数统计 $type 1 加1 $type 2 减少
    private function verify_channel_stat($channel_id, $type) {
        if ($channel_id != null) {
            $where = "id = " . $channel_id;
            if ($type == 1) {
                $rs = M('tchannel')->where($where)->setInc('verify_count');
                if ($rs === false) {
                    $this->log("增加验证统计数失败[" . M()->_sql() . "]");
                }
                // 增加 tday_stat 统计数
                if ($this->b_id != null) {
                    $batch_info = M('tbatch_info')->where("id = " . $this->b_id)->field(
                        'm_id, node_id')->find();
                    $marketing_info = M('tmarketing_info')->where(
                        "id = " . $batch_info['m_id'])->field('batch_type')->find();
                    // 增加
                    $where = "batch_type = '" . $marketing_info['batch_type'] .
                         "' and batch_id = " . $batch_info['m_id'] .
                         " and channel_id  = " . $channel_id .
                         " and label_id = 0 and parent_id = 0 and  full_id = '0' and day = '" .
                         date('Ymd') . "'";
                    $rs = M('tdaystat')->where($where)->limit(1)->setInc(
                        'verify_count');
                    if (! $rs) {
                        $this->log("增加验证统计数tday_stat失败[" . M()->_sql() . "]");
                        // 失败的时候尝试插入
                        $save_day_stat['batch_type'] = $marketing_info['batch_type'];
                        $save_day_stat['batch_id'] = $batch_info['m_id'];
                        $save_day_stat['channel_id'] = $channel_id;
                        $save_day_stat['day'] = date('Ymd');
                        $save_day_stat['full_id'] = '0';
                        $save_day_stat['node_id'] = $batch_info['node_id'];
                        $save_day_stat['verify_count'] = 1;
                        $rs = M('tdaystat')->add($save_day_stat);
                        if ($rs === false) {
                            $this->log(
                                "增加保存验证统计数tday_stat失败[" . M()->_sql() . "]");
                        }
                    }
                }
            } else if ($type == 2) {
                $rs = M('tchannel')->where($where)->setDec('verify_count');
                if ($rs === false) {
                    $this->log("减少验证统计数失败[" . M()->_sql() . "]");
                }
            }
        }
    }
    
    // 递送同步
    private function deleverreportsync() {
        $this->notifyreturn();
    }
    
    // 机构同步
    private function nodesync() {
        $node_sync = $this->ReqArr['NodeSyncReq'];
        $sync_type = $node_sync['SyncType'];

        $where = "node_id ='" . $node_sync['NodeId'] . "'";
        $node_rs = M('TnodeInfo')->where($where)->find();
        // print_r($node_sync);

        $node_info                    = array();
        $node_info['node_id']         = $node_sync['NodeId'];
        $node_info['node_name']       = iconv(
                "gbk",
                "utf8",
                $node_sync['NodeName']
        );
        $node_info['node_short_name'] = iconv(
                "gbk",
                "utf8",
                $node_sync['NodeShortName']
        );
        $node_info['node_citycode']   = $node_sync['CityCode'];
        $node_info['node_addr']       = iconv(
                "gbk",
                "utf8",
                $node_sync['NodeAddr']
        );
        $node_info['contact_name']    = iconv(
                "gbk",
                "utf8",
                $node_sync['ContactName']
        );
        $node_info['contact_phone']         = $node_sync['ContactPhone'];
        $node_info['contact_tel']           = $node_sync['ContactTel'];
        $node_info['contact_eml']           = $node_sync['ContactEmail'];
        $node_info['status']                = $node_sync['Status'];
        $node_info['sign_time']             = $node_sync['AddTime'];
        $node_info['add_time']              = $node_sync['AddTime'];
        $node_info['parent_id']             = $node_sync['ParentId'];
        $node_info['full_id']               = $node_sync['NodePath'];
        $node_info['node_level']            = (strlen($node_sync['NodePath']) + 1) / (strlen($node_sync['NodeId']) + 1);
        $node_info['custom_sms_flag']       = $node_sync['CustomSmsFlag'];

        // 子机构需要补充根机构的描述信息，非一级机构忽略
        $node_root_id                 = substr($node_sync['NodePath'], 0, strlen($node_sync['NodeId']));
        $where                        = "node_id = '" . $node_root_id . "'";
        if (! $node_rs && $node_info['node_level'] > 1) {
                $node_root                    = M('TnodeInfo')->where($where)->find();

                $node_info['client_id']       = $node_root['client_id'];
                $node_info['contract_no']     = $node_root['contract_no'];
                $node_info['applyfee_id']     = $node_root['applyfee_id'];
                $node_info['trade_type']      = $node_root['trade_type'];
                $node_info['node_type']       = $node_root['node_type'];
                $node_info['node_license_id'] = $node_root['node_license_id'];
                $node_info['node_law_name']   = $node_root['node_law_name'];
                $node_info['node_law_id']     = $node_root['node_law_id'];
        }

        if ($sync_type == '0') { // 新增
            $rs = M('TnodeInfo')->Add($node_info); // 只有非1级别机构才新增
            if (!$rs) {
                $this->log("机构同步新增[" . $node_sync['NodeId'] . "]异常");
                $this->notifyreturn('1002');
            }
        }
        if ($sync_type == '1') { // 编辑
            $node_info['update_time'] = $node_sync['UpdateTime'];
            if (! $node_rs) { // 不存在的编辑 转为新增
                    $rs = M('TnodeInfo')->Add($node_info); // 只有非1级别机构才新增
                    if (! $rs) {
                        $this->log("机构同步新增[" . $node_sync['NodeId'] . "]异常");
                        $this->notifyreturn('1002');
                    }
            } else {
                if($node_rs['custom_sms_flag'] == 0 && $node_info['custom_sms_flag'] == 1){    //对已存在的商户首次开通,清空字段值
                    //目前无视子商户，如果开通的话只在住商户的node_id上开通，子商户等待下次提单子再做
                    $isOk = M('tbatch_info')->where((array('node_id'=>$node_info['node_id'])))->save(array('sms_text'=>''));
                    if($isOk === false){
                        $this->log("机构开通自定义短信失败[" . $node_sync['NodeId'] . "]异常");
                        $this->notifyreturn('1006');
                    }
                }
                if($node_rs['custom_sms_flag'] == 1 && $node_info['custom_sms_flag'] == 0){    //对已开通过的商户进行再次   关闭的时候
                    $node_info['custom_sms_flag'] = 2;
                }
                if($node_rs['custom_sms_flag'] == 2 && $node_info['custom_sms_flag'] == 1){    //对反复开关的商户进行再次   开通
                    $node_info['custom_sms_flag'] = 1;
                }

                $rs = M('TnodeInfo')->where($where)->save($node_info);
                if (! $rs) {
                    $this->log("机构同步更新[" . $node_sync['NodeId'] . "]异常");
                    $this->notifyreturn('1005');
                }
            }
        }else {
            $this->log("机构同步类型[" . $sync_type . "]异常");
            $this->notifyreturn('1001');
        }
        $this->notifyreturn();
    }
    
    // 门店
    private function storesync() {
        $store_sync = $this->ReqArr['StoreSyncReq'];
        $sync_type = $store_sync['SyncType'];
        
        if ($sync_type == '0') { // 新增
            $where = "store_id ='" . $store_sync['StoreId'] . "'";
            $store_rs = M('TstoreInfo')->where($where)->find();
            
            if (! $store_rs) { // 不存在则新增
                               // $this->notifyreturn();//门店新增不再接收
                
                $store_info = array();
                $store_info['store_id'] = $store_sync['StoreId'];
                $store_info['node_id'] = $store_sync['NodeId'];
                $store_info['store_name'] = iconv("gbk", "utf8", 
                    $store_sync['StoreName']);
                $store_info['province_code'] = $store_sync['ProvinceCode'];
                $store_info['city_code'] = $store_sync['CityCode'];
                $store_info['town_code'] = $store_sync['TownCode'];
                $store_info['address'] = iconv("gbk", "utf8", 
                    $store_sync['StoreAddr']);
                $store_info['post_code'] = $store_sync['PostCode'];
                $store_info['principal_name'] = iconv("gbk", "utf8", 
                    $store_sync['PrincipalName']);
                // $store_info['principal_position'] =
                // iconv("gbk","utf8",$store_sync['PrincipalPosition']);
                // $store_info['principal_tel'] = $store_sync['PrincipalTel'];
                $store_info['principal_phone'] = $store_sync['PrincipalPhone'];
                $store_info['store_phone'] = $store_sync['PrincipalTel'];
                $store_info['principal_email'] = $store_sync['PrincipalEmail'];
                $store_info['pos_count'] = $store_sync['PosCount'];
                $store_info['status'] = $store_sync['Status'];
                $store_info['lbs_x'] = $store_sync['Lat'];
                $store_info['lbs_y'] = $store_sync['Lng'];
                $store_info['add_time'] = $store_sync['AddTime'];
                $store_info['store_short_name'] = iconv("gbk", "utf8", 
                    $store_sync['StoreShortName']);
                
                $store_info['pos_range'] = '0';
                if ($store_info['pos_count'] > 0) { // 支撑新增带终端的受理范围都是2-全业务
                    $store_info['pos_range'] = '2';
                }
                
                $rs = M('TstoreInfo')->Add($store_info);
                if (! $rs) {
                    $this->log("机构同步新增[" . $store_info['StoreId'] . "]异常");
                    $this->notifyreturn('1002');
                }
            }
        } else if ($sync_type == '1') { // 编辑
            $where = "store_id ='" . $store_sync['StoreId'] . "'";
            $store_rs = M('TstoreInfo')->where($where)->find();
            
            $store_info = array();
            $store_info['store_id'] = $store_sync['StoreId'];
            $store_info['node_id'] = $store_sync['NodeId'];
            $store_info['store_name'] = iconv("gbk", "utf8", 
                $store_sync['StoreName']);
            $store_info['province_code'] = $store_sync['ProvinceCode'];
            $store_info['city_code'] = $store_sync['CityCode'];
            $store_info['town_code'] = $store_sync['TownCode'];
            $store_info['address'] = iconv("gbk", "utf8", 
                $store_sync['StoreAddr']);
            $store_info['post_code'] = $store_sync['PostCode'];
            $store_info['principal_name'] = iconv("gbk", "utf8", 
                $store_sync['PrincipalName']);
            // $store_info['principal_position'] =
            // iconv("gbk","utf8",$store_sync['PrincipalPosition']);
            // $store_info['principal_tel'] = $store_sync['PrincipalTel'];
            $store_info['principal_phone'] = $store_sync['PrincipalPhone'];
            $store_info['store_phone'] = $store_sync['PrincipalTel'];
            $store_info['principal_email'] = $store_sync['PrincipalEmail'];
            $store_info['status'] = $store_sync['Status'];
            $store_info['pos_count'] = $store_sync['PosCount'];
            $store_info['lbs_x'] = $store_sync['Lat'];
            $store_info['lbs_y'] = $store_sync['Lng'];
            $store_info['add_time'] = $store_sync['AddTime'];
            $store_info['update_time'] = $store_sync['UpdateTime'];
            $store_info['store_short_name'] = iconv("gbk", "utf8", 
                $store_sync['StoreShortName']);
            
            if (! $store_rs) { // 不存在则转为新增
                $store_info['pos_range'] = '0';
                if ($store_info['pos_count'] > 0) { // 支撑新增带终端的受理范围都是2-全业务
                    $store_info['pos_range'] = '2';
                }
                
                $rs = M('TstoreInfo')->Add($store_info);
                if (! $rs) {
                    $this->log("机构同步新增[" . $store_info['StoreId'] . "]异常");
                    $this->notifyreturn('1002');
                }
            } 

            else {
                if ($store_info['pos_count'] > 0 && ! ($store_info['pos_count'] ==
                     1 && $store_rs['pos_range'] == '1')) {
                    $store_info['pos_range'] = '2';
                }
                
                $rs = M('TstoreInfo')->where($where)->save($store_info);
                if ($rs === false) {
                    $this->log("门店同步更新[" . $store_info['StoreId'] . "]异常");
                    $this->notifyreturn('1005');
                }
            }
        } else {
            $this->log("门店同步类型[" . $sync_type . "]异常");
            $this->notifyreturn('1001');
        }
        
        $this->notifyreturn();
    }
    
    // 终端
    private function possync() {
        $pos_sync = $this->ReqArr['PosSyncReq'];
        $sync_type = $pos_sync['SyncType'];
        $pos_type = '9'; // 终端类型转换
		//终端类型：0-6200 1-6600 2-6800 3-webpos 4-9200  5-6900 6-ipos 7-wpos 8-N900

        if (in_array($pos_sync['PosType'], ['0','1','2','4','5','8']) )
        {
            $pos_type = '1'; //实体终端
        } else if (in_array($pos_sync['PosType'], ['3','6'])) {
            $pos_type = '2';
        } else if ($pos_sync['PosType'] == '7') {
            $pos_type = '0';
        }
        
        $pos_status = '0'; // 终端状态转换
        if ($pos_sync['Status'] == '2') {
            $pos_status = '2';
        } else if ($pos_sync['Status'] == '3') {
            $pos_status = '4';
        } else if ($pos_sync['Status'] == '4') {
            $pos_status = '3';
        }
        
        $pay_type = '0';
        $func_type = '2';
        if (in_array($pos_sync['PayerNu'], 
            array(
                "0", 
                "1", 
                "3", 
                "4"))) {
            $pay_type = '1';
            $func_type = '2';
        } elseif ($pos_sync['PayerNu'] == '2') {
            $pay_type = '0';
            $func_type = '2';
        } elseif ($pos_sync['PayerNu'] == '6') {
            $pay_type = '0';
            $func_type = '1';
        }
        
        if ($sync_type == '0') { // 新增
            $where = "pos_id ='" . $pos_sync['PosId'] . "'";
            $pos_rs = M('TposInfo')->where($where)->find();
            
            if (! $pos_rs) { // 不存在则新增
                if ($pos_type == '0') {
                    $this->notifyreturn();
                }
                
                $pos_info = array();
                $pos_info['pos_id'] = $pos_sync['PosId'];
                $pos_info['pos_name'] = iconv("gbk", "utf8", 
                    $pos_sync['PosName']);
                $pos_info['pos_short_name'] = iconv("gbk", "utf8", 
                    $pos_sync['PosShortName']);
                $pos_info['node_id'] = $pos_sync['NodeId'];
                $pos_info['store_id'] = $pos_sync['StoreId'];
                $pos_info['store_name'] = iconv("gbk", "utf8", 
                    $pos_sync['StoreName']);
                $pos_info['pos_type'] = $pos_type;
				$pos_info['zcpt_pos_type'] = $pos_sync['PosType'];
                $pos_info['pos_status'] = $pos_status;
                $pos_info['func_type'] = $func_type;
                $pos_info['pay_type'] = $pay_type;
                $pos_info['add_time'] = $pos_sync['AddTime'];
                
                $rs = M('TposInfo')->Add($pos_info);
                
                if (! $rs) {
                    $this->log("终端同步新增[" . $pos_sync['PosId'] . "]异常");
                    $this->notifyreturn('1002');
                }
            }
        } else if ($sync_type == '1') { // 编辑
            $where = "pos_id ='" . $pos_sync['PosId'] . "'";
            $pos_rs = M('TposInfo')->where($where)->find();
            
            $pos_info = array();
            $pos_info['pos_id'] = $pos_sync['PosId'];
            $pos_info['pos_name'] = iconv("gbk", "utf8", $pos_sync['PosName']);
            $pos_info['pos_short_name'] = iconv("gbk", "utf8", 
                $pos_sync['PosShortName']);
            $pos_info['node_id'] = $pos_sync['NodeId'];
            $pos_info['store_id'] = $pos_sync['StoreId'];
            $pos_info['store_name'] = iconv("gbk", "utf8", 
                $pos_sync['StoreName']);
            $pos_info['pos_type'] = $pos_type;
			$pos_info['zcpt_pos_type'] = $pos_sync['PosType'];
            $pos_info['pos_status'] = $pos_status;
            $pos_info['func_type'] = $func_type;
            $pos_info['pay_type'] = $pay_type;
            $pos_info['add_time'] = $pos_sync['AddTime'];
            $pos_info['update_time'] = $pos_sync['UpdateTime'];
            
            if (! $pos_rs) { // 不存在的编辑 返回异常，可能时序问题
                $rs = M('TposInfo')->Add($pos_info);
                
                if (! $rs) {
                    $this->log("终端同步新增[" . $pos_sync['PosId'] . "]异常");
                    $this->notifyreturn('1002');
                }
            }else {
                $rs = M('TposInfo')->where($where)->save($pos_info);
                if (! $rs) {
                    $this->log("终端同步更新[" . $pos_sync['PosId'] . "]异常");
                    $this->notifyreturn('1005');
                }
            }
        } else {
            $this->log("终端同步类型[" . $sync_type . "]异常");
            $this->notifyreturn('1001');
        }

        $this->notifyreturn();
    }

//计算条码支付手续费和实收金额
private function calc_pay_fee(&$verify_info,&$fee_amt,&$real_amt,$ori_pos_trace = Array()) {
	$shop_amt = 0;



	if ($verify_info['code_type'] == '100'){	//支付宝
		if ($verify_info['TransType'] == '0204')
		{
			$shop_amt = $verify_info['McardFee'] + $verify_info['MdiscountFee'] + $verify_info['McouponFee'];
			$fee_amt = round(($verify_info['Amt'] - $shop_amt) * $verify_info['Frate'],2);
			$real_amt = round($verify_info['Amt'] - $shop_amt	- $fee_amt,2);
		}
		else
		{	
			//统计之前已经退款的总计
			$rs = M('tzfb_offline_pay_trace')->field('ifnull(sum(exchange_amt),0) as exchange_amt')->where("trans_type = 'R' and status = '0' and zfb_out_trade_no =  '{$verify_info['OutTradeNo']}' and pos_seq <> '{$verify_info['TerminalSeq']}' ")->find();
			$this->log("sql=".M()->_sql());

			$this->log("amount=[". $verify_info['Amt'] ."] org_amt=[".$ori_pos_trace['exchange_amt'] ."]");
			$this->log("已退金额=[". $rs['exchange_amt'] ."]");
			if ( $verify_info['Amt'] < $ori_pos_trace['exchange_amt'] ) //如退款金额是小于——则退款金额进行退款；退手续费按照【退款金额】来计算
			{
				$this->log("mcard_fee=[". $ori_pos_trace['mcard_fee'] ."] mdiscount_fee=[".$ori_pos_trace['mdiscount_fee'] ."] mcoupon_fee=[".$ori_pos_trace['mcoupon_fee'] ."]");
				//计算剩余可退款金额 原订单金额-已退款金额-商家优惠金额
				$remain_amt = $ori_pos_trace['exchange_amt'] - $rs['exchange_amt']- $ori_pos_trace['mcard_fee'] - $ori_pos_trace['mdiscount_fee'] - $ori_pos_trace['mcoupon_fee'];
				$remain_amt = ($remain_amt < 0) ? 0:$remain_amt;
				$this->log("可退金额=[". $remain_amt ."]");
				//如果要退款的金额小于等于可退金额，则按退款金额进行记录
				if ($remain_amt >= $verify_info['Amt'])
				{
					//订单金额：447.90元  费率0.004%   交易收取手续费：1.79元 退款：248.9
					//收款手续费：（447.9-248.9）*0.004=0.79。四舍五入。为0.8
					//退款手续费：1.79-0.8=0.99元
					if ($rs['exchange_amt'] == 0)	//之前没退过款的部分退款，按支付宝方式计算
					{
						$fee_amt = $ori_pos_trace['fee_amt'] - round(($ori_pos_trace['exchange_amt'] - $verify_info['Amt'] - $ori_pos_trace['mcard_fee'] - $ori_pos_trace['mdiscount_fee'] - $ori_pos_trace['mcoupon_fee']) * $verify_info['Frate'],2);
						$real_amt = $verify_info['Amt'] - $fee_amt;	
					}
					else	//之前退过款的，概率太低，算法太复杂暂时不考虑
					{
						$fee_amt = round($verify_info['Amt'] * $verify_info['Frate'],2);
						$real_amt = $verify_info['Amt'] - $fee_amt;
					}
				}//否则按照可退款金额记录
			else
			{
					$fee_amt = round($remain_amt * $verify_info['Frate'],2);
					$real_amt = $remain_amt - $fee_amt;
				}
			}
			else
			{
				$verify_info['McardFee'] =  ($verify_info['McardFee'] == 0) ? round($ori_pos_trace['mcard_fee'],2) : round($verify_info['McardFee'],2);
				$verify_info['MdiscountFee'] = ($verify_info['MdiscountFee'] ==0 ) ? round($ori_pos_trace['mdiscount_fee'],2) : round($verify_info['MdiscountFee'],2) ;
				$verify_info['McouponFee'] = ($verify_info['McouponFee'] == 0) ? round($ori_pos_trace['mcoupon_fee'],2) : round($verify_info['McouponFee'],2);
				$shop_amt = $verify_info['McardFee'] + $verify_info['MdiscountFee'] + $verify_info['McouponFee'];
				$fee_amt = round(($verify_info['Amt'] - $shop_amt) * $verify_info['Frate'],2);
				$real_amt = round($verify_info['Amt'] - $shop_amt	- $fee_amt,2);
			}

		}
		
		$real_amt = round($real_amt, 2);
		$this->log("amount=[". $verify_info['Amt'] ."] shop_amt=[" . $shop_amt ."] fee_amt=[" . $fee_amt . "] real_amt=[" . $real_amt . "]");
        

		//支付宝——交易金额-商家优惠金额-【（交易金额-商家优惠金额）*交易费率后_四舍五入的接入】
		//其中商家优惠金额=商户店铺卡+商户优惠券+商户红包
		
		
	}
	else if ($verify_info['code_type'] == '101'){	//微信
		//rdm:18996
		//手续费——交易金额（及订单金额）*交易费率
		//实收金额——交易金额-微信平台优惠-【交易金额*交易费率后_四舍五入的接入 】
		$fee_amt = round($verify_info['Amt']*$verify_info['Frate'], 2);
		$real_amt = $verify_info['Amt'] - $fee_amt;
		/*
        $fee_amt = round(
            ($verify_info['Amt'] - $verify_info['CouponFee']) *
                 $verify_info['Frate'], 2) +
         round($verify_info['CouponFee'] * $verify_info['Frate'], 2);

		//交易金额-微信平台优惠-【（交易金额-微信平台优惠）*交易费率后_四舍五入的接入】
		$real_amt = $verify_info['Amt'] - $verify_info['CouponFee']	- $fee_amt;
		*/
	} 
	else if ($verify_info['code_type'] == '105'){	//QQ钱包 同微信
		$fee_amt = round($verify_info['Amt']*$verify_info['Frate'], 2);
		$real_amt = $verify_info['Amt'] - $fee_amt;
	} 
	else  {
		$fee_amt = $verify_info['Amt'] * $verify_info['Frate'];
		$fee_amt = round($fee_amt, 2);

		$real_amt = $verify_info['Amt'] - $fee_amt;
	}

	$this->log("real_amt1=[" . $real_amt . "]");
}
// 支付线下支付流水同步应答处理
private function pay_notifyreturn($verify_info) {
	// 计算手续费和实收金额
	$fee_amt = 0;
	$real_amt = 0;
	
	$this->calc_pay_fee($verify_info,$fee_amt,$real_amt);

	// 更改状态	支撑status=3 表示失败
	if ($verify_info['Status'] == 3) {
	$verify_info['Status'] = 1;
	}
	
	$pos_trace = array();
	$pos_trace['exchange_amt'] = $verify_info['Amt'];
	$pos_trace['status'] = $verify_info['Status'];
	$pos_trace['trans_time'] = $verify_info['TransTime'];
	$pos_trace['ret_code'] = $verify_info['SysRetCode'];
	$pos_trace['ret_desc'] = $verify_info['SysRetDesc'];
	$pos_trace['user_name'] = $verify_info['BuyerUserId']; // 支付宝买家账号
	$pos_trace['zfb_buyer_logon_id'] = $verify_info['BuyerLogonId']; // 支付宝买家用户号
	$pos_trace['zfb_trade_no'] = $verify_info['AlipayTradeNo']; // 支付宝流水号
	$pos_trace['zfb_out_trade_no'] = $verify_info['OutTradeNo']; // 支付宝商户订单号
	$pos_trace['zfb_out_pos_seq'] = $verify_info['OutPosSeq']; // 支付宝外部商户流水号
	$pos_trace['zfb_frate'] = $verify_info['Frate']; // 费率
	$pos_trace['zfb_coupon_fee'] = $verify_info['CouponFee']; // 支付红包
	$this->log('CouponFee=['.$verify_info['CouponFee'].']');

	$pos_trace['mcard_fee'] = $verify_info['McardFee']; // 商户店铺卡金额
	$pos_trace['mdiscount_fee'] = $verify_info['MdiscountFee']; // 商户优惠券金额
	$pos_trace['mcoupon_fee'] = $verify_info['McouponFee']; // 商户红包金额
	$pos_trace['point_fee'] = $verify_info['PointFee']; // 支付宝积分金额
	$pos_trace['discount_fee'] = $verify_info['DiscountFee']; // 支付宝折扣券金额

	$pos_trace['code_type'] = $verify_info['code_type'];
	$pos_trace['syn_seq'] = $verify_info['ReqSeq'];
	$pos_trace['fee_amt'] = $fee_amt;
	$pos_trace['real_amt'] = $real_amt;

	$pos_trace['pos_id'] = $verify_info['TerminalId'];
	$pos_trace['pos_seq'] = $verify_info['TerminalSeq'];
	$pos_trace['trans_type'] = 'T';
	$pos_trace['is_canceled'] = '0';
	$pos_trace['user_id'] = '00000000'; // epos,68同步的user_id记录00000000
	$pos_trace['node_id'] = $verify_info['ISSPID'];
	$pos_trace['store_id'] = $this->store_id;

	// 查看是否存在源流水
	$where = "trans_type = 'T' and zfb_out_trade_no = '" . $verify_info['OutTradeNo'] .	 "'";
	$ori_pos_trace = M('tzfb_offline_pay_trace')->where($where)->find();
	if (! $ori_pos_trace) {
		// 记流水
		$rs = M('tzfb_offline_pay_trace')->Add($pos_trace);
		if (! $rs) {
			$this->log("保存流水失败[tzfb_offline_pay_trace]失败". M()->_sql());
			$this->notifyreturn('0003');
		}
	} else {
		//存在折更新流水
		$rs = M('tzfb_offline_pay_trace')->where($where)->save($pos_trace);
		if ($rs === false) {
			$this->log("保存流水失败[tzfb_offline_pay_trace]失败");
			$this->notifyreturn('0003');
		}
	}

	if ($verify_info['code_type'] == '101'){	//微信
		if ($verify_info['Status'] == '0' && '0' !== $ori_pos_trace['status']) {
			$this->weixin_notify($pos_trace['zfb_buyer_logon_id'], $url, 
				$pos_trace['node_id'], $pos_trace['pos_id'], 
				$pos_trace['zfb_trade_no'], $pos_trace['exchange_amt'], 
				$pos_trace['trans_time']);
		}
	}

	$this->notifyreturn();
}

// 支付线下支付流水同步应答处理-退款
private function pay_r_notifyreturn($verify_info) {
	// 更改状态	支撑status=3 表示失败
	if ($verify_info['Status'] == 3) {
	$verify_info['Status'] = 1;
	}

	if (empty($verify_info['OutTradeNo']))
	{
		$this->log("未找到原流水[" . $verify_info['OutTradeNo'] . "]");
		$this->notifyreturn('0004');
	}

	$where = "trans_type = 'T' and zfb_out_trade_no = '" . $verify_info['OutTradeNo'] .	 "'";
	$ori_pos_trace = M('tzfb_offline_pay_trace')->where($where)->find();
	if (! $ori_pos_trace) {
	$this->log("未找到源流水[" . $verify_info['OutTradeNo'] . "]");
	$this->notifyreturn('0003');
	}

	$status = $verify_info['Status'];
	// 交易成功需要更新源流水的状态
	if ($verify_info['Status'] == 0) {
		if ($ori_pos_trace['status'] == '6') { // 待确认状态
			$status = '6';
			$verify_info['SysRetDesc'] .= '。退款原交易';
		}

		// 更新源流水
		$new_pos_trace['is_canceled'] = '1';
		$new_pos_trace['cancel_pos_seq'] = $verify_info['TerminalSeq'];
		M('tzfb_offline_pay_trace')->where($where)->save($new_pos_trace);

	} else if ($verify_info['Status'] == 7) { // 重复退款

		$verify_info['SysRetCode'] = 0001;
		$verify_info['SysRetDesc'] = '重复退款';
		$status = '1';
	}

	// 计算手续费和实收金额
	$fee_amt = 0;
	$real_amt = 0;


	$this->calc_pay_fee($verify_info,$fee_amt,$real_amt,$ori_pos_trace);

	//退款手续费和实收金额为负数
	$fee_amt = 0 - $fee_amt;
	$real_amt = 0 -$real_amt;


	// 记流水
	$pos_trace = array();
	$pos_trace['pos_id'] = $verify_info['TerminalId'];
	$pos_trace['pos_seq'] = $verify_info['TerminalSeq'];
	$pos_trace['trans_type'] = 'R';
	$pos_trace['status'] = $status;
	$pos_trace['is_canceled'] = '0';
	$pos_trace['exchange_amt'] = $verify_info['Amt'];
	$pos_trace['trans_time'] = $verify_info['TransTime'];
	$pos_trace['ret_code'] = $verify_info['SysRetCode'];
	$pos_trace['ret_desc'] = $verify_info['SysRetDesc'];
	$pos_trace['user_id'] = '00000000'; // epos,68同步的user_id记录00000000
	$pos_trace['user_name'] = $verify_info['BuyerUserId']; // 支付宝买家账号
	$pos_trace['zfb_buyer_logon_id'] = $verify_info['BuyerLogonId']; // 支付宝买家用户号
	$pos_trace['zfb_trade_no'] = $verify_info['AlipayTradeNo']; // 支付宝流水号
	$pos_trace['zfb_out_trade_no'] = $verify_info['OutTradeNo']; // 支付宝商户订单号
	$pos_trace['zfb_out_pos_seq'] = $verify_info['OutPosSeq']; // 支付宝外部商户流水号
	$pos_trace['zfb_frate'] = $verify_info['Frate']; // 费率
	$pos_trace['zfb_coupon_fee'] = $verify_info['CouponRefundFee']; // 退款红包

	$pos_trace['mcard_fee'] = $verify_info['McardFee']; // 商户店铺卡金额
	$pos_trace['mdiscount_fee'] =$verify_info['MdiscountFee']; // 商户优惠券金额
	$pos_trace['mcoupon_fee'] = $verify_info['McouponFee']; // 商户红包金额
	$pos_trace['point_fee'] = $verify_info['PointFee']; // 支付宝积分金额
	$pos_trace['discount_fee'] = $verify_info['DiscountFee']; // 支付宝折扣券金额

	$pos_trace['code_type'] = $verify_info['code_type'];
	$pos_trace['node_id'] = $verify_info['ISSPID'];
	$pos_trace['syn_seq'] = $verify_info['ReqSeq'];
	$pos_trace['fee_amt'] = $fee_amt;
	$pos_trace['real_amt'] = $real_amt;
	$pos_trace['org_posseq'] = $ori_pos_trace['pos_seq'];
	$pos_trace['store_id'] = $this->store_id;

	// 查看是否存在同一流水
	$where = "pos_id = '" . $verify_info['TerminalId'] . "' and pos_seq = '" . $verify_info['TerminalSeq'] . "'";
	$old_pos_trace = M('tzfb_offline_pay_trace')->where($where)->find();
	if (! $old_pos_trace) {
		$rs = M('tzfb_offline_pay_trace')->Add($pos_trace);
		if (! $rs) {
			$this->log("新增流水失败[tzfb_offline_pay_trace]失败" . M()->_sql());
			$this->notifyreturn('0003');
		}
	} else {
		$rs = M('tzfb_offline_pay_trace')->where($where)->save($pos_trace);
		if ($rs === false) {
			$this->log("保存流水失败[tzfb_offline_pay_trace]失败" . M()->_sql());
			$this->notifyreturn('0004');
		}
		}
	$this->notifyreturn();
}

// 支付线下支付流水同步应答处理-撤销
private function pay_c_notifyreturn($verify_info) {
	// 更改状态
	if ($verify_info['Status'] == 3) {
	$verify_info['Status'] = 1;
	}

	if (empty($verify_info['OutTradeNo']))
	{
		$this->log("未找到原流水[" . $verify_info['OutTradeNo'] . "]");
		$this->notifyreturn('0004');
	}

	$where = "trans_type = 'T' and zfb_out_trade_no = '" . $verify_info['OutTradeNo'] . "'";
	$ori_pos_trace = M('tzfb_offline_pay_trace')->where($where)->find();
	if (! $ori_pos_trace) {
		$this->log("未找到源流水[" . $verify_info['OutTradeNo'] . "]");
		$this->notifyreturn('0003');
	}
	$status = $verify_info['Status'];

	// 交易成功需要更新源流水的状态
	if ($verify_info['Status'] == 0) {
		if ($ori_pos_trace['status'] == '6') { // 待确认状态
			$status = '6';
			$verify_info['SysRetDesc'] .= '。撤销原交易';
		}
		// 更新源流水
		$new_pos_trace['is_canceled'] = '2';
		$new_pos_trace['cancel_pos_seq'] = $verify_info['TerminalSeq'];
		M('tzfb_offline_pay_trace')->where($where)->save($new_pos_trace);

	} else if ($verify_info['Status'] == 7) { // 重复撤销
		$verify_info['SysRetCode'] = 0001;
		$verify_info['SysRetDesc'] = '重复撤销';
		$status = '1';
	}

	// 计算手续费和实收金额
	$fee_amt = 0;
	$real_amt = 0;

	$this->calc_pay_fee($verify_info,$fee_amt,$real_amt,$ori_pos_trace);

	//撤销手续费和实收金额为负数
	$fee_amt = 0 - $fee_amt;
	$real_amt = 0 -$real_amt;

	// 记流水
	$pos_trace = array();
	$pos_trace['pos_id'] = $verify_info['TerminalId'];
	$pos_trace['pos_seq'] = $verify_info['TerminalSeq'];
	$pos_trace['trans_type'] = 'C';
	$pos_trace['status'] = $status;
	$pos_trace['is_canceled'] = '0';
	$pos_trace['exchange_amt'] = $verify_info['Amt'];
	$pos_trace['trans_time'] = $verify_info['TransTime'];
	$pos_trace['ret_code'] = $verify_info['SysRetCode'];
	$pos_trace['ret_desc'] = $verify_info['SysRetDesc'];
	$pos_trace['user_id'] = '00000000'; // epos,68同步的user_id记录00000000
	$pos_trace['user_name'] = $verify_info['BuyerUserId']; // 支付宝买家账号
	$pos_trace['zfb_buyer_logon_id'] = $verify_info['BuyerLogonId']; // 支付宝买家用户号
	$pos_trace['zfb_trade_no'] = $verify_info['AlipayTradeNo']; // 支付宝流水号
	$pos_trace['zfb_out_trade_no'] = $verify_info['OutTradeNo']; // 支付宝商户订单号
	$pos_trace['zfb_out_pos_seq'] = $verify_info['OutPosSeq']; // 支付宝外部商户流水号
	$pos_trace['zfb_frate'] = $verify_info['Frate']; // 费率
	$pos_trace['zfb_coupon_fee'] = $verify_info['CouponRefundFee']; // 退款红包

	$pos_trace['mcard_fee'] = empty($verify_info['McardFee']) ? $ori_pos_trace['mcard_fee'] : $verify_info['McardFee']; // 商户店铺卡金额
	$pos_trace['mdiscount_fee'] =empty($verify_info['MdiscountFee']) ? $ori_pos_trace['mdiscount_fee'] : $verify_info['MdiscountFee']; // 商户优惠券金额
	$pos_trace['mcoupon_fee'] = empty($verify_info['McouponFee']) ? $ori_pos_trace['mcoupon_fee'] : $verify_info['McouponFee']; // 商户红包金额
	$pos_trace['point_fee'] = empty($verify_info['PointFee']) ? $ori_pos_trace['point_fee'] : $verify_info['PointFee']; // 支付宝积分金额
	$pos_trace['discount_fee'] = empty($verify_info['DiscountFee']) ? $ori_pos_trace['discount_fee'] : $verify_info['DiscountFee']; // 支付宝折扣券金额

	$pos_trace['code_type'] = $verify_info['code_type'];
	$pos_trace['node_id'] = $verify_info['ISSPID'];
	$pos_trace['syn_seq'] = $verify_info['ReqSeq'];
	$pos_trace['fee_amt'] = $fee_amt;
	$pos_trace['real_amt'] = $real_amt;
	$pos_trace['org_posseq'] = $ori_pos_trace['pos_seq'];
	$pos_trace['store_id'] = $this->store_id;

	// 查看是否存在同一流水
	$where = "pos_id = '" . $verify_info['TerminalId'] . "' and pos_seq = '" . $verify_info['TerminalSeq'] . "'";
	$old_pos_trace = M('tzfb_offline_pay_trace')->where($where)->find();
	if (! $old_pos_trace) {
		$rs = M('tzfb_offline_pay_trace')->Add($pos_trace);
		if (! $rs) {
			$this->log("新增流水失败[tzfb_offline_pay_trace]失败" . M()->_sql());
			$this->notifyreturn('0003');
		}
	} else {
		$rs = M('tzfb_offline_pay_trace')->where($where)->save($pos_trace);
		if ($rs === false) {
			$this->log("保存流水失败[tzfb_offline_pay_trace]失败" . M()->_sql());
			$this->notifyreturn('0004');
		}
	}
	$this->notifyreturn();
}


// 第三方团购平台核销流水同步应答处理
private function group_buy_notifyreturn($verify_info) {
	// 更改状态	支撑status=3 表示失败
	if ($verify_info['Status'] == 3) {
	$verify_info['Status'] = 1;
	}
	
	$pos_trace = array();
	// 如果前端没有送商品金额，那就取商品配置表的金额，方便统计
	$pos_trace['exchange_amt'] = $verify_info['Amt'];
	$pos_trace['status'] = $verify_info['Status'];
	$pos_trace['trans_time'] = $verify_info['TransTime'];
	$pos_trace['ret_code'] = $verify_info['SysRetCode'];
	$pos_trace['ret_desc'] = $verify_info['SysRetDesc'];

	$pos_trace['code_type'] = $verify_info['code_type'];
	$pos_trace['syn_seq'] = $verify_info['ReqSeq'];
	$pos_trace['group_goods_id'] = $verify_info['SpareField5'];

	$pos_trace['ticket_number'] = $verify_info['SpareField4'];

	$pos_trace['pos_id'] = $verify_info['TerminalId'];
	$pos_trace['pos_seq'] = $verify_info['TerminalSeq'];
	$pos_trace['trans_type'] = '1';
	$pos_trace['is_canceled'] = '0';
	$pos_trace['node_id'] = $verify_info['ISSPID'];
	$pos_trace['store_id'] = $this->store_id;

	// 查看是否存在源流水
	$where = "trans_type = '1' and pos_id = '" . $verify_info['TerminalId'] . "' and pos_seq = '" . $verify_info['TerminalSeq'] . "'";
	$ori_pos_trace = M('tgroup_buy_verify_trace')->where($where)->find();
	if (! $ori_pos_trace) {
		// 记流水
		$rs = M('tgroup_buy_verify_trace')->Add($pos_trace);
		if (! $rs) {
			$this->log("保存流水失败[tgroup_buy_verify_trace]失败". M()->_sql());
			$this->notifyreturn('0003');
		}
	} else {
		//存在折更新流水
		$rs = M('tgroup_buy_verify_trace')->where($where)->save($pos_trace);
		if ($rs === false) {
			$this->log("保存流水失败[tgroup_buy_verify_trace]失败");
			$this->notifyreturn('0003');
		}
	}

	//检查团购商品有没有记录过，没有的话记录一下
	if (!empty($verify_info['SpareField5']) && !empty($verify_info['DealMiniTitle']))
	{
		$goods_info = M('tgroup_buy_goods_info')->field('goods_price,settle_price')->where(" node_id = '{$verify_info['ISSPID']}' and goods_id = '{$verify_info['SpareField5']}'  and code_type = '{$verify_info['code_type']}'")->find();
		if (!$goods_info)
		{
			$this->log("新商品！！第三方平台商品编码[" .$verify_info['SpareField5'].']');
			$goods_info['node_id']	= $verify_info['ISSPID'];
			$goods_info['code_type']	= $verify_info['code_type'];
			$goods_info['goods_name']	= iconv("gbk", "utf-8", $verify_info['DealMiniTitle']); 
			$goods_info['goods_id']	= $verify_info['SpareField5'];
			$goods_info['status']	= '0';
			$goods_info['add_time']	= date('YmdHis');
			$goods_info['start_time']	= $verify_info['DealStartTime'];
			$goods_info['end_time']	= $verify_info['DealExpireTime'];
			$rs = M('tgroup_buy_goods_info')->add($goods_info);
			if (!$rs) {
				$this->log(print_r($goods_info, true));
				$this->log("记录[goods_info]失败");
				$this->notifyreturn('0008');
			}
		}
	}


	$this->notifyreturn();
}



// wpos应答处理
private function wpos_notifyreturn($verify_info) {
// 更新流水信息
$where = "pos_id ='" . $verify_info['TerminalId'] . "' and pos_seq = '" .
 $verify_info['TerminalSeq'] . "'";

$pos_trace['req_seq'] = $verify_info['SpSeq'];
$pos_trace['syn_seq'] = $verify_info['ReqSeq'];
$pos_trace['ori_syn_seq'] = $verify_info['SpareField2'];
$pos_trace['settle_amt'] = $verify_info['settle_amt'];
$pos_trace['goods_id'] = $verify_info['goods_id'];
M('TposTrace')->where($where)->save($pos_trace);

// 更新凭证信息
$req_seq = $verify_info['SpSeq']; // 源请求流水
$where = "req_seq = '" . $org_seq . "'";

$barcode_trace['use_status'] = '0';
if ($verify_info['Status'] == '2') {
$barcode_trace['use_status'] = '1';
}
M('TbarcodeTrace')->where($where)->save($barcode_trace);

if ($barcode_trace['use_status'] != '0') {
// 行为数据添加
$this->addMemberBehavior($this->pos_node_id, $this->phone, $this->channel_id);
}

// 更新统计表 goods_id 这里不能增加统计数
$where = "NODE_ID ='" . $verify_info['ISSPID'] . "' and BATCH_NO ='" .
 $verify_info['ActivityID'] . "' and POS_ID ='" . $verify_info['TerminalId'] .
 "' and TRANS_DATE ='" . date('Y-m-d') . "'";
$new_day_count = array();
$new_day_count['goods_id'] = $verify_info['goods_id'];
$rs = M('TposDayCount')->where($where)->save($new_day_count);
if (! $rs) {
$this->log("更新统计信息[tpos_day_count]失败");
$this->notifyreturn('0002');
}
$this->notifyreturn();
}

// 应答
private function zcpt_notifyreturn($verify_info) {

// 获取交易类型
$trans_type = $verify_info['TransType']; // 交易类型
$req_seq = $verify_info['SpSeq']; // 请求流水
$org_seq = $verify_info['SpareField2']; // 源请求流水

if ($trans_type == '0001') // 验证
{
// 记流水
$pos_trace = array();
$pos_trace['pos_id'] = $verify_info['TerminalId'];
$pos_trace['pos_seq'] = $verify_info['TerminalSeq'];
$pos_trace['trans_type'] = '0';
$pos_trace['status'] = '0';
$pos_trace['is_canceled'] = '0';
$pos_trace['exchange_amt'] = $verify_info['Amt'];
$pos_trace['trans_time'] = $verify_info['TransTime'];
$pos_trace['batch_no'] = $verify_info['ActivityID'];
$pos_trace['ret_code'] = 0;
$pos_trace['ret_desc'] = '验证成功';
$pos_trace['user_id'] = '00000000'; // epos,68同步的user_id记录00000000
$pos_trace['user_name'] = '00000000'; // epos,68同步的user_id记录00000000
$pos_trace['phone_no'] = $verify_info['PhoneNo'];
$pos_trace['assistant_no_back'] = '0000';
$pos_trace['assistant_no_md5'] = '00000000000000000000000000000000';
$pos_trace['code_type'] = '007'; // 流水同步数据
$pos_trace['stat_id'] = 0;
$pos_trace['node_id'] = $verify_info['ISSPID'];
$pos_trace['req_seq'] = $req_seq;
$pos_trace['syn_seq'] = $verify_info['ReqSeq'];
$pos_trace['goods_id'] = $verify_info['goods_id'];
$pos_trace['pos_node_id'] = $this->pos_node_id;

if ($verify_info['PrepayDepositAmt'] != null)
    $pos_trace['settle_amt'] = $verify_info['PrepayDepositAmt'];
    // 排重处理
$where = "pos_id ='" . $verify_info['TerminalId'] . "' and pos_seq = '" .
     $verify_info['TerminalSeq'] . "'";
$tmp_pos_trace = M('TposTrace')->where($where)->find();
if ($tmp_pos_trace) {
    $rs = M('TposTrace')->where($where)->save($pos_trace);
    if ($rs === false) {
        $this->log("更新统计信息[TposTrace]失败" . M()->_sql());
        $this->notifyreturn('0001');
    }
    $this->notifyreturn();
} else {
    $rs = M('TposTrace')->Add($pos_trace);
    if (! $rs) {
        $this->log("记录统计信息[TposTrace]失败" . M()->_sql());
        $this->notifyreturn('0001');
    }
}
// 增加渠道表验证统计
$this->verify_channel_stat($this->channel_id, 1);
// 更改凭证信息
$where = "req_seq = '" . $req_seq . "'";

$barcode_trace['use_status'] = '2';
if ($verify_info['Status'] == '2') {
    $barcode_trace['use_status'] = '1';
}
$barcode_trace['use_time'] = $verify_info['TransTime'];
M('TbarcodeTrace')->where($where)->save($barcode_trace);

// 行为数据添加
$this->addMemberBehavior($this->pos_node_id, $this->phone, $this->channel_id);

// 统计
$where = "NODE_ID ='" . $verify_info['ISSPID'] . "' and BATCH_NO ='" .
     $verify_info['ActivityID'] . "' and POS_ID ='" . $verify_info['TerminalId'] .
     "' and TRANS_DATE ='" . date('Y-m-d') . "' and b_id = " . $this->b_id;
$pos_day_count = M('TposDayCount')->where($where)->find();
if (! $pos_day_count) {
    $pos_day_count['node_id'] = $verify_info['ISSPID'];
    $pos_day_count['pos_id'] = $verify_info['TerminalId'];
    $pos_day_count['batch_no'] = $verify_info['ActivityID'];
    $pos_day_count['trans_date'] = date('Y-m-d');
    $pos_day_count['send_num'] = 0;
    $pos_day_count['send_amt'] = 0;
    $pos_day_count['verify_num'] = 1;
    $pos_day_count['verify_amt'] = $verify_info['Amt'];
    $pos_day_count['cancel_num'] = 0;
    $pos_day_count['cancel_amt'] = 0;
    $pos_day_count['goods_id'] = $verify_info['goods_id'];
    $pos_day_count['b_id'] = $this->b_id;
    $rs = M('TposDayCount')->add($pos_day_count);
    if (! $rs) {
        $this->log("记录统计信息[tpos_day_count]失败");
        $this->notifyreturn('0001');
    }
} else {
    $new_day_count = array();
    $new_day_count['verify_num'] = $pos_day_count['verify_num'] + 1;
    $new_day_count['verify_amt'] = $pos_day_count['verify_amt'] +
         $verify_info['Amt'];
    $new_day_count['goods_id'] = $verify_info['goods_id'];
    $rs = M('TposDayCount')->where($where)->save($new_day_count);
    if (! $rs) {
        $this->log("更新统计信息[tpos_day_count]失败");
        $this->notifyreturn('0002');
    }
}
} else if ($trans_type == '0002') // 撤销
{
// 先获取撤销源流水
$where = "pos_id = '" . $verify_info['TerminalId'] . "' and syn_seq = '" .
     $org_seq . "'";
$ori_pos_trace = M('TposTrace')->where($where)->find();
if (! $ori_pos_trace) {
    $this->log("未找到源流水[" . $org_seq . "]");
    $this->notifyreturn('0003');
}
// 更新源流水
$new_pos_trace['is_canceled'] = '1';
M('TposTrace')->where($where)->save($new_pos_trace);

// 记流水
$pos_trace = array();
$pos_trace['pos_id'] = $verify_info['TerminalId'];
$pos_trace['pos_seq'] = $verify_info['TerminalSeq'];
$pos_trace['trans_type'] = '1';
$pos_trace['status'] = '0';
$pos_trace['is_canceled'] = '0';
$pos_trace['exchange_amt'] = $verify_info['Amt'];
$pos_trace['trans_time'] = $verify_info['TransTime'];
$pos_trace['batch_no'] = $verify_info['ActivityID'];
$pos_trace['ret_code'] = 0;
$pos_trace['ret_desc'] = '撤销成功';
$pos_trace['user_id'] = '00000000'; // epos,68同步的user_id记录00000000
$pos_trace['user_name'] = '00000000'; // epos,68同步的user_id记录00000000
$pos_trace['phone_no'] = $verify_info['PhoneNo'];
$pos_trace['assistant_no_back'] = '0000';
$pos_trace['assistant_no_md5'] = '00000000000000000000000000000000';
$pos_trace['code_type'] = '007'; // 流水同步数据
$pos_trace['stat_id'] = 0;
$pos_trace['node_id'] = $verify_info['ISSPID'];
$pos_trace['req_seq'] = $req_seq;
$pos_trace['syn_seq'] = $verify_info['ReqSeq'];
$pos_trace['ori_syn_seq'] = $org_seq;
$pos_trace['goods_id'] = $verify_info['goods_id'];
$pos_trace['pos_node_id'] = $this->pos_node_id;

if ($verify_info['PrepayDepositAmt'] != null)
    $pos_trace['settle_amt'] = $verify_info['PrepayDepositAmt'];
    // 排重处理
$where = "pos_id ='" . $verify_info['TerminalId'] . "' and pos_seq = '" .
     $verify_info['TerminalSeq'] . "'";
$tmp_pos_trace = M('TposTrace')->where($where)->find();
if ($tmp_pos_trace) {
    $rs = M('TposTrace')->where($where)->save($pos_trace);
    if ($rs === false) {
        $this->log("更新统计信息[TposTrace]失败" . M()->_sql());
        $this->notifyreturn('0001');
    }
    $this->notifyreturn();
} else {
    $rs = M('TposTrace')->Add($pos_trace);
    if (! $rs) {
        $this->log("记录统计信息[TposTrace]失败" . M()->_sql());
        $this->notifyreturn('0001');
    }
}
// 减少渠道表验证统计
$this->verify_channel_stat($this->channel_id, 2);
// 更改凭证信息
$where = "req_seq = '" . $req_seq . "'";

$barcode_trace['use_status'] = '0';
if ($verify_info['Status'] == '2') {
    $barcode_trace['use_status'] = '1';
}
M('TbarcodeTrace')->where($where)->save($barcode_trace);

// 统计
$where = "NODE_ID ='" . $verify_info['ISSPID'] . "' and BATCH_NO ='" .
     $verify_info['ActivityID'] . "' and POS_ID ='" . $verify_info['TerminalId'] .
     "' and TRANS_DATE ='" . date('Y-m-d') . "' and b_id = " . $this->b_id;
$pos_day_count = M('TposDayCount')->where($where)->find();
if (! $pos_day_count) {
    $pos_day_count['node_id'] = $verify_info['ISSPID'];
    $pos_day_count['pos_id'] = $verify_info['TerminalId'];
    $pos_day_count['batch_no'] = $verify_info['ActivityID'];
    $pos_day_count['trans_date'] = date('Y-m-d');
    $pos_day_count['send_num'] = 0;
    $pos_day_count['send_amt'] = 0;
    $pos_day_count['verify_num'] = 0;
    $pos_day_count['verify_amt'] = 0;
    $pos_day_count['cancel_num'] = 1;
    $pos_day_count['cancel_amt'] = $verify_info['Amt'];
    $pos_day_count['goods_id'] = $verify_info['goods_id'];
    $pos_day_count['b_id'] = $this->b_id;
    $rs = M('TposDayCount')->add($pos_day_count);
    if (! $rs) {
        $this->log("记录统计信息[tpos_day_count]失败");
        $this->notifyreturn('0001');
    }
} else {
    $new_day_count = array();
    $new_day_count['cancel_num'] = $pos_day_count['cancel_num'] + 1;
    $new_day_count['cancel_amt'] = $pos_day_count['cancel_amt'] +
         $verify_info['Amt'];
    $new_day_count['goods_id'] = $verify_info['goods_id'];
    $rs = M('TposDayCount')->where($where)->save($new_day_count);
    if (! $rs) {
        $this->log("更新统计信息[tpos_day_count]失败");
        $this->notifyreturn('0002');
    }
}
} else if ($trans_type == '0003') // 冲正
{
// 先获取冲正源流水
$where = "pos_id = '" . $verify_info['TerminalId'] . "' and syn_seq = '" .
     $org_seq . "'";
$ori_pos_trace = M('TposTrace')->where($where)->find();
if (! $ori_pos_trace) {
    $this->log("未找到源流水[" . $org_seq . "]");
    $this->notifyreturn('0003');
}
// 更新源流水
$new_pos_trace['status'] = '2';
M('TposTrace')->where($where)->save($new_pos_trace);

// 记流水
$pos_trace = array();
$pos_trace['pos_id'] = $verify_info['TerminalId'];
$pos_trace['pos_seq'] = $verify_info['TerminalSeq'];
$pos_trace['org_posseq'] = $org_seq;
$pos_trace['trans_type'] = '2';
$pos_trace['status'] = '0';
$pos_trace['is_canceled'] = '0';
$pos_trace['exchange_amt'] = $verify_info['Amt'];
$pos_trace['trans_time'] = $verify_info['TransTime'];
$pos_trace['batch_no'] = $verify_info['ActivityID'];
$pos_trace['ret_code'] = 0;
$pos_trace['ret_desc'] = '冲正成功';
$pos_trace['user_id'] = '00000000'; // epos,68同步的user_id记录00000000
$pos_trace['user_name'] = '00000000'; // epos,68同步的user_id记录00000000
$pos_trace['phone_no'] = $verify_info['PhoneNo'];
$pos_trace['assistant_no_back'] = '0000';
$pos_trace['assistant_no_md5'] = '00000000000000000000000000000000';
$pos_trace['code_type'] = '007'; // 流水同步数据
$pos_trace['stat_id'] = 0;
$pos_trace['node_id'] = $verify_info['ISSPID'];
$pos_trace['req_seq'] = $req_seq;
$pos_trace['syn_seq'] = $verify_info['ReqSeq'];
$pos_trace['ori_syn_seq'] = $org_seq;
$pos_trace['goods_id'] = $verify_info['goods_id'];
$pos_trace['pos_node_id'] = $this->pos_node_id;

if ($verify_info['PrepayDepositAmt'] != null)
    $pos_trace['settle_amt'] = $verify_info['PrepayDepositAmt'];
    // 排重处理
$where = "pos_id ='" . $verify_info['TerminalId'] . "' and pos_seq = '" .
     $verify_info['TerminalSeq'] . "'";
$tmp_pos_trace = M('TposTrace')->where($where)->find();
if ($tmp_pos_trace) {
    $rs = M('TposTrace')->where($where)->save($pos_trace);
    if ($rs === false) {
        $this->log("更新统计信息[TposTrace]失败" . M()->_sql());
        $this->notifyreturn('0001');
    }
    $this->notifyreturn();
} else {
    $rs = M('TposTrace')->Add($pos_trace);
    if (! $rs) {
        $this->log("记录统计信息[TposTrace]失败" . M()->_sql());
        $this->notifyreturn('0001');
    }
}

// 更改凭证信息
$where = "req_seq = '" . $req_seq . "'";

$barcode_trace['use_status'] = '0';
if ($verify_info['Status'] == '2') {
    $barcode_trace['use_status'] = '1';
} else if ($verify_info['Status'] == '3') {
    $barcode_trace['use_status'] = '2';
}
M('TbarcodeTrace')->where($where)->save($barcode_trace);

// 统计
$where = "NODE_ID ='" . $verify_info['ISSPID'] . "' and BATCH_NO ='" .
     $verify_info['ActivityID'] . "' and POS_ID ='" . $verify_info['TerminalId'] .
     "' and TRANS_DATE ='" . date('Y-m-d') . "' and b_id = " . $this->b_id;
$pos_day_count = M('TposDayCount')->where($where)->find();
if (! $pos_day_count) {
    $pos_day_count['node_id'] = $verify_info['ISSPID'];
    $pos_day_count['pos_id'] = $verify_info['TerminalId'];
    $pos_day_count['batch_no'] = $verify_info['ActivityID'];
    $pos_day_count['trans_date'] = date('Y-m-d');
    $pos_day_count['send_num'] = 0;
    $pos_day_count['send_amt'] = 0;
    $pos_day_count['verify_num'] = - 1;
    $pos_day_count['verify_amt'] = 0 - $verify_info['Amt'];
    $pos_day_count['cancel_num'] = 0;
    $pos_day_count['cancel_amt'] = 0;
    $pos_day_count['goods_id'] = $verify_info['goods_id'];
    $pos_day_count['b_id'] = $this->b_id;
    if ($ori_pos_trace['trans_type'] == '1') // 撤销冲正
{
        $pos_day_count['verify_num'] = 0;
        $pos_day_count['verify_amt'] = 0;
        $pos_day_count['cancel_num'] = - 1;
        $pos_day_count['cancel_amt'] = 0 - $verify_info['Amt'];
    }
    $rs = M('TposDayCount')->add($pos_day_count);
    if (! $rs) {
        $this->log("记录统计信息[tpos_day_count]失败");
        $this->notifyreturn('0001');
    }
} else {
    $new_day_count = array();
    if ($ori_pos_trace['trans_type'] == '0') // 验证冲正
{
        $new_day_count['verify_num'] = $pos_day_count['verify_num'] - 1;
        $new_day_count['verify_amt'] = $pos_day_count['verify_amt'] -
             $verify_info['Amt'];
        // 减少渠道表验证统计
        $this->verify_channel_stat($this->channel_id, 2);
    } else // 撤销冲正
{
        $new_day_count['cancel_num'] = $pos_day_count['cancel_num'] - 1;
        $new_day_count['cancel_amt'] = $pos_day_count['cancel_amt'] -
             $verify_info['Amt'];
        // 增加渠道表验证统计
        $this->verify_channel_stat($this->channel_id, 1);
    }
    $new_day_count['goods_id'] = $verify_info['goods_id'];
    $rs = M('TposDayCount')->where($where)->save($new_day_count);
    if (! $rs) {
        $this->log("更新统计信息[tpos_day_count]失败");
        $this->notifyreturn('0002');
    }
}
}

$this->notifyreturn();
}

private function weixin_notify($open_id, $url, $node_id, $pos_id, $order_id, 
$amount, $trans_time) {
// 没有绑定open_id 退出
if ($open_id == null || $open_id == '') {
return false;
}
// 获取门店名称
$where = "pos_id = '" . $pos_id . "'";
$store_info = M()->table('tpos_info')->field('store_name')->where($where)->find(); // 没有绑定关系
                                                                                   // create
                                                                                   // data
$data_array['keyword1']['value'] = $order_id; // 订单号
$data_array['keyword2']['value'] = $amount . "元"; // 消费金额
$data_array['keyword3']['value'] = $store_info['store_name']; // 消费门店
$data_array['keyword4']['value'] = date('Y-m-d H:i:s', strtotime($trans_time)); // 时间

$data_array['keyword1']['color'] = '#173177';
$data_array['keyword2']['color'] = '#173177';
$data_array['keyword3']['color'] = '#173177';
$data_array['keyword4']['color'] = '#173177';
$this->log("weixin_notify:" . print_r($data_array, true));
$this->_weixin_notify($open_id, $node_id, $url, $data_array);
}

/*
 * 发送微信通知
 */
private function _weixin_notify($open_id, $node_id, $url, $data_array) {
$weixinSendService = D('WeiXinSend', 'Service');
    try{
        $weixinSendService->init($node_id);
        $weixinSendService->templateSend($open_id, $node_id, '2', $url, $data_array);
    }catch(Exception $e){
        $this->log('_weixin_notify-send-fail'.$e->getMessage());
    }
}

// 通知应答
private function notifyreturn($resp_id = '0000') {
$resp_xml = '<?xml version="1.0" encoding="gbk"?><' . $this->responseType .
 '><StatusCode>' . $resp_id . '</StatusCode></' . $this->responseType . '>';
echo $resp_xml;
$this->log($resp_xml, 'RESPONSE');
exit();
}

// 添加会员验证行为数据
private function addMemberBehavior($nodeId, $phone, $channelId) {
if (strlen($phone) != 11 || $phone == '13900000000')
return;

$memberModel = D('MemberInstall', 'Model');
$condition = $phone;
$conditionType = 1; // 1手机 2翼码授权openid 3商户授权openid

$option = array(
'channel_id' => $channelId);
$result = $memberModel->wxTermMemberFlag($nodeId, $condition, $conditionType, 
true, $option);

if ($result === false) {
$this->log("getMemberInfo fail! node_id[{$nodeId}] condition[{$condition}]");
return;
}

$memberId = $result['id'];

$behaviorModel = D('MemberBehavior', 'Model');
$result = $behaviorModel->addBehaviorData($memberId, $nodeId, 0, 0, 1);
if ($result === false) {
$this->log(
    "===MEM_DEBUG===记录会员行为数据失败[凭证核销]member_id[{$memberId}],node_id[{$nodeId}]");
}
}

// 记录日志
protected function log($msg, $level = Log::INFO) {
trace('Log.' . $level . ':' . $msg);
log_write($msg, $level);
}
}
