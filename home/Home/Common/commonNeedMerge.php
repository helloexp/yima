<?php

// 动态转成utf-8
function utf8Str(&$str)
{
    $str = function_exists('mb_convert_encoding') ? mb_convert_encoding($str, 'utf-8', 'utf-8,gbk') : $str;
    return $str;
}

// 动态转成utf-8
function utf8Array(&$arr)
{
    if (! is_array($arr)) {
        return false;
    }
    $str = implode("|", $arr);
    $str = function_exists('mb_convert_encoding') ? mb_convert_encoding($str, 'utf-8', 'gbk') : $str;
    $arr = explode('|', $str);
    return $arr;
}

/**
 * 权限验证
 *
 * @param $nodeId   机构号
 * @param $chargeId 服务项id
 */
function checkUserRights($nodeId, $chargeId)
{
    // 商户信息
    // $nodeInfo = M('tnode_info')->where("node_id={$nodeId}")->find();
    $nodeInfo = get_node_info($nodeId);
    // 演示用户不用验证
    if ($nodeInfo['node_type'] == 4) {
        return true;
    }
    // 粉丝计费项和基础平台计费项取消,所有付费用户,翼码,演示用户可用
    if (($chargeId == '3003' || $chargeId == '3050') && (verify_val($nodeInfo, 'node_type', 0, '==') || verify_val($nodeInfo, 'node_type',  1, '==') || verify_val($nodeInfo, 'node_type',  4, '=='))) {
        return true;
    }
    // 获取商户开通的服务
    $where = array(
        'node_id' => $nodeId, 
        'charge_id' => $chargeId, 
        'status' => '0', 
        'charge_level' => '1');
    $count = M('tnode_charge')->where($where)->count();
    // echo M()->getLastSql();exit;
    if ($count == 0) {
        return false;
    } else {
        return true;
    }
}

/**
 * func send_mail() 发送邮件
 *
 * @param array $ps [] string $ps['smtp_server'] SMTP服务器 （非必填
 *            缺省值'smtp.263xmail.com'） string $ps['smtp_auth']
 *            true是表示使用身份验证,否则不使用身份验证 （非必填 缺省值'TRUE'） string $ps['smtp_user']
 *            SMTP服务器的用户帐号 （非必填 缺省值'noreply@imageco.com.cn'） string
 *            $ps['smtp_pass'] SMTP服务器的用户密码 （非必填 缺省值'nlnoreply'） string
 *            $ps['smtp_mail'] SMTP服务器的用户邮箱 （非必填 缺省值'noreply@imageco.com.cn'）
 *            array $ps['email'] 发送给谁 单个（必填） string $ps['subjiect'] 邮件主题 （必填）
 *            array $ps['content'][] 邮件内容 数组格式 （必填） string $ps['type']
 *            邮件格式（HTML/TXT）,TXT为文本邮件 （非必填 缺省值'HTML'） array $ps['code'][] 附加码
 *            （非必填）
 * @return array $resp[] string $resp['resp_id'] 返回码 string $resp['resp_desc']
 *         返回描述
 */
function send_mail($ps)
{
    if (unsetOrIsEmpty($ps, 'subject')) {
        $res = array(
            "sucess" => false, 
            "msg" => "缺少邮件主题");
        return $res;
    }
    if (unsetOrIsEmpty($ps, 'content')) {
        $res = array(
            "sucess" => false, 
            "msg" => "缺少邮件内容");
        return $res;
    }
    if (unsetOrIsEmpty($ps, 'email')) {
        $res = array(
            "sucess" => false, 
            "msg" => "缺少发送email地址或参数格式不正确");
        return $res;
    }
    if (! class_exists('email')) {
        import('@.ORG.Com.Email') or die('[@.ORG.Com.Email]导入包失败');
    }
    
    if (unsetOrIsEmpty($ps, 'smtp_server')) {
        $ps['smtp_server'] = 'smtp.263xmail.com';
    }
    if (unsetOrIsEmpty($ps, 'smtp_auth')) {
        $ps['smtp_auth'] = true;
    }
    if (unsetOrIsEmpty($ps, 'smtp_user')) {
        $ps['smtp_user'] = 'wangcai01@imageco.com.cn';
    }
    if (unsetOrIsEmpty($ps, 'smtp_pass')) {
        $ps['smtp_pass'] = 'imageco123';
    }
    if (unsetOrIsEmpty($ps,'smtp_mail')) {
        $ps['smtp_mail'] = 'wangcai01@imageco.com.cn';
    }
    if (unsetOrIsEmpty($ps,'type')) {
        $ps['type'] = 'HTML';
    }
    $params = array(
        'smtp_server' => get_val($ps, 'smtp_server'),  // SMTP服务器
        'smtp_auth' => get_val($ps, 'smtp_auth'),  // true是表示使用身份验证,否则不使用身份验证
        'smtp_user' => get_val($ps, 'smtp_user'),  // SMTP服务器的用户帐号
        'smtp_pass' => get_val($ps, 'smtp_pass'),// SMTP服务器的用户密码
);
    
    $smtp = new smtp($params);
    
    $params = array(
        'smtp_mail' => get_val($ps, 'smtp_mail'),  // SMTP服务器的用户邮箱
        'subject' => get_val($ps, 'subject'),  // 邮件主题
        'type' => get_val($ps, 'type'),
    ); // 邮件格式（HTML/TXT）,TXT为文本邮件
    
    $params['smtp_mail_to'] = get_val($ps, 'email'); // 发送给谁
    $params['content'] = get_val($ps, 'content'); // 邮件内容
    $send = $smtp->sendmail($params); // 调用发邮件方法
    if (! $send) {
        $res = array(
            "sucess" => false, 
            "msg" => "发送失败");
        return $res;
    }
    $res = array(
        "sucess" => true, 
        "msg" => "发送成功");
    return $res;
}

/**
 * 方法：to_email($content) 功能:发邮件 参数：数组 返回值：成功 2 失败 1
 */
function to_email($content)
{
    Vendor('PHPMailer.class', '', '.phpmailer.php'); // 导入第三方邮件库
    $mail = new PHPMailer();
    $mail->isSMTP(); // Set mailer to use SMTP
    $mail->Host = 'smtp.263xmail.com'; // Specify main and backup SMTP servers
    $mail->SMTPAuth = true; // Enable SMTP authentication
    $mail->Username = 'noreply@imageco.com.cn'; // SMTP username
    $mail->Password = 'nlnoreply'; // SMTP password
                                   // $mail->SMTPSecure = 'tls'; // Enable TLS
                                   // encryption, `ssl` also accepted
    $mail->CharSet = 'utf-8';
    $mail->From = 'noreply@imageco.com.cn'; // 发件人
    $mail->FromName = 'noreply'; // 发件人别名
    $mail->Port = 25; // 端口
    $mail->addAddress($content['petname']);
    $cc = get_val($content, 'CC');
    if (is_array($cc)) {
        foreach ($cc as $val) {
            $mail->addCC($val); // 抄送人 可多次调用添加多个抄送人
        }
    } else {
        $mail->addCC($cc); // 抄送1人
    }
    // $mail->WordWrap = 50; // Set word wrap to 50 characters
    $mail->isHTML(true); // 以HTML发送
    $mail->Subject = get_val($content, 'test_title'); // 邮件标题
    $mail->Body = get_val($content, 'text_content'); // 邮件内容
                                            // 处理附件 如果add_file为array则表示多附件
    $add_file = get_val($content, 'add_file');
    if (is_array($add_file)) {
        foreach ($add_file as $v) {
            $mail->addAttachment($v);
        } // 附件
    } else {
        $mail->addAttachment($add_file);
    } // 附件
    
    if (! $mail->send()) {
        return '1'; // 失败
    } else {
        return '2'; // 成功
    }
}
