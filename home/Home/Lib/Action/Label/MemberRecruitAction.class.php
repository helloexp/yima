<?php

/**
 * 会员招募 前端WAP显示 Class MemberRecruitAction
 *
 * @author Jeff Liu @date 2015-07-08
 */
class MemberRecruitAction extends MyBaseAction {

    /**
     * input placeholder属性
     *
     * @var array
     */
    private static $placeholder_mapping = array(
        'name' => '请输入您的姓名', 
        'phone_no' => '请输入手机号', 
        'birthday' => '请输入您的生日');

    public function _initialize() {
        parent::_initialize();
        
        import('@.Service.MemberRecruitService') or
             die('导入MemberRecruitService包失败');
        
        $this->_checkUser(true); // 判断是否需要登录微信(需要的话,直接登录微信)
    }

    /**
     * 会员招募前端页面展示
     *
     * @author Jeff Liu
     */
    public function index() {
        $id = I('id', null, 'mysql_real_escape_string'); // channel_id
                                                         
        // 标签
        $result = MemberRecruitService::getBatchChannelData($id, $this->batch_type);
        if (! $result) { // 没有对应标签信息
            $this->showErrorByErrno(- 1003);
        }
        // 活动
        $m_id = $result['batch_id']; // 活动id tbatch_channel 的batch_id 对应 tmarketing_info 的id 坑爹玩意
        $marketing_info = MemberRecruitService::getMarketingInfo($m_id, $this->batch_type);
        $save_info = array(
            'id' => $id, 
            'batch_type' => $this->batch_type,  // 活动类型
            'batch_id' => $result['id'], 
            'm_id' => $marketing_info['id'],  // 活动id
            'node_id' => $marketing_info['node_id'],  // 机构id
            'select_type' => $marketing_info['select_type'], 
            'is_cj' => $marketing_info['is_cj'],  // 是否需要抽奖
            'is_send' => $marketing_info['is_send'],  // 是否需要发码
            'member_level' => $marketing_info['member_level'],  // 会员等级
            'channel_id' => $result['channel_id'],  // 渠道id
            'member_card_id' => $marketing_info['member_card_id']); // 会员卡id
                                                                    
        // 微信会员判断
        $sId = I('s_id', null, 'mysql_real_escape_string');
        $login_time = I('login_time', null);
        if (isset($sId) && isset($login_time)) {
            // 微信会员相关信息验证
            $login_info = $this->verifyWeChatMember($marketing_info['node_id'], $sId, $login_time);
            $save_info['s_id'] = $sId;
            
            // 获取会员卡信息
            $member_cards_info = MemberRecruitService::getMemberCardInfo($marketing_info['node_id'], $login_info['phone_no']);
            // 会员卡信息处理
            $this->memberCardsInfoProcess($member_cards_info);
        }
        
        // 更新点击数 todo 这个可以优化。只进行记录 不进行统计，统计放在一个单独的地方进行处理 比较好。
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        session('saveInfo', $save_info);
        
        $bg_img = get_upload_url($marketing_info['bg_pic']);
        $this->assign('bg_img', $bg_img);
        // 动态构建 表单 html
        $form_html_content = self::_buildHTMLElement($marketing_info);
        $question_list = MemberRecruitService::getCollectQuestionInfo($marketing_info['id']);
        if ($question_list && is_array($question_list)) {
            foreach ($question_list as $question) {
                switch ($question['name']) {
                    case 'name':
                        $this->assign('requeired_name', 
                            $question['is_required']);
                        $this->assign('name_id', $question['id']);
                        break;
                    case 'birthday':
                        $this->assign('requeired_birthday', 
                            $question['is_required']);
                        $this->assign('birthday_id', $question['id']);
                        break;
                    case 'area':
                        $this->assign('requeired_area', 
                            $question['is_required']);
                        break;
                    default:
                        break;
                }
            }
        }
        
        $this->assign('form_html_content', $form_html_content);
        $this->assign('expiresTime',MemberRecruitService::VERIFY_CODE_EXPIRE_TIME);
        $this->assign('id', $id);
        $this->assign('row', $marketing_info);
        $this->assign('config', unserialize($marketing_info['config_data']));
        $this->display("index{$marketing_info['page_style']}");
    }

    /**
     * 微信会员相关信息判断
     *
     * @author Jeff Liu
     * @param string $node_id 机构id
     * @param string $sId 微信id
     * @param int $login_time 登陆时间
     * @return mixed
     */
    private function verifyWeChatMember($node_id, $sId, $login_time) {
        // 获取
        $login_Info = MemberRecruitService::verifyWeChatMember($node_id, $sId, 
            $login_time);
        if (is_scalar($login_Info) && $login_Info < 0) {
            $this->showErrorByErrno($login_Info);
        }
        
        return $login_Info;
    }

    /**
     * 会员卡信息 处理
     *
     * @author Jeff Liu
     * @param array $member_cards_info 会员卡信息
     */
    private function memberCardsInfoProcess($member_cards_info) {
        $ret = MemberRecruitService::memberCardsInfoProcess($member_cards_info);
        $this->assign('code_img', $ret['code_img']);
        $this->assign('member_cards_info', $ret['member_cards_info']);
        $this->assign('is_show', $ret['is_show']);
    }

    /**
     * 构建form html 内容
     *
     * @author Jeff Liu
     * @param array $market_info
     *
     * @return string
     */
    private static function _buildHTMLElement($market_info) {
        $html_content_list = array();
        $question_list = MemberRecruitService::getCollectQuestionInfo($market_info['id']);
        if ($question_list && is_array($question_list)) {
            foreach ($question_list as $question) {
                switch ($question['type']) {
                    case 1: // 单选
                        if($question['name'] != 'sex'){
                            $html_content_list[] = self::_typeisRadioAndShowSelect($question);
                        }else{
                            $html_content_list[] = self::_assembleRadioHtmlElement($question, $market_info['page_style']);
                        }
                        break;
                    case 2: // 多选
                        $html_content_list[] = self::_assembleCheckboxHtmlElement(
                            $question, $market_info['page_style']);
                        break;
                    case 4: // select
                        $html_content_list[] = self::_assembleSelectHtmlElement($question);
                        break;
                    case 3: // 文本
                    default:
                        if ($question['name'] != 'member_tel') { // 手机号先过滤掉
                            $html_content_list[] = self::_assembleTextHtmlElement(
                                $question);
                        }
                        break;
                }
            }
        }
        
        return $html_content_list;
    }
    
    /**
     * 会员招募单项选择框
     * @param type $question
     */
    private function _typeisRadioAndShowSelect($question, $name){
        $value_list = isset($question['value_list']) ? explode('|',$question['value_list']) : array();
        $label_html = self::_assembleLabelHtmlElement($question, '', false);
        $htmlStr = '<select name='.$question['name'].'><option value="">请选择</option>';
        foreach($value_list as $val){
            $tempArray = explode(':', $val);
            $htmlStr .= '<option value="'.$tempArray[0].'">'.$tempArray[1].'</option>';
        }
        $htmlStr .= '</select>';
        $result = $label_html.$htmlStr;
        return $result;
    }

    /**
     * todo 构建 select 标签 这个还需要修改为通用逻辑
     *
     * @param $row
     * @return string
     */
    private static function _assembleSelectHtmlElement($row) {
        if (MemberRecruitService::AREA_SELECT_STYLE) {
            $content = '<p class="tit">%s</p>
                    <p class="cont">
                        <select name="province" id="province" class="textbox w100">
                            <option value="" style="max-width:70px;">省</option>
                        </select>
                        <select name="city" id="city" class="textbox w100" style="max-width:65px;">
                            <option value="">市</option>
                        </select>
                        <select name="town" id="town" class="textbox w100">
                            <option value="">区</option>
                        </select>
                    </p>';
            $label_html = self::_assembleLabelHtmlElement($row, '', false);
            
            $script_files = '<script type="text/javascript" src="__PUBLIC__/Js/citycode.js?v=__VR__"></script>';
            $script_content = '<script type="text/javascript" >$(document).ready(function (e) {
                CityCode({
                    province: $("#province"),//省
                    city: $("#city"),//市
                    town: $("#town"),//区
                    selected: "",' . 'url: "' .
                 U('LabelAdmin/AjaxCity/index') . '"}' . ');});</script>';
            
            return $script_files . sprintf($content, $label_html) .
                 $script_content;
        } else {
            $hidden_input = '<input type="hidden" name="province" id="province" />
            <input type="hidden" name="city" id="city" />
            <input type="hidden" name="town" id="town" />';
            $script_files = '<script type="text/javascript" src="__PUBLIC__/Js/zepto.js"></script>
                            <script type="text/javascript" src="__PUBLIC__/Js/WMdialog.min.js"></script>
                            <script type="text/javascript" src="__PUBLIC__/Js/WMmobile-select-area.js"></script>
                            <link href="__PUBLIC__/Css/select_areaDialog.css?v=__VR__" rel="stylesheet" type="text/css"/>';
            $select_area = '<p class="tit">所在区域</p>
                    <p class="cont">
                    <input type="text" id="txt_area" name="address" class="input" value="上海 上海市 浦东新区"/>
                    <input type="hidden" id="hd_area" name="hidden_address" value="09,021,012"/>
                    </p>';
            $script_content = '<script>$(document).ready(function (e) {var selectArea = new MobileSelectArea();selectArea.init({trigger:"#txt_area",value:$("#hd_area").val(),data:"__PUBLIC__/data.json"});})</script>';
            
            return $script_files . $hidden_input . $select_area . $script_content;
        }
    }

    /**
     *
     * @author Jeff Liu
     * @param array $row
     *
     * @return string
     */
    private static function _assembleRequiredHtmlElement($row) {
        $required_html_element = '';
        if (isset($row['is_required']) && $row['is_required']) {
            $required_html_element = '<span class="required">*</span>';
        }
        
        return $required_html_element;
    }

    /**
     *
     * @author Jeff Liu
     * @param array $row 会员招募所需的字段信息
     * @param string $text input的text属性
     * @param bool $useLabel 是否使用label标签
     * @return string
     */
    private static function _assembleLabelHtmlElement($row, $text = '', 
        $useLabel = true) {
        $requied_html = self::_assembleRequiredHtmlElement($row);
        if (empty($text)) {
            $text = $row['text'];
        }
        
        if ($useLabel) {
            $format = '<p class="tit">%s<label for="%s">%s</label>:</p>';
            
            return sprintf($format, $requied_html, $row['id'], $text);
        } else {
            $format = '<p class="tit">%s%s</p>';
            
            return sprintf($format, $requied_html, $text);
        }
    }

    /**
     * 组成 text html标签
     *
     * @author Jeff Liu
     * @param array $row 会员招募所需的字段信息
     * @return string
     */
    private static function _assembleTextHtmlElement($row) {
        $format = '%s<p class="cont"><input type="%s" maxlength="15" placeholder="%s" name="%s" value="%s" id="%s" class="input" %s %s></p>';
        $label_html = self::_assembleLabelHtmlElement($row, '', false);
        $name = self::_buidlHtmlElementName($row);
        if (isset(self::$placeholder_mapping[$row['name']])) {
            $placeholder = self::$placeholder_mapping[$row['name']];
        } else {
            $placeholder = '';
        }
        $value = isset($row['value']) ? $row['value'] : '';
        $id = isset($row['id']) ? $row['id'] : '';
        $type = 'text';
        $onclick = '';
        $type_format = '';
        if ($row['name'] == 'phone_no') {
            $type = 'tel';
        } elseif ($row['name'] == 'birthday') { // 方便手机端显示
            $type = 'date';
            $type_format = 'format="yyyy-mm-dd"';
        }
        
        return sprintf($format, $label_html, $type, $placeholder, $name, $value, 
            $id, $onclick, $type_format);
    }

    /**
     * 构建 html element 的name属性
     *
     * @param array $row 会员招募所需的字段信息
     * @return string
     */
    private static function _buidlHtmlElementName($row) {
        $origin_type = isset($row['type']) ? $row['type'] : '';
        $id = isset($row['id']) ? $row['id'] : '';
        if (isset($row['is_base_field']) && $row['is_base_field'] == 1) { // 基础字段
                                                                          // 直接显示原有的name
            return $row['name'];
        } else { // 自定义 重新构建name属性
            return MemberRecruitService::CUSTMOM_HTML_PREFIX . $origin_type . '_' .
                 $id;
        }
    }

    /**
     * 构建raido 或者 checkbox
     *
     * @param array $row 会员招募所需的字段信息
     * @param string $type input 类型
     * @param int $page_style 使用的模板编号（1：第一个模板，2：第二个模板）
     * @return string
     */
    private static function _assembleRadioOrCheckboxHtmlElement($row, $type, $page_style) {
        $value_list = isset($row['value_list']) ? $row['value_list'] : array();
        $radio_html_element = '';
        $label_html = self::_assembleLabelHtmlElement($row, '', false);
        if ($value_list) {
            $value_arr = explode('|', $value_list);
            if ($page_style == 2) {
                $radio_format = '<span style="position:relative;">
                                    <input type="%s"  name="%s" value="%s" id="%s" %s>
                                    <i class="radios"></i>
                                    <label for="%s" style="padding-left:20px;" data-value="%s">%s</label>
                                 </span>';
                foreach ($value_arr as $index => $value_info) {
                    list ($value, $text) = explode(':', $value_info);
                    $name = self::_buidlHtmlElementName($row);
                    if ($value == $row['default_value']) {
                        $checked = ' checked="checked"';
                    } else {
                        $checked = '';
                    }
                    $radio_html_element .= sprintf($radio_format, $type, $name, 
                        $value, 'sc' . $row['id'] . '-' . $index, $checked, 
                        'sc' . $row['id'] . '-' . $index, $text, $text);
                }
                $radio_html_element = $label_html . '<p class="cont">' .
                     $radio_html_element . '</p>';
            } else {
                $label = '';
                $input = '';
                $radio_label_format = '<label for="%s" onclick="javascript:changeRadio(\'%s\');" data-value="%s" >%s</label>';
                $radio_input_format = '<input type="%s" name="%s" value="%s" id="%s" %s/>';
                $name = self::_buidlHtmlElementName($row);
                foreach ($value_arr as $index => $value_info) {
                    list ($value, $text) = explode(':', $value_info);
                    if ($value == $row['default_value'] || $index === 0) {
                        $checked = ' checked="checked"';
                    } else {
                        $checked = '';
                    }
                    $id = 'sc' . $row['id'] . '-' . $index;
                    $input .= sprintf($radio_input_format, $type, $name, $value, 
                        $id, $checked);
                    $label .= sprintf($radio_label_format, $id, $id, $text, 
                        $text);
                }
                $script_format = '<script type="text/javascript">function changeRadio(newId) {
                    $("input:radio[name=%s]").each(function(i,val){
                        $(val).attr("checked",false);
                    });
                    $("#" + newId).attr("checked",true);
                }</script>';
                $script = sprintf($script_format, $name);
                $radio_html_element = $label_html .
                     '<nav class="segmented-control">' . $input . $label .
                     '</nav>' . $script;
            }
        }
        
        return $radio_html_element;
    }

    /**
     * 组成 radio html标签
     *
     * @param array $row 会员招募所需的字段信息
     * @param int $page_style 使用的模板编号（1：第一个模板，2：第二个模板）
     * @return string
     */
    private static function _assembleRadioHtmlElement($row, $page_style) {
        return self::_assembleRadioOrCheckboxHtmlElement($row, 'radio',  $page_style);
    }

    /**
     * 组成 radio html标签
     *
     * @author Jeff Liu
     * @param array $row 会员招募所需的字段信息
     * @return string
     */
    private static function _assembleCheckboxHtmlElement($row, $page_style) {
        return self::_assembleRadioOrCheckboxHtmlElement($row, 'checkbox', 
            $page_style);
    }

    /**
     * 验证 需要修改
     *
     * @author Jeff Liu
     * @return mixed
     */
    private function verifyRecruit() {
        $overdue = $this->checkDate();
        if ($overdue === false) { // 活动已过期
            $this->showErrorByErrno(- 1005);
        }
        
        $verifed = MemberRecruitService::verifyRecruit();
        if ($verifed !== true) { // 验证出错
            $errno = isset($verifed['errno']) ? $verifed['errno'] : '';
            $errmsg = isset($verifed['errmsg']) ? $verifed['errmsg'] : '';
            $this->showErrorByErrno($errno, null, $errmsg);
            return false;
        }
        
        return true;
    }

    /**
     * 招募新会员(提交表单)
     *
     * @author Jeff Liu
     */
    public function add() {
        $result = array();
        // 验证失败的话 直接就exit了
        $this->verifyRecruit();
        
        $recruitData = MemberRecruitService::buildRecruitData($this->wxSess);
        $save_info = $recruitData['save_info'];
        $phone_no = $recruitData['phone_no'];
        $id = $recruitData['id'];
        $card_id = $recruitData['mc_id'];
        // 保存相关数据
        $save_result = MemberRecruitService::saveRecruitData($recruitData, $this->batch_type);
        $updateId = 0;
        if ($save_result < 0) { // 报错
            $this->showErrorByErrno($save_result);
        } else {
            $updateId = $save_result;
        }
        
        // 发码
        $sendStr = '';
        if ($save_info['is_send'] == '1') {
            $sendStr .= MemberRecruitService::sendWeChatCode($save_info, $phone_no, $updateId);
        }
        
        session("cc_node_id", $this->node_id);
        session('groupPhone', $phone_no);
        session("store_mem_id{$this->node_id}", array('user_id'=>$save_result));
        $cardData = D("MemberInstall")->getMemberCardsId($this->node_id, $card_id);
        $cjResult['memberInfo'] = "您已成为{$this->marketInfo['node_name']}的<span style='color:#ed3f41'>{$cardData['card_name']}</span>!";
        $cjResult['status'] = 1;
        $cjResult['is_cj'] = $save_info['is_cj'];
        $cjResult['member_id'] = $save_result;
        $cjResult['id'] = $id;
        $this->ajaxReturn($cjResult);
    }
    
    public function cjAct(){
        $id = I('id');
        $save_info = session('saveInfo');
        $memberId = I('memberId');
        $phone_no = M('tmember_info')->where(array('id'=>$memberId))->getField('phone_no');
        
        // 发码
        $sendStr = '';
        if ($save_info['is_send'] == '1') {
            $sendStr .= MemberRecruitService::sendWeChatCode($save_info, $phone_no, $memberId);
        }
        
        $award_level_msg = MemberRecruitService::getAwardLevelMsg($this->node_id, $this->batch_type, $this->batch_id);
        $wxUserInfo = $this->getwxUserInfo();
        $other = array();
        if ($wxUserInfo) {
            $other = array(
                'wx_open_id' => $wxUserInfo['openid'], 
                'wx_nick' => $wxUserInfo['nickname']);
        }
        $joinMode = isset($this->marketInfo['join_mode']) ? $this->marketInfo['join_mode'] : 0;
        $nodeId = isset($this->marketInfo['node_id']) ? $this->marketInfo['node_id'] : '';
        $cjResult = MemberRecruitService::drawLottery($id, $phone_no, $sendStr, $award_level_msg, $other, $joinMode, $nodeId);
        $cjResult['cjInfo'] = $sendStr.$cjResult['cjMsg'];
        $this->ajaxReturn($cjResult);
    }

    /**
     * 手机发送验证码
     */
    public function sendCheckCode() {
        $overdue = $this->checkDate();
        if ($overdue === false) { // 该活动不在有效期之内
            $this->showErrorByErrno(- 1016);
        }
        
        $phone_no = I('post.phone_no', null);
        if (! check_str($phone_no, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->showErrorByErrno(- 1008, null, $error);
        }
        
        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupCheckCode = array(
                'number' => 1111, 
                'add_time' => time(), 
                'phoneNo' => $phone_no);
            session('groupCheckCode', $groupCheckCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        
        $save_info = session('saveInfo');
        if (empty($save_info)) { // 参数错误
            $this->showErrorByErrno(- 1006);
        }
        
        // 发送频率验证
        $check_code = session('checkCode');
        if (! empty($check_code) &&
             (time() - $check_code['add_time']) < $this->expiresTime) {
            $this->showErrorByErrno(- 1017);
        }
        $num = mt_rand(1000, 9999);
        // 短信内容
        $node_name = MemberRecruitService::getNodeInfo($save_info['node_id']);
        $code_info = "【{$node_name}】 会员注册验证码：{$num}；如非本人操作请忽略！";
        // 通知支撑
        $transaction_id = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                    // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $transaction_id, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phone_no),  // 手机号
                'SendClass' => 'MMS', 
                'MessageText' => $code_info,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('MOBILE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->showErrorByErrno(- 1018);
        }
        $check_code = array(
            'number' => $num, 
            'add_time' => time());
        session('checkCode', $check_code);
        $this->success('验证码已发送');
    }

    /**
     * 将base64加密wbmp格式文件转换成png格式
     */
    public function showPng() {
        $str = I('b_str');
        $im = imagecreatefromstring(base64_decode($str));
        header("content-type:image/png");
        imagepng($im);
        exit();
    }
}