<?php

/**
 * goods_id生成
 *
 * @return string
 */
function get_goods_id()
{
    $data = M()->query("SELECT _nextval('goods_id') as goods_id FROM DUAL");
    if (! $data) {
        $this->error('商品id生成失败');
    }
    $goodsId = $data[0]['goods_id'];
    return 'gw' . str_pad($goodsId, 10, '0', STR_PAD_LEFT);
}

/**
 * 获取营销活动数量
 *
 * @param $batch_type
 * @param $nodeId
 * @return mixed
 */
function getBatchNum($batch_type, $nodeId)
{
    $whereStr = R('Base/nodeIn', array($nodeId));
    $count    = M('tmarketing_info')->where("node_id IN({$whereStr}) and batch_type = '" . $batch_type . "'")->count();
    return $count;
}

/**
 * 根据手机号添加粉丝
 *
 * @param array $memberArr 粉丝信息数组(键名要对应tmember_info表里面的字段)
 * @param int $batchChannelId 表tbatch_channel的id
 * @param int $nodeId 商户号
 *
 * @return bool
 */
function add_member_by_mobile($memberArr, $channelId, $batchId, $nodeId, $full_id)
{
    return true;
    $memberKey = array(
        'name' => '', 
        'phone_no' => '', 
        'sex' => '', 
        'age' => '');
    $memberArr = array_intersect_key($memberArr, $memberKey);
    if (empty($memberArr['phone_no'])) {
        return false;
    }
    $memberModel = M('tmember_info_tmp');
    // 校验手机号是否存在
    $num = $memberModel->where(
        "phone_no='{$memberArr['phone_no']}' AND node_id='{$nodeId}'")->count();
    if ($num == 0) { // 添加会员
        if (! empty($full_id)) {
            $full_arr = explode(',', $full_id);
            $query_arr = M('tbatch_channel')->where(
                "node_id='{$nodeId}' and id={$full_arr[0]}")->find();
            if ($query_arr) {
                $channelId = $query_arr['channel_id'];
                $batchId = $query_arr['batch_id'];
            }
        }
        
        $memberArr['node_id'] = $nodeId;
        $memberArr['full_id'] = $full_id;
        $memberArr['add_time'] = date('YmdHis');
        $memberArr['channel_id'] = $channelId;
        $memberArr['batch_id'] = $batchId;
        $memberArr['join_num'] = '1';
        $result = $memberModel->add($memberArr);
        if ($result) {
            return true;
        }
    } else { // 参与次数加1
        $result = $memberModel->where(
            "phone_no='{$memberArr['phone_no']}' AND node_id={$nodeId}")->setInc(
            'join_num');
        if ($result) {
            return true;
        }
    }
    ;
    return false;
}

/**
 *
 * @param $msg_type
 * @param $msg_info
 * @return string
 */
function chat_msg_show($msg_type, $msg_info)
{
    switch ($msg_type) {
        case '0': // 文本
            $html = $msg_info;
            break;
        case '5': // 点击菜单
            $html = $msg_info;
            break;
        case '1': // 图片
            if (basename($msg_info) == $msg_info) {
                $msg_info = './Home/Upload/Weixin/' . $msg_info;
            } else {
                $msg_info = get_upload_url($msg_info);
            }
            $html = <<<HTML
<a href="{$msg_info}" target="_blank" class="media_img">
    <img class="wxmImg Zoomin" src="{$msg_info}">
</a>
HTML;
            break;
        case '2': // 图文
            $arr = json_decode($msg_info, true);
            if (is_array($arr)) {
                // foreach($arr['Articles'] as $art){
                $art = isset($arr['articles']) ? $arr['articles'][0] : $arr['Articles'][0];
            } else {
                $list = M('twx_material')->find($arr);
                $art['picurl'] = $list['material_img'];
                $art['url'] = $list['material_link'];
                $art['title'] = $list['material_title'];
                $art['description'] = $list['material_summary'];
            }
            if (basename($art['picurl']) == $art['picurl']) {
                $art['picurl'] = './Home/Upload/Weixin/' . $art['picurl'];
            } else {
                $art['picurl'] = get_upload_url($art['picurl']);
            }
            $html = <<<HTML
<div class="appmsgImgArea">
	<img src="{$art['picurl']}" />
</div>
<div class="appmsgContentArea">
	<div class="appmsgTitle">
        <a href="{$art['url']}" target="_blank">[图文消息]{$art['title']}</a>
    </div>
    <a href="{$art['url']}" target="_blank" class="appmsgDesc">{$art['description']}</a>
</div>
HTML;
            // }
            break;
        case '3':
            $html = <<<HTML
<div class="mediaBox audioBox">
	<div class="mediaContent">
		<span class="audioTxt">语音消息</span>
        <!-- <b>3"</b> -->
		<span class="iconAudio"></span>
	</div>
</div>
HTML;
            break;
        default:
            $html = '';
            break;
    }
    
    return $html;
}

/**
 *
 * @param $contract_no
 * @return bool
 */
function QueryJsIDReq($contract_no)
{
    $RemoteRequest = D('RemoteRequest', 'Service');
    $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
    $req_array = array(
        'QueryJsIDReq' => array(
            'TransactionID' => $TransactionID, 
            'SystemID' => C('YZ_SYSTEM_ID'), 
            'ContractID' => $contract_no));
    $contractInfo = $RemoteRequest->requestYzServ($req_array);
    if ($contractInfo['Status']['StatusCode'] != '0000') {
        return false;
    }
    
    return $contractInfo;
}

/**
 *
 * @param $long_url
 * @return bool|string
 */
function create_sina_short_url($long_url)
{
    if (strpos($long_url, 'http://t.cn') === 0) {
        return $long_url;
    }
    $api = 'http://api.weibo.com/2/short_url/shorten.json?source=4294557489&url_long=' .
         urlencode($long_url);
    do {
        $error = '';
        $result_str = httpPost($api, '', $error, 
            array(
                'TIMEOUT' => 30, 
                'METHOD' => 'GET'));
        $result = json_decode($result_str, true);
        if (! is_array($result) || ! isset($result['urls'])) {
            continue;
        }
        
        $url = $result['urls'][0]['url_short'];
        break;
    }
    while (++ $i < 3);
    
    if ($url == '') {
        return false;
    }
    return $url;
}

/**
 * 获取token
 *
 * @param $appId
 * @param $appSecret
 * @return mixed
 */
function getToken($appId, $appSecret)
{
    $error = '';
    $apiUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' .
         $appId . '&secret=' . $appSecret . '';
    $result = httpPost($apiUrl, '', $error, 
        array(
            'TIMEOUT' => 30, 
            'METHOD' => 'GET'));
    $result = json_decode($result, true);
    $accessToken = $result['access_token'];
    return $accessToken;
}

/**
 * 获取ticket
 *
 * @param $token
 * @return mixed
 */
function getTicket($token)
{
    $error = '';
    $apiUrl = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' .
         $token . '&type=jsapi';
    $result = httpPost($apiUrl, '', $error, 
        array(
            'TIMEOUT' => 30, 
            'METHOD' => 'GET'));
    $result = json_decode($result, true);
    $Ticket = $result['ticket'];
    return $Ticket;
}

/**
 * 页面调用方法，生成Gform的radio
 *
 * @param $name
 * @param array $opt
 * @param $value
 * @return string
 */
function Gform_radio($name, array $opt, $value)
{
    $hidden_html = '<input type="radio" name="' . $name . '" id="' . $name .
         '" value="' . $value . '" checked="checked">';
    $opt_html = '';
    foreach ($opt as $k => $v) {
        $checked = $hover = '';
        if ($k == $value) {
            $checked = 'checked';
            $hover = 'hover';
        }
        $opt_html .= '<span class="' . $hover . '" data-val="' . $k . '">' . $v .
             '</span>';
    }
    return $hidden_html . '<div class="newRadio">' . $opt_html . '</div>';
}

/**
 * 页面调用方法，生成Gform的checkbox
 *
 * @param $name
 * @param array $opt
 * @param array $value
 *
 * @return string
 */
function Gform_checkbox($name, array $opt, array $value = array())
{
    $hidden_html = $opt_html = '';
    foreach ($opt as $k => $v) {
        $checked = $hover = '';
        if (in_array($k, $value)) {
            $checked = 'checked="checked"';
            $hover = 'hover';
        }
        $hidden_html .= '<input type="checkbox" name="' . $name . '" value="' .
             $k . '" ' . $checked . '>';
        $opt_html .= '<span class="' . $hover . '" data-val="' . $k . '">' . $v .
             '</span>';
    }
    return $hidden_html . '<div class="newRadio">' . $opt_html . '</div>';
}

/**
 * 增加消息数
 *
 * @param $where
 * @param int $step
 */
function add_msgstat($where, $step = 1)
{
    $result = M('tmessage_stat')->where($where)->find();
    if ($result) {
        M('tmessage_stat')->where($where)->setInc('total_cnt', $step);
        M('tmessage_stat')->where($where)->setInc('new_message_cnt', $step);
        M('tmessage_stat')->where($where)->save(
            array(
                'last_time' => date('YmdHis')));
    } else {
        M('tmessage_stat')->add(
            array(
                'node_id' => $where['node_id'], 
                'message_type' => $where['message_type'], 
                'total_cnt' => $step, 
                'new_message_cnt' => $step, 
                'last_time' => date('YmdHis')));
    }
}

/**
 */
function df_wx_login()
{
    if (session('?node_wxid_' . C('df.node_id'))) {
        return;
    }
    $backurl = U('', I('get.'), '', '', true);
    $backurl = urlencode($backurl);
    $jumpurl = U('Df/DFWeixinLoginNode/index', 
        array(
            'type' => 0, 
            'backurl' => $backurl));
    redirect($jumpurl);
}

/**
 * 获取指定日期对应星座
 *
 * @param integer $month 月份 1-12
 * @param integer $day 日期 1-31
 * @param integer $return_need 0返回星座名称1返回星座数组有效key1-12,2返回$signs_name数组
 * @return boolean|string
 */
function getConstellation($month, $day, $return_need = 1)
{
    $signs_name = array(
        '1' => '水瓶座', 
        '2' => '双鱼座', 
        '3' => '白羊座', 
        '4' => '金牛座', 
        '5' => '双子座', 
        '6' => '巨蟹座', 
        '7' => '狮子座', 
        '8' => '处女座', 
        '9' => '天秤座', 
        '10' => '天蝎座', 
        '11' => '射手座', 
        '12' => '摩羯座');
    if ($return_need == 2) {
        return $signs_name;
    }
    $day = intval($day);
    $month = intval($month);
    if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
        return false;
    }
    $signs = array(
        array(
            '20' => '水瓶座'), 
        array(
            '19' => '双鱼座'), 
        array(
            '21' => '白羊座'), 
        array(
            '20' => '金牛座'), 
        array(
            '21' => '双子座'), 
        array(
            '22' => '巨蟹座'), 
        array(
            '23' => '狮子座'), 
        array(
            '23' => '处女座'), 
        array(
            '23' => '天秤座'), 
        array(
            '24' => '天蝎座'), 
        array(
            '22' => '射手座'), 
        array(
            '22' => '摩羯座'));
    list ($start, $name) = each($signs[$month - 1]);
    if ($day < $start) {
        list (, $name) = each($signs[($month - 2 < 0) ? 11 : $month - 2]);
    }
    if ($return_need == 0) {
        return $name;
    }
    if ($return_need == 1) {
        $signs_name = array_flip($signs_name);
        return $signs_name[$name];
    }
    
    return false;
}