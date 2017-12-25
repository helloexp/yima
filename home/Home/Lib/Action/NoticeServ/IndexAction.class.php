<?php

/* ����֪ͨ�ӿ� */
class IndexAction extends Action {

    public $ReqArr;

    public $transType;

    public function index() {
        $reqXml = file_get_contents('php://input');
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml]����ʧ��');
        $xml = new Xml();
        
        $this->log($reqXml, 'REQUEST');
        $this->ReqArr = $xml->parse($reqXml);
        $this->transType = $xml->getRootName();
        
        if ($this->transType == 'NotifyNodeServ') {
            $this->notifynodeserv();
        } else if ($this->transType == 'NotifyPosServ') {
            $this->notifyposserv();
        } else if ($this->transType == 'NotifyNodeBill') {
            $this->notifynodebill();
        } else {
            $this->transType = 'ErrorRes';
            $this->notifyreturn('1000', '��Ч�Ľ������');
        }
    }
    
    // Ӫ���̻��Ʒ����³���֪
    private function notifynodeserv() {
        $node_id = $this->ReqArr['NotifyNodeServ']['NodeID'];
        $charge_list_new = $this->ReqArr['NotifyNodeServ']['NewServ']['Serv'];
        if ($charge_list_new && ! isset($charge_list_new[0]))
            $charge_list_new = array(
                $charge_list_new);
        
        $charge_list_stop = $this->ReqArr['NotifyNodeServ']['StopServ']['Serv'];
        if ($charge_list_stop && ! isset($charge_list_stop[0]))
            $charge_list_stop = array(
                $charge_list_stop);
            
            // �����ķ���
        foreach ($charge_list_new as &$charge_new) {
            $charge_id = $charge_new['Code'];
            
            if ($charge_id == '') // ���ֽ����ֵ������ޱ����
{
                break;
            }
            
            // ��¼����ҵ��
            $sql = "Replace into tnode_charge(node_id, charge_id,status, charge_level) values('" .
                 $node_id . "','" . $charge_id . "','0','1')";
            
            $rs = M('TnodeCharge')->execute($sql);
            if (! $rs) {
                $this->notifyreturn('0001', 'ͬ��ʧ��');
            }
        }
        
        // ֹͣ�ķ���
        foreach ($charge_list_stop as &$charge_stop) {
            $charge_id = $charge_stop['Code'];
            
            if ($charge_id == '') // ���ֽ����ֵ������ޱ����
{
                break;
            }
            
            // ��¼ֹͣҵ��
            $sql = "Replace into tnode_charge(node_id, charge_id,status, charge_level)				values('" .
                 $node_id . "','" . $charge_id . "','1','1')";
            $rs = M('TnodeCharge')->execute($sql);
            if (! $rs) {
                $this->notifyreturn('0001', 'ͬ��ʧ��');
            }
        }
        $this->notifyreturn();
    }
    
    // Ӫ���ն˼Ʒ����³���֪
    private function notifyposserv() {
        $node_id = $this->ReqArr['NotifyPosServ']['NodeID'];
        $pos_list = $this->ReqArr['NotifyPosServ']['PosList']['Pos'];
        if ($pos_list && ! isset($pos_list[0]))
            $pos_list = array(
                $pos_list);
        
        foreach ($pos_list as &$pos_info) {
            $pos_id = $pos_info['PosCode'];
            $status = $pos_info['PosStatus'];
            $charge_list_new = $pos_info['NewServ']['Serv'];
            if ($charge_list_new && ! isset($charge_list_new[0]))
                $charge_list_new = array(
                    $charge_list_new);
            
            $charge_list_stop = $pos_info['StopServ']['Serv'];
            if ($charge_list_stop && ! isset($charge_list_stop[0]))
                $charge_list_stop = array(
                    $charge_list_stop);
            
            if ($charge_id == '') // ���ֽ����ֵ������ޱ����
{
                break;
            }
            
            // �����ն�״̬
            $where = "NODE_ID = '" . $node_id . "' and POS_ID = '" . $pos_id .
                 "'";
            $rs = M('TposInfo')->where($where)->save(
                array(
                    'pos_status' => $status));
            if (! $rs) {
                $this->notifyreturn('0001', 'ͬ��ʧ��');
            }
            
            // �����ķ���
            foreach ($charge_list_new as &$charge_new) {
                $charge_id = $charge_new['Code'];
                
                if ($charge_id == '') // ���ֽ����ֵ������ޱ����
{
                    break;
                }
                
                // ��¼����ҵ��
                $sql = "Replace into tnode_charge(node_id, pos_id, charge_id,status, charge_level)				values('" .
                     $node_id . "','" . $pos_id . "','" . $charge_id .
                     "','0','2')";
                $rs = M('TnodeCharge')->execute($sql);
                if (! $rs) {
                    $this->notifyreturn('0001', 'ͬ��ʧ��');
                }
            }
            
            // ֹͣ�ķ���
            foreach ($charge_list_stop as &$charge_stop) {
                $charge_id = $charge_stop['Code'];
                
                if ($charge_id == '') // ���ֽ����ֵ������ޱ����
{
                    break;
                }
                
                // ��¼ֹͣҵ��
                $sql = "Replace into tnode_charge(node_id, pos_id, charge_id,status, charge_level)				values('" .
                     $node_id . "','" . $pos_id . "','" . $charge_id .
                     "','1','2')";
                $rs = M('TnodeCharge')->execute($sql);
                if (! $rs) {
                    $this->notifyreturn('0001', 'ͬ��ʧ��');
                }
            }
        }
        $this->notifyreturn();
    }
    
    // Ӫ���˵�֪ͨ
    private function notifynodebill() {
        $bill_id = $this->ReqArr['NotifyNodeBill']['BillSeq'];
        $bill_info = $this->ReqArr['NotifyNodeBill']['Desc'];
        $bill_amt = $this->ReqArr['NotifyNodeBill']['TotalAmount'];
        $invoice_status = $this->ReqArr['NotifyNodeBill']['BillingStatus'];
        // $node_id = $this->ReqArr['NotifyNodeBill']['NodeID'];
        $bill_month = $this->ReqArr['NotifyNodeBill']['Month'];
        $contract_no = $this->ReqArr['NotifyNodeBill']['ContractID']; // �����
                                                                      
        // ��ȡ�������
        $top_node_arr = array();
        $where = "contract_no ='" . $contract_no . "'";
        $node_rs = M('TnodeInfo')->where($where)
            ->order('node_id')
            ->select();
        if (! $node_rs) {
            $this->notifyreturn('0001', 'ͬ��ʧ��');
            return;
        }
        foreach ($node_rs as $node) {
            $node_tree = explode(',', $node['full_id']);
            if ($node_tree[0] === $node['node_id']) { // �������
                $top_node_arr[] = $node['node_id'];
                break;
            } else {
                $find_flag = false;
                // Ѱ��·���д��ڵ����ϲ����
                foreach ($node_tree as $val) {
                    // �鿴�����Ƿ����
                    foreach ($node_rs as $node_tmp) {
                        if ($node_tmp['node_id'] == $val) {
                            $top_node_arr[] = $node_tmp['node_id'];
                            $find_flag = true;
                            break;
                        }
                    }
                    if ($find_flag)
                        break;
                }
            }
        }
        if (($top_node_arr == null) || (count($top_node_arr) == 0)) {
            $this->notifyreturn('0001', 'ͬ��ʧ��');
            return;
        }
        $top_node_arr = array_unique($top_node_arr);
        foreach ($top_node_arr as $top_node) {
            // ��¼�˵�
            $sql = "Replace into tbill_info(bill_id, bill_info, bill_amt, invoice_status, node_id, bill_month, contract_no) values('" .
                 $bill_id . "','" . iconv("gbk", "utf-8", $bill_info) . "','" .
                 $bill_amt . "','" . $invoice_status . "','" . $top_node . "','" .
                 $bill_month . "','" . $contract_no . "')";
            
            $rs = M('TbillInfo')->execute($sql);
            if (! $rs) {
                $this->notifyreturn('0001', 'ͬ��ʧ��');
                break;
            }
        }
        $this->notifyreturn();
    }
    
    // ֪ͨӦ��
    private function notifyreturn($resp_id = '0000', $resp_desc = 'ͬ���ɹ�') {
        $resp_xml = '<?xml version="1.0" encoding="gbk"?><' . $this->transType .
             '><Status><StatusCode>' . $resp_id . '</StatusCode><StatusText>' .
             $resp_desc . '</StatusText></Status></' . $this->transType . '>';
        echo $resp_xml;
        $this->log($resp_xml, 'RESPONSE');
        exit();
    }
    
    // ��¼��־
    protected function log($msg, $level = Log::INFO) {
        trace('Log.' . $level . ':' . $msg);
        Log::write($msg, $level);
    }
}