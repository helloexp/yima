<?php

class Xml {

    var $parser;

    var $document;

    var $error;

    var $debug_mode = false;

    var $encoding = 'utf-8';

    var $_encoding = 'gb2312';

    var $case_mode = 0;
    // 大小写敏感 0 不区分大小写 1区分大小写
    protected $root = '';
    // 根节点名称
    protected $option = array();
    // 设置选项
    /*
     * 临时存储变量 parent; 当前父节点 - stack; #a stack of the most recent parent at each
     * nesting level last_opened_tag; #keeps track of the last tag opened. data;
     */
    var $_data_tmp = array();

    /**
     * 开始构造函数
     */
    function __construct($url = null) {
        // 初始化选项参数
        $this->option['CASE_MODE'] = 0; // 是否区分大小写
        $this->option['ENCODING'] = 'gb2312'; // 默认编码
    }
    
    // 解析xml， 把xml转化为数组
    function parseFile($file) {
        if (! $file) {
            return $this->dieError('File name is empty!');
        }
        $data = file_get_contents($file) or $err_flag = true;
        if ($err_flag) {
            return $this->dieError('File is empty or File is not Exists');
        }
        return $this->parse($data);
    }

    function &parse($data) {
        $this->case_mode = $this->option['CASE_MODE'];
        if (! $data) {
            return $this->dieError('Xml Data is empty!');
        }
        $err_flag = false;
        // 初始化
        $this->parser = & xml_parser_create();
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, '_open', '_close');
        xml_set_character_data_handler($this->parser, '_data');
        $this->document = array();
        $this->_data_tmp['stack'] = array();
        $this->_data_tmp['parent'] = & $this->document;
        if (preg_match('/<?xml.*encoding=[\'"](.*?)[\'"].*?>/m', $data, $m)) {
            $encoding = strtoupper($m[1]);
            $this->encoding = $encoding;
            $this->_encoding = $encoding === 'GBK' ? 'GBK' : $this->_encoding;
        }
        @xml_parse($this->parser, $data, true) or $err_flag = true;
        if ($err_flag) {
            $this->dieError(
                'XML error: ' .
                     xml_error_string(xml_get_error_code($this->parser)) .
                     ' at line ' . xml_get_current_line_number($this->parser));
            return false;
        }
        // 清空临时变量
        $this->_data_tmp = null;
        xml_parser_free($this->parser);
        $this->root = key($this->document);
        return $this->document;
    }

    function _open(&$parser, $tag, $attributes) {
        $tag = $this->case_mode ? $tag : strtolower($tag);
        $this->_data_tmp['data'] = ''; // stores temporary cdata
        $this->_data_tmp['last_opened_tag'] = $tag;
        if (is_array($this->_data_tmp['parent']) and
             array_key_exists($tag, $this->_data_tmp['parent'])) { // if
                                                                  // you've
                                                                  // seen
                                                                  // this
                                                                  // tag
                                                                  // before
            if (is_array($this->_data_tmp['parent'][$tag]) and
             array_key_exists(0, $this->_data_tmp['parent'][$tag])) {
            // if the keys are numeric
            // this is the third or later instance of $tag we've come across
            $key = $this->count_numeric_items($this->_data_tmp['parent'][$tag]);
        } else {
            // this is the second instance of $tag that we've seen. shift around
            if (array_key_exists("$tag attr", $this->_data_tmp['parent'])) {
                $arr = array(
                    '0 attr' => &$this->_data_tmp['parent']["$tag attr"], 
                    &$this->_data_tmp['parent'][$tag]);
                unset($this->_data_tmp['parent']["$tag attr"]);
            } else {
                $arr = array(
                    &$this->_data_tmp['parent'][$tag]);
            }
            $this->_data_tmp['parent'][$tag] = &$arr;
            $key = 1;
        }
        $this->_data_tmp['parent'] = &$this->_data_tmp['parent'][$tag];
    } else {
        $key = $tag;
    }
    if ($attributes)
        $this->_data_tmp['parent']["$key attr"] = $this->iconv_arr($attributes, 
            'gb2312');
    $this->_data_tmp['parent'] = &$this->_data_tmp['parent'][$key];
    $this->_data_tmp['stack'][] = &$this->_data_tmp['parent'];
}

function _data(&$parser, $data) {
    if ($this->_data_tmp['last_opened_tag'] != NULL) // you don't need to store
                                                     // whitespace in between
                                                     // tags
        $this->_data_tmp['data'] .= strtoupper($this->option['ENCODING']) !=
             'UTF-8' ? iconv('utf-8', $this->_encoding, $data) : $data;
}

function _close(&$parser, $tag) {
    $tag = $this->case_mode ? $tag : strtolower($tag);
    if ($this->_data_tmp['last_opened_tag'] == $tag) {
        $this->_data_tmp['parent'] = $this->_data_tmp['data'];
        $this->_data_tmp['last_opened_tag'] = NULL;
    }
    array_pop($this->_data_tmp['stack']);
    if ($this->_data_tmp['stack'])
        $this->_data_tmp['parent'] = &$this->_data_tmp['stack'][count(
            $this->_data_tmp['stack']) - 1];
}

/**
 * 设置参数
 */
function setopt($opt = null, $val = null) {
    if (is_array($opt)) {
        foreach ($opt as $key => $valstr) {
            $this->option[$key] = $valstr;
        }
    } else {
        $this->option[$opt] = $val;
    }
}

/*
 * 设置返回错误 根据debug_mode 来决定是否直接输出错误,并中断
 */
function dieError($err) {
    if ($this->debug_mode)
        exit($err);
    $this->error = $err;
    $this->document = null;
    return false;
}

function count_numeric_items(&$array) {
    return is_array($array) ? count(
        array_filter(array_keys($array), 'is_numeric')) : 0;
}
// 编码转换函数
function iconv_str($str, $code = 'gb2312') {
    $str = iconv('utf-8', $code, $str);
    return $str;
}
// 数组编码转换
function iconv_arr($arr, $code = 'gb2312') {
    if (! $arr)
        return false;
    foreach ($arr as $key => $val) {
        if (is_array($val)) {
            $arr[$key] = $this->iconv_arr($val, $code);
        } else {
            $arr[$key] = $this->iconv_str($val, $code);
        }
    }
    return $arr;
}

/*
 * 设置路径 @ $path:路径名　以->号分隔 @ $flag:绝对相对路径标记 true 绝对　false　相对
 */
function setPath($path, $flag = true) {
    if ($flag || $this->_path == "")
        $this->_path = $path;
    else
        $this->_path .= '->' . $path;
}

/*
 * 判断节点是否存在 @ $namepath:路径名 以->号分隔 @ $flag:绝对相对路径标记 true 绝对　false　相对 return
 * false:不存在 true存在
 */
function isNode($namepath, $flag = false) {
    if ($this->error)
        return false;
    $namepath = $this->case_mode ? $namepath : strtolower($namepath);
    $_document = $this->document;
    // 如果是相对路径
    if (! $flag && $this->_path)
        $namepath = $this->_path . '->' . $namepath;
    $patharr = explode('->', $namepath);
    $endflag = 0;
    foreach ($patharr as $key) {
        $key = trim($key);
        if ($endflag || ! array_key_exists($key, $_document))
            return false;
        $_document = $_document[$key];
        if (is_string($_document))
            $endflag = 1;
    }
    return true;
}

/*
 * 获取节点属性 @ $namepath 路径名 以->号分隔 具体格式 a->b@C @ $flag 绝对相对路径标记,true绝对 false　相对
 * return null:不存在
 */
function getAttr($namepath, $flag = false) {
    if ($this->error)
        return false;
    $namepath = $this->case_mode ? $namepath : strtolower($namepath);
    if (! $flag && $this->_path)
        $namepath = $this->_path . '->' . $namepath;
    $patharr = explode('@', $namepath);
    $namepath = $patharr[0];
    $attrname = trim($patharr[1]);
    $patharr = explode('->', $namepath);
    $pathcount = count($patharr);
    $_document = $this->document;
    $endflag = 0;
    $i = 1;
    foreach ($patharr as $key) {
        $key = trim($key);
        if ($endflag || ! array_key_exists($key, $_document))
            return null;
        if ($i ++ == $pathcount) {
            if (array_key_exists($key . ' attr', $_document)) {
                return $attrname ? (array_key_exists($attrname, 
                    $_document[$key . ' attr']) ? $_document[$key . ' attr'][$attrname] : false) : $_document[$key .
                     ' attr'];
            }
            if (is_string($_document[$key]) ||
                 ! array_key_exists(0, $_document[$key])) {
                return false;
            }
        }
        $_document = $_document[$key];
        if (is_string($_document))
            $endflag = 1;
    }
    $arr = array();
    $i = 0;
    foreach ($_document as $key => $val) {
        if (is_int($key)) {
            $i ++;
            continue;
        }
        if ($attrname && ! array_key_exists($attrname, $val))
            continue;
        $arr[$i] = $attrname ? $val[$attrname] : $val;
    }
    return $arr;
}

/*
 * 获取报文节点值 @ $namepath:节点路径，以->分开 @ $flag:路径相对绝对标记，true 绝对路径 false　相对路径
 */
function getAll($namepath = null, $flag = false) {
    if ($this->error)
        return false;
    
    $root = '';
    if ($namepath == '') {
        $namepath = $this->root;
        $root = $namepath;
    }
    $namepath = $this->case_mode ? $namepath : strtolower($namepath);
    $_document = $this->document;
    // 如果是相对路径
    if (! $flag && $this->_path)
        $namepath = $this->_path . '->' . $namepath;
    $patharr = explode('->', $namepath);
    $endflag = 0;
    foreach ($patharr as $key) {
        $key = trim($key);
        if ($endflag || ! array_key_exists($key, $_document))
            return null;
        $_document = $_document[$key];
        if (is_string($_document))
            $endflag = 1;
    }
    if ($endflag || ! array_key_exists(0, $_document))
        return $root ? array(
            $root => $_document) : $_document;
        // 以下操作是去除属性attr的操作
    $arr = array();
    foreach ($_document as $key => $val) {
        $arr[$key] = $val;
    }
    
    return $root ? array(
        $root => $arr) : $arr;
}

function getValue($namepath, $flag = false) {
    if ($this->error)
        return false;
    $namepath = $this->case_mode ? $namepath : strtolower($namepath);
    $_document = $this->document;
    // 如果是相对路径
    if (! $flag && $this->_path)
        $namepath = $this->_path . '->' . $namepath;
    $patharr = explode('->', $namepath);
    $endflag = 0;
    foreach ($patharr as $key) {
        $key = trim($key);
        if ($endflag || ! array_key_exists($key, $_document))
            return null;
        $_document = $_document[$key];
        if (is_string($_document))
            $endflag = 1;
    }
    if ($endflag || ! array_key_exists(0, $_document))
        return $_document;
        // 以下操作是去除属性attr的操作
    $arr = array();
    foreach ($_document as $key => $val) {
        if (! is_int($key)) {
            continue;
        }
        $arr[$key] = $val;
    }
    return $arr;
}
// 关闭xml解析,重置所有变量
function close() {
    $this->error = null;
    $this->document = null;
    $this->_data_tmp = null;
}

function error($show = false) {
    if ($show === false)
        return $this->error;
    if ($show === true)
        echo $this->error;
    $this->error = $show;
    return false;
}
// 数组转xml
function &arr2xml(&$data, $encoding = NULL, $level = 0, $prior_key = NULL) {
    $encoding = $encoding ? $encoding : $this->encoding;
    if ($data == "") {
        return;
    }
    if ($level == 0) {
        ob_start();
        echo '<?xml version="1.0" encoding="', $encoding, '"?>';
    }
    while (list ($key, $value) = each($data))
        if (! strpos($key, ' attr')) // if it's not an attribute
                                     // we don't treat attributes by themselves,
                                     // so for an empty element
                                     // that has attributes you still need to
                                     // set the element to NULL
            
            if (is_array($value) and array_key_exists(0, $value)) {
                $this->arr2xml($value, $encoding, $level, $key);
            } else {
                $tag = $prior_key ? $prior_key : $key;
                // echo str_repeat("\t", $level);
                echo '<', $tag;
                if (array_key_exists("$key attr", $data)) { // if there's an
                                                            // attribute for
                                                            // this element
                    while (list ($attr_name, $attr_value) = @each(
                        $data["$key attr"])) {
                        echo ' ', $attr_name, '="', htmlspecialchars(
                            $attr_value), '"';
                    }
                    @reset($data["$key attr"]);
                }
                
                if (is_null($value))
                    echo ' />';
                elseif (! is_array($value))
                    echo '>', htmlspecialchars($value), "</$tag>";
                else
                    echo '>', $this->arr2xml($value, $encoding, $level + 1), "</$tag>";
            }
    reset($data);
    if ($level == 0) {
        $str = &ob_get_contents();
        ob_end_clean();
        return $str;
    }
}

function get($name) {
    if ($name == 'root') {
        return $this->root;
    }
}
}

?>
