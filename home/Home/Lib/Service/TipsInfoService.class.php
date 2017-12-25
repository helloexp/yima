<?php

/**
 * 获取configTipsInfo配置项信息
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class TipsInfoService
 */
class TipsInfoService {

    /**
     * 根据编号获得对一个tipsInfo
     *
     * @author Jeff Liu
     * @param $no
     * @param string $returnKey
     *
     * @return mixed
     */
    public static function getMessageInfoByErrno($no, $returnKey = '') {
        $tipsInfoList = C('tipsInfo');
        if (isset($tipsInfoList[$no])) {
            $tipsInfo = $tipsInfoList[$no];
        } else {
            $tipsInfo = isset($tipsInfoList['default']) ? $tipsInfoList['default'] : array();
        }
        if ($returnKey) {
            $tipsInfo = isset($tipsInfo[$returnKey]) ? $tipsInfo[$returnKey] : $tipsInfo;
        }
        return $tipsInfo;
    }

    /**
     *
     * @param $no
     * @return mixed
     */
    public static function getMessageInfoErrorSoftTxtByNo($no) {
        return self::getMessageInfoByErrno($no, 'errorSoftTxt');
    }

    /**
     *
     * @param $no
     * @return mixed
     */
    public static function getMessageInfoErrorTxtByNo($no) {
        return self::getMessageInfoByErrno($no, 'errorTxt');
    }

    /**
     *
     * @param $no
     * @return mixed
     */
    public static function getMessageInfoErrorImgByNo($no) {
        return self::getMessageInfoByErrno($no, 'errorImg');
    }
}

