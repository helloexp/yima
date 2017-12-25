<?php

/* 主动通知接口 */
class PtsIndexAction extends Action {

    public $ReqArr;

    public $transType;

    public $responseType;

    public $channel_id;

    public $b_id;

    public $pos_node_id;

    public function index() {
        $reqXml = file_get_contents('php://input');
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml]导入失败');
        $xml = new Xml();
        
        $this->log($reqXml, 'REQUEST');
        $this->ReqArr = $xml->parse($reqXml);
        $this->transType = $xml->getRootName();
        
        if ($this->transType == 'status_sync') { // 数据同步
                                                 // //马上发没有规定数据返回格式，就直接按照支撑的返回了
            $this->responseType = 'VerifySyncRes';
            $verify_info = $this->ReqArr['status_sync'];
            $this->verifysync($verify_info);
        } else {
            $this->responseType = 'ErrorRes';
            $this->notifyreturn('1000');
        }
    }
    
    // 验证同步
    private function verifysync($verify_info) {
        // 数据转换成支撑的格式
        $verify_info['TerminalId'] = $verify_info['verify_info']['pos_id'];
        $verify_info['TerminalSeq'] = $verify_info['verify_info']['pos_seq'];
        $verify_info['Amt'] = $verify_info['verify_info']['amount'];
        $verify_info['TransTime'] = $verify_info['trans_time'];
        $verify_info['ActivityID'] = $verify_info['verify_info']['amount'];
        $verify_info['ReqSeq'] = $verify_info['verify_info']['verify_seq'];
        $verify_info['Status'] = $verify_info['status'];
        $verify_info['org_pos_seq'] = $verify_info['verify_info']['org_pos_seq'];
        $verify_info['req_seq'] = $verify_info['req_seq'];
        
        // 二、查终端信息
        $where = "pos_id ='" . $verify_info['TerminalId'] . "'  ";
        $pos_info = M('TposInfo')->where($where)->find();
        if (! $pos_info) {
            $this->log("未找到终端[" . $verify_info['TerminalId'] . "]");
            // $this->notifyreturn();
        }
        
        $this->pos_node_id = $pos_info['node_id'];
        
        // zhengxh 2014-9-3
        // 调整顺序，先把条码的goods_id先查出来后，放入$this->wpos_notifyreturn($verify_info) 进行更新
        // 三、查活动信息 查GOODS_ID
        $where = "req_seq ='" . $verify_info['req_seq'] . "'";
        $barcode_info = M('TbarcodeTrace')->where($where)->find();
        if (! $barcode_info) {
            $this->log("未找到委托流水[" . $verify_info['req_seq'] . "]");
            // $this->notifyreturn();
        }
        $this->channel_id = $barcode_info['channel_id'];
        $this->b_id = $barcode_info['b_id'];
        if ($this->b_id == null)
            $this->b_id = 0;
        
        $verify_info['goods_id'] = $barcode_info['goods_id'];
        $verify_info['ISSPID'] = $barcode_info['node_id'];
        $verify_info['ActivityID'] = $barcode_info['batch_no'];
        $verify_info['PhoneNo'] = $barcode_info['phone_no'];
        
        $this->zcpt_notifyreturn($verify_info, $barcode_info); // 同步处理
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
                    $batch_info = M('tbatch_info')->where("id = " . $this->b_id)
                        ->field('m_id, node_id')
                        ->find();
                    $marketing_info = M('tmarketing_info')->where(
                        "id = " . $batch_info['m_id'])
                        ->field('batch_type')
                        ->find();
                    // 增加
                    $where = "batch_type = '" . $marketing_info['batch_type'] .
                         "' and batch_id = " . $batch_info['m_id'] .
                         " and channel_id  = " . $channel_id .
                         " and label_id = 0 and parent_id = 0 and  full_id = '0' and day = '" .
                         date('Ymd') . "'";
                    $rs = M('tdaystat')->where($where)
                        ->limit(1)
                        ->setInc('verify_count');
                    $this->log("增加验证统计数tday_stat失败[" . M()->_sql() . "]");
                    if (! $rs) {
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
    
    // 应答
    private function zcpt_notifyreturn($verify_info, $barcode_info) {
        
        // 获取交易类型
        if ($verify_info['trans_type'] == 'pos_verify') {
            $trans_type = '0001'; // 验证
        } else if ($verify_info['trans_type'] == 'pos_revoke') {
            $trans_type = '0002'; // 撤销
        } else if ($verify_info['trans_type'] == 'pos_rollback') {
            $trans_type = '0003'; // 冲正
        }
        $req_seq = $barcode_info['req_seq']; // 请求流水
        
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
            $pos_trace['batch_no'] = $barcode_info['batch_no'];
            $pos_trace['ret_code'] = 0;
            $pos_trace['ret_desc'] = '验证成功';
            $pos_trace['user_id'] = '00000000'; // epos,68同步的user_id记录00000000
            $pos_trace['user_name'] = '00000000'; // epos,68同步的user_id记录00000000
            $pos_trace['phone_no'] = $barcode_info['phone_no'];
            $pos_trace['assistant_no_back'] = '0000';
            $pos_trace['assistant_no_md5'] = '00000000000000000000000000000000';
            $pos_trace['code_type'] = '007'; // 流水同步数据
            $pos_trace['stat_id'] = 0;
            $pos_trace['node_id'] = $barcode_info['node_id'];
            $pos_trace['req_seq'] = $req_seq;
            $pos_trace['syn_seq'] = $verify_info['ReqSeq'];
            $pos_trace['goods_id'] = $verify_info['goods_id'];
            $pos_trace['pos_node_id'] = $this->pos_node_id;
            $pos_trace['settle_amt'] = $verify_info['Amt'];
            // 排重处理
            $where = "pos_id ='" . $verify_info['TerminalId'] .
                 "' and pos_seq = '" . $verify_info['TerminalSeq'] . "'";
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
            
            if ($verify_info['Status'] == '1') {
                $barcode_trace['use_status'] = '2';
            } else if ($verify_info['Status'] == '0') {
                $barcode_trace['use_status'] = '0';
            }
            $barcode_trace['use_time'] = $verify_info['TransTime'];
            M('TbarcodeTrace')->where($where)->save($barcode_trace);
            
            // 统计
            $where = "NODE_ID ='" . $verify_info['ISSPID'] . "' and BATCH_NO ='" .
                 $verify_info['ActivityID'] . "' and POS_ID ='" .
                 $verify_info['TerminalId'] . "' and TRANS_DATE ='" .
                 date('Y-m-d') . "' and b_id = " . $this->b_id;
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
            $where = "pos_id = '" . $verify_info['TerminalId'] .
                 "' and pos_seq = '" . $verify_info['org_pos_seq'] . "'";
            $ori_pos_trace = M('TposTrace')->where($where)->find();
            if (! $ori_pos_trace) {
                $this->log("未找到源流水[" . $verify_info['org_pos_seq'] . "]");
                $this->notifyreturn('0003');
            }
            // 更新源流水
            $new_pos_trace['is_canceled'] = '1';
            M('TposTrace')->where($where)->save($new_pos_trace);
            
            // 记流水
            $pos_trace = array();
            $pos_trace['pos_id'] = $verify_info['TerminalId'];
            $pos_trace['pos_seq'] = $verify_info['TerminalSeq'];
            $pos_trace['org_posseq'] = $verify_info['org_pos_seq'];
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
            $pos_trace['ori_syn_seq'] = $ori_pos_trace['ReqSeq'];
            $pos_trace['goods_id'] = $verify_info['goods_id'];
            $pos_trace['pos_node_id'] = $this->pos_node_id;
            $pos_trace['settle_amt'] = $verify_info['Amt'];
            // 排重处理
            $where = "pos_id ='" . $verify_info['TerminalId'] .
                 "' and pos_seq = '" . $verify_info['TerminalSeq'] . "'";
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
            
            if ($verify_info['Status'] == '1') {
                $barcode_trace['use_status'] = '2';
            } else if ($verify_info['Status'] == '0') {
                $barcode_trace['use_status'] = '0';
            }
            M('TbarcodeTrace')->where($where)->save($barcode_trace);
            
            // 统计
            $where = "NODE_ID ='" . $verify_info['ISSPID'] . "' and BATCH_NO ='" .
                 $verify_info['ActivityID'] . "' and POS_ID ='" .
                 $verify_info['TerminalId'] . "' and TRANS_DATE ='" .
                 date('Y-m-d') . "'  and b_id = " . $this->b_id;
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
            $where = "pos_id = '" . $verify_info['TerminalId'] .
                 "' and pos_seq = '" . $verify_info['org_pos_seq'] . "'";
            $ori_pos_trace = M('TposTrace')->where($where)->find();
            if (! $ori_pos_trace) {
                $this->log("未找到源流水[" . $verify_info['org_pos_seq'] . "]");
                $this->notifyreturn('0003');
            }
            // 更新源流水
            $new_pos_trace['status'] = '2';
            M('TposTrace')->where($where)->save($new_pos_trace);
            
            // 记流水
            $pos_trace = array();
            $pos_trace['pos_id'] = $verify_info['TerminalId'];
            $pos_trace['pos_seq'] = $verify_info['TerminalSeq'];
            $pos_trace['org_posseq'] = $verify_info['org_pos_seq'];
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
            $pos_trace['ori_syn_seq'] = $ori_pos_trace['ReqSeq'];
            $pos_trace['goods_id'] = $verify_info['goods_id'];
            $pos_trace['pos_node_id'] = $this->pos_node_id;
            $pos_trace['settle_amt'] = $verify_info['Amt'];
            // 排重处理
            $where = "pos_id ='" . $verify_info['TerminalId'] .
                 "' and pos_seq = '" . $verify_info['TerminalSeq'] . "'";
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
            
            if ($verify_info['Status'] == '1') {
                $barcode_trace['use_status'] = '2';
            } else if ($verify_info['Status'] == '0') {
                $barcode_trace['use_status'] = '0';
            }
            M('TbarcodeTrace')->where($where)->save($barcode_trace);
            
            // 统计
            $where = "NODE_ID ='" . $verify_info['ISSPID'] . "' and BATCH_NO ='" .
                 $verify_info['ActivityID'] . "' and POS_ID ='" .
                 $verify_info['TerminalId'] . "' and TRANS_DATE ='" .
                 date('Y-m-d') . "'  and b_id = " . $this->b_id;
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
                    $new_day_count['verify_num'] = $pos_day_count['verify_num'] -
                         1;
                    $new_day_count['verify_amt'] = $pos_day_count['verify_amt'] -
                         $verify_info['Amt'];
                    // 减少渠道表验证统计
                    $this->verify_channel_stat($this->channel_id, 2);
                } else // 撤销冲正
{
                    $new_day_count['cancel_num'] = $pos_day_count['cancel_num'] -
                         1;
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
    
    // 通知应答
    private function notifyreturn($resp_id = '0000') {
        $resp_xml = '<?xml version="1.0" encoding="gbk"?><' . $this->responseType .
             '><StatusCode>' . $resp_id . '</StatusCode></' . $this->responseType .
             '>';
        echo $resp_xml;
        $this->log($resp_xml, 'RESPONSE');
        exit();
    }
    
    // 记录日志
    protected function log($msg, $level = Log::INFO) {
        trace('Log.' . $level . ':' . $msg);
        log_write($msg, $level);
    }
}
