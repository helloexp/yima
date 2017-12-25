<?php

/**
 * webpos 信息传输加密
 *
 * @author lyb
 */
class WebPos3Des {

    /**
     * 1.签到MAC计算（源串，主密钥） 2.签到MAC校验（源串，主密钥，mac） 3.普通交易MAC计算 （源串，主密钥，工作密钥）
     * 3des解开工作密钥，计算MAC 4.普通交易MAC校验 （源串，主密钥，工作密钥， mac）3des解开工作密钥，计算MAC
     * 主密钥和工作密钥从终端表中获取
     * 签到 $i_mac_str = ''.$pos_id.$user_id.$pos_seq; 验证 $i_mac_str =
     * ''.$pos_id.$user_id.$encode_type.$valid_info.$pos_seq; 冲正 $i_mac_str =
     * ''.$pos_id.$user_id.$encode_type.$valid_info.$org_pos_seq.$pos_seq; 撤销
     * $i_mac_str =
     * ''.$pos_id.$user_id.$encode_type.$valid_info.$org_pos_seq.$user_id; 对账
     * $i_mac_str =
     * ''.$pos_id.pos_seq.$user_id.$settle_batch.$encode_type.$valid_info;
     */
    private function mcrypt_3des_cbc($input, $key) {
        if (!function_exists('mcrypt_module_open')) {
            import('@.ORG.Crypt.Des') or die('[@.ORG.Crypt.Des]导入失败');

            return Des::encrypt($input, $key, 1);
        } else {
            $td        = mcrypt_module_open(MCRYPT_3DES, ' ', MCRYPT_MODE_CBC, ' ');
            $blocksize = mcrypt_enc_get_block_size($td);
            $keysize   = mcrypt_enc_get_key_size($td);
            $iv_size   = mcrypt_enc_get_iv_size($td);
            $iv        = mcrypt_create_iv($iv_size, MCRYPT_RAND);
            $input_len = strlen($input);
            $padsize   = $blocksize - ($input_len % $blocksize);
            // $input .= str_repeat(pack ( 'C*', $padsize), $padsize);
            mcrypt_generic_init($td, $key, "\x00\x00\x00\x00\x00\x00\x00\x00");
            // mcrypt_generic_init($td, $key, $iv);
            $crypt = mcrypt_generic($td, $input);
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);

            return $crypt;
        }
    }

    private function mdecrypt_3des_cbc($input, $key) {
        if (!function_exists('mcrypt_module_open')) {
            import('@.ORG.Crypt.Des') or die('[@.ORG.Crypt.Des]导入失败');

            return Des::decrypt($input, $key, 1);
        } else {
            $td        = mcrypt_module_open(MCRYPT_3DES, ' ', MCRYPT_MODE_CBC, ' ');
            $blocksize = mcrypt_enc_get_block_size($td);
            $input_len = strlen($input);
            $padsize   = $blocksize - ($input_len % $blocksize);
            // $input .= str_repeat(pack ( 'C*', $padsize), $padsize);
            mcrypt_generic_init($td, $key, "\x00\x00\x00\x00\x00\x00\x00\x00");
            $crypt = mdecrypt_generic($td, $input);
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);

            return $crypt;
        }
    }

    /**
     * 1.签到MAC计算 入参（源串，主密钥） $i_mac_str = ''.$pos_id.$user_id.$pos_seq;
     */
    function login_mac($i_mac_src, $master_key) {
        // include_once 'Common/Log/SysLog.class.php';
        // $log = new SysLog(__FILE__);
        // file_put_contents(APP_LOG_DIR."tmp.txt",$i_mac_src.",".
        // $master_key,FILE_APPEND);
        // $log->log($i_mac_src.",". $master_key);
        $root_key        = "790840177908401779084017";
        $real_master_key = $this->mdecrypt_3des_cbc(pack("H*", $master_key), $root_key);
        $real_master_key = $real_master_key . substr($real_master_key, 0, 8);
        $md5_mac_src     = pack("H*", md5($i_mac_src));

        return strtoupper(bin2hex($this->mcrypt_3des_cbc($md5_mac_src, $real_master_key)));
    }

    /**
     * 2.签到MAC校验 入参（源串，主密钥，mac） return 0 succ ;其他 fail; $i_mac_str = 返回报文的xml参数
     *
     * @param unknown_type $i_mac_src
     * @param unknown_type $master_key
     * @param unknown_type $mac
     */
    function login_mac_check($i_mac_src, $master_key, $mac) {
        $root_key        = "790840177908401779084017";
        $real_master_key = $this->mdecrypt_3des_cbc(pack("H*", $master_key), $root_key);
        $real_master_key = $real_master_key . substr($real_master_key, 0, 8);
        $md5_mac_src     = pack("H*", md5($i_mac_src));
        $local_mac       = strtoupper(bin2hex($this->mcrypt_3des_cbc($md5_mac_src, $real_master_key)));

        return strncmp($local_mac, $mac, 32);
    }

    /**
     * 3.普通交易MAC计算 入参（源串，主密钥，工作密钥） 验证 $i_mac_str =
     * ''.$pos_id.$user_id.$encode_type.$valid_info.$pos_seq; 冲正 $i_mac_str =
     * ''.$pos_id.$user_id.$encode_type.$valid_info.$org_pos_seq.$pos_seq; 撤销
     * $i_mac_str =
     * ''.$pos_id.$user_id.$encode_type.$valid_info.$org_pos_seq.$pos_seq; 对账
     * $i_mac_str =
     * ''.$pos_id.pos_seq.$user_id.$settle_batch.$encode_type.$valid_info;
     *
     * @param unknown_type $i_mac_src
     * @param unknown_type $master_key
     * @param unknown_type $work_key
     */
    function trans_mac($i_mac_src, $master_key, $work_key) {
        // include_once 'Common/Log/SysLog.class.php';
        // $log = new SysLog(__FILE__);
        // $log->log($i_mac_src.",". $master_key.",".$work_key);
        // file_put_contents(APP_LOG_DIR."tmp.txt",$i_mac_src.",".
        // $master_key.",".$work_key,FILE_APPEND);
        $root_key        = "790840177908401779084017";
        $real_master_key = $this->mdecrypt_3des_cbc(pack("H*", $master_key), $root_key);
        $real_master_key = $real_master_key . substr($real_master_key, 0, 8);
        $real_work_key   = $this->mdecrypt_3des_cbc(pack("H*", $work_key), $real_master_key);
        $real_work_key   = $real_work_key . substr($real_work_key, 0, 8);
        $md5_mac_src     = pack("H*", md5($i_mac_src));

        return strtoupper(bin2hex($this->mcrypt_3des_cbc($md5_mac_src, $real_work_key)));
    }

    /**
     * 4.普通交易MAC校验 入参（源串，主密钥，工作密钥， mac） return 0 succ ;其他 fail; $i_mac_str =
     * 返回报文的xml参数
     *
     * @param unknown_type $i_mac_src
     * @param unknown_type $master_key
     * @param unknown_type $work_key
     * @param unknown_type $mac
     */
    function trans_mac_check($i_mac_src, $master_key, $work_key, $mac) {
        $root_key        = "790840177908401779084017";
        $real_master_key = $this->mdecrypt_3des_cbc(pack("H*", $master_key), $root_key);
        $real_master_key = $real_master_key . substr($real_master_key, 0, 8);
        $real_work_key   = $this->mdecrypt_3des_cbc(pack("H*", $work_key), $real_master_key);
        $real_work_key   = $real_work_key . substr($real_work_key, 0, 8);
        $md5_mac_src     = pack("H*", md5($i_mac_src));
        $local_mac       = strtoupper(bin2hex($this->mcrypt_3des_cbc($md5_mac_src, $real_work_key)));

        return strncmp($local_mac, $mac, 32);
    }
}

?>