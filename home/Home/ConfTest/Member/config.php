<?php
return array(
    'UPLOAD' => APP_PATH . 'Upload/', 
    'UPLOAD_FILE' => array(
        'SIZE' => 1024 * 1024, 
        'TYPE' => array(
            'csv')), 
    'MEMBER_LEVEL' => 5,  // 会员分多少级别
    'LEVEL_ARR' => array(
        '1' => '一类权益', 
        '2' => '二类权益', 
        '3' => '三类权益', 
        '4' => '四类权益', 
        '5' => '五类权益'), 
    'SEX_ARR' => array(
        '1' => '男', 
        '2' => '女'), 
    'FILE_FIELD' => array(
        '姓名', 
        '手机号', 
        '性别', 
        '生日', 
        '区域'), 
    'TYPE_CHANGE' => array(
        '1', 
        '0')); // tmember_batch的date_type对应tbatch_info和goods_info的验码时间类型

?>