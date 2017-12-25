<?php

class Xml {

    const ENCODE_TYPE_UTF8 = "utf-8";

    const ENCODE_TYPE_GBK = "gbk";

    private $xmlHeader = "";

    private $xmlRoot = "";

    private $xmlFoot = "";

    private $encodeType = "";

    private $isLineFeed = false;

    function __construct($encodeType = self::ENCODE_TYPE_UTF8, $isLineFeed = true) {
        $this->encodeType = $encodeType;
        $this->isLineFeed = $isLineFeed;
        $this->xmlHeader  = "<?xml version='1.0' encoding = '{$this->encodeType}' ?>";
    }

    /**
     * 得到xml
     *
     * @param unknown_type $arr
     *
     * @return string
     */
    public function getXMLFromArray($arr, $includeHeader = true) {
        $lineFeed = $this->isLineFeed ? "\n" : "";
        $str      = "";

        foreach ($arr as $key => $value) {
            if (!is_array($value)) {
                $str .= "<{$key}>$value</{$key}>{$lineFeed}";
            } else {
                $str .= "<{$key}>{$lineFeed}{$this->getXMLFromArray($value,false)}</{$key}>{$lineFeed}";
            }
        }
        if (!$includeHeader) {
            return $str;
        }
        $str = $this->xmlHeader . $lineFeed . $this->xmlRoot . $lineFeed . $str . $this->xmlFoot;
        if ($this->encodeType != self::ENCODE_TYPE_UTF8) {
            $str = mb_convert_encoding($str, $this->encodeType, self::ENCODE_TYPE_UTF8);
        }

        return $str;
    }

    public function setXmlRoot($rootKeyName) {
        $this->xmlRoot = $rootKeyName;
    }

    public function setXmlFoot($footKeyName) {
        $this->xmlFoot = $footKeyName;
    }

    public function setXmlHeader($header) {
        $this->header = $header;
    }

    function getArrayFromXml($xml) {
        $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            $arr   = array();
            for ($i = 0; $i < $count; $i++) {
                $key = $matches[1][$i];
                $val = $this->getArrayFromXml($matches[2][$i]); // 递归
                if (array_key_exists($key, $arr)) {
                    if (is_array($arr[$key])) {
                        if (!array_key_exists(0, $arr[$key])) {
                            $arr[$key] = array(
                                    $arr[$key],
                            );
                        }
                    } else {
                        $arr[$key] = array(
                                $arr[$key],
                        );
                    }
                    $arr[$key][] = $val;
                } else {
                    $arr[$key] = $val;
                }
            }

            return $arr;
        } else {
            if ($this->encodeType != self::ENCODE_TYPE_UTF8) {
                $xml = mb_convert_encoding($xml, self::ENCODE_TYPE_UTF8, $this->encodeType);
            }

            return $xml;
        }
    }

    // Xml 转 数组, 不包括根键
    function getArrayFromXmlNoRoot($xml) {
        $arr = $this->getArrayFromXml($xml);
        $key = array_keys($arr);

        return $arr[$key[0]];
    }
}

// =================增加优化xml转换的函数==================================================
/*
 * Xml转Array函数 linfeng 2013-03-13
 */
function Xml2Array($str) {
    // 截取字符串
    $Xml_Array  = explode("&mac", $str);
    $Xml_String = $Xml_Array[0];
    $Xml_String = iconv("GBK", "UTF-8", $Xml_String);
    $Xml_String = str_replace("xml=", "", $Xml_String);
    $Xml_String = str_replace("GBK", "UTF-8", $Xml_String);
    $Xml_Object = simplexml_load_string($Xml_String);

    return Object2Array($Xml_Object);
}

/*
 * Xml对象递归转换 linfeng 2013-03-13
 */
function Object2Array($obj) {
    $arr  = array();
    $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
    foreach ($_arr as $key => $val) {
        $val       = (is_array($val) || is_object($val)) ? Object2Array($val) : $val;
        $arr[$key] = $val;
    }

    return $arr;
}

// =================增加优化xml转换的函数==================================================
?>