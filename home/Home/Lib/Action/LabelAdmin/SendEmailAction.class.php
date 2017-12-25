<?php

/**
 * 发送邮件
 *
 * @author six
 */
class SendEmailAction extends BaseAction {

    public function send_email() {
        $contact_phone = I('contact_phone', null);
        $contact_eml = I('contact_eml', null);
        $qq = I('qq', null);
        $sendTime = date('Y-m-d H:i:s');
        if (! $contact_phone || ! $contact_eml)
            $this->error('手机号码或者联系邮箱不得为空', 
                array(
                    '返回电商首页' => U('Home/Index/marketingShow5')));
        $nodeInfo = M('tnode_info')->where("node_id='{$this->nodeId}'")->find();
        
        // 判断是否今天发过申请邮件
        $count = M('tsend_email_trace')->where(
            array(
                'node_id' => $this->nodeId, 
                'send_time' => array(
                    'like', 
                    date('Ymd') . "%")))->count();
        if ($count > 0) {
            $this->error('您账户所属商户今天已经发送过申请邮件，无需再次发送', 
                array(
                    '返回电商首页' => U('Home/Index/marketingShow5')));
        }
        
        $content = "旺号：{$nodeInfo['client_id']}<br>真实姓名：{$nodeInfo['contact_name']}<br/>手机号码：{$contact_phone}<br/>邮箱：{$contact_eml}<br/>公司名称：{$nodeInfo['node_name']}<br/>QQ：{$qq}<br/>申请时间：{$sendTime}<br/>";
        $ps = array(
            "subject" => "旺财平台商品销售类业务权限审核", 
            "content" => $content, 
            "email" => "qianwen@imageco.com.cn");
        $resp = send_mail($ps);
        if ($resp['sucess'] == '1') {
            $data = array(
                'node_id' => $nodeInfo['node_id'], 
                'contact_phone' => $contact_phone, 
                'contact_eml' => $contact_eml, 
                'qq' => $qq, 
                'send_time' => date('YmdHis'));
            $result = M('tsend_email_trace')->add($data);
            $this->success("您的申请已提交！旺小二稍后会联系您介绍开通多宝电商的相关事宜！", 
                array(
                    '返回电商首页' => U('Home/Index/marketingShow5')));
        } else {
            $this->error("商品销售类业务权限申请失败，邮件发送失败", 
                array(
                    '返回电商首页' => U('Home/Index/marketingShow5')));
        }
    }
}