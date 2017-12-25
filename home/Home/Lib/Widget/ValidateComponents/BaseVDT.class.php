<?php 
class BaseVDT{
	public $nodeInfo  = array();//机构信息
	public $config    = array();//待校验的参数
	public $rules     = array();//校验规则
	public $errorInfo = array();//错误信息返回
	public $alias 	  = array();//错误字段别名设置

	public function __construct(){
		//控制器初始化
        if(method_exists($this,'_init'))
            $this->_init();
	}

	/**
	 * [setNodeInfo 初始化机构信息]
	 * @param [type] $nodeInfo [机构信息]
	 */
	public function setNodeInfo($nodeInfo)
	{
		$this->nodeInfo = $nodeInfo;
		return $this;
	}

	/**
	 * [setConfig 初始化待校验字段]
	 * @param [type] $config [待校验字段]
	 */
	public function setConfig($config)
	{
		$this->config = $config;
		return $this;
	}

	/**
	 * [setConfig 初始化参数别名]
	 * @param [type] $alias [别名]
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;
		return $this;
	}

	/**
	 * [setRules 初始化校验规则]
	 * @param [type] $rules [校验规则]
	 * null=>若不设置或设为false，则该字段不得为空，为true表示可以为空
	 * maxlen=>最大字节数
	 * minlen=>最小字节数
	 * belen=>必须字节数
	 * maxlen_cn=>最大字数
	 * minlen_cn=>最小字数
	 * belen_cn=>必须字数
	 * maxval=>最大值
	 * minval=>最小值
	 * minval=>最小值
	 * strtype=>字符串类型设置(string,number,float,int,alpha,datetime,md5,email,mobile,price)
	 * inarr=>在字符串范围内
	 * regxp=>自定义正则表达式
	 */
	public function setRules($rules)
	{
		$this->rules = $rules;
		return $this;
	}

	/**
	 * [run 校验入口]
	 * @return [type] [无返回]
	 */
	public function run()
	{
		if(!$this->config || !$this->rules )
		{
			$this->errorInfo[] = '没有传入参数或设定校验规则';
			return false;
		}
		foreach ($this->config as $varName => $varVal) {
			get_val($this->rules,$varName) && self::check_str($varName,$varVal);
		}
		log_write('字段校验结果:'.print_r($this->errorInfo,true));
	}
	/**
	 * [check_str 检查字段]
	 * @param  [type] $varName [参数名称]
	 * @param  [type] $varVal  [参数值]
	 * @return [type]          [true:表示成功校验,false:校验失败]
	 */
	private function check_str($varName, $varVal)
	{
		$len    = strlen($varVal);
		$len_cn = mb_strlen($varVal, 'utf8');
		$rule   = $this->rules[$varName];
		$alias  = $this->alias;

	    if(!isset($alias[$varName]))
	    {
	    	$alias[$varName] = $varName;
	    }
	    // 表示该字段可以不填，并且不校验
	    if ($rule['null'] && !$len) {
	    	return true;
	    }
	    //判断为空
	    if (!$len) {
	        $this->errorInfo[$varName] = $alias[$varName].'不能为空！';
	        return false;
	    }
	    //最大字节数
	    if (isset($rule['maxlen'])) {
	        if ($len > $rule['maxlen']) {
	        	$this->errorInfo[$varName] = $alias[$varName].'不能超过 ' . $rule['maxlen'] . ' 字节';
	            return false;
	        }
	    }
	    //最小字节数
	    if (isset($rule['minlen'])) {
	        if ($len < $rule['minlen']) {
	        	$this->errorInfo[$varName] = $alias[$varName].'不能少于 ' . $rule['minlen'] . ' 字节';
	            return false;
	        }
	    }
	    //必须字节数
	    if (isset($rule['belen'])) {
	        if ($len != $rule['belen']) {
	        	$this->errorInfo[$varName] = $alias[$varName].'必须等于 ' . $rule['belen'] . ' 字节';
	            return false;
	        }
	    }
	    //最大字数
	    if (isset($rule['maxlen_cn'])) {
	        if ($len_cn > $rule['maxlen_cn']) {
	        	$this->errorInfo[$varName] = $alias[$varName].'不能超过 ' . $rule['maxlen_cn'] . ' 字';
	            return false;
	        }
	    }
	    //最小字数
	    if (isset($rule['minlen_cn'])) {
	        if ($len_cn < $rule['minlen_cn']) {
	        	$this->errorInfo[$varName] = $alias[$varName].'不能少于 ' . $rule['minlen_cn'] . ' 字';
	            return false;
	        }
	    }
	    //必须字数
	    if (isset($rule['belen_cn'])) {
	        if ($len != $rule['belen_cn']) {
	        	$this->errorInfo[$varName] = $alias[$varName].'必须等于 ' . $rule['belen_cn'] . ' 字';
	            return false;
	        }
	    }
	    //最大值
	    if (isset($rule['maxval'])) {
	        if ($varVal > $rule['maxval']) {
	        	$this->errorInfo[$varName] = $alias[$varName].'必须小于等于 ' . $rule['maxval'];
	            return false;
	        }
	    }
	    //最小值
	    if (isset($rule['minval'])) {
	        if ($varVal < $rule['minval']) {
	        	$this->errorInfo[$varName] = $alias[$varName].'必须大于等于 ' . $rule['minval'];
	            return false;
	        }
	    }
	    //字符串类型设置
	    if (isset($rule['strtype'])) {
	        if ($rule['strtype'] == 'number') {
	            if (!is_numeric($varVal)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'必须为数字型';
	                return false;
	            }
	        } elseif ($rule['strtype'] == 'float') {
	            if (!is_numeric($varVal)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'必须为浮点型';
	                return false;
	            }
	            $varVal = $varVal / 1;
	            if (!is_int($varVal) && !is_float($varVal)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'必须为浮点型';
	                return false;
	            }
	        } elseif ($rule['strtype'] == 'int') {
	            if (!is_numeric($varVal)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'必须为整型';
	                return false;
	            }
	            $varVal = $varVal / 1;
	            if (!is_int($varVal)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'必须为整型';
	                return false;
	            }
	        } elseif ($rule['strtype'] == 'alpha') {
	            if (!preg_match('/^[a-z]*$/isU', $varVal)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'必须为字母';
	                return false;
	            }
	        } //时间类型
	        elseif ($rule['strtype'] == 'datetime') {
	            $data_format = $rule['format'] ? $rule['format'] : 'Ymd';
	            if (!self::check_date($varVal, $data_format)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'时间格式不对';
	                return false;
	            }
	        } //MD5类型
	        elseif ($rule['strtype'] == 'md5') {
	            if (!preg_match('/^[0-9a-f]{32}$/isU', $varVal)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'必须为md5';
	                return false;
	            }
	        } //只能是字符串
	        elseif ($rule['strtype'] == 'string') {
	            if (!is_string($varVal)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'必须为字符串';
	                return false;
	            }
	        } //email
	        elseif ($rule['strtype'] == 'email') {
	            if (!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $varVal)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'邮箱格式错误';
	                return false;
	            }
	        } //手机号
	        elseif ($rule['strtype'] == 'mobile') {
	            if (!preg_match("/^1[34578][0-9]{9}$/", $varVal)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'格式错误';
	                return false;
	            }
	        } //价格格式验证 0.00保留2位小数
	        elseif ($rule['strtype'] == 'price') {
	            if (!preg_match("/^(0|[1-9]\d+)\.\d{2}$/", $varVal)) {
	            	$this->errorInfo[$varName] = $alias[$varName].'价格格式错误';
	                return false;
	            }
	        }
	    }
	    //在字符串范围内
	    if (isset($rule['inarr']) && $rule['inarr']) {
	        if (!in_array($varVal, $rule['inarr'])) {
	        	$this->errorInfo[$varName] = $alias[$varName].'必须为' . implode(',', $rule['inarr']);
	            return false;
	        }
	    }
	    //正则表达式
	    if (isset($rule['regxp']) && $rule['regxp']) {
	        if (!preg_match($rule['regxp'], $varVal)) {
	        	$this->errorInfo[$varName] = $alias[$varName].'格式错误';
	            return false;
	        }
	    }
	    return true;
	}
	/**
	 * 验证日期格式
	 *
	 * @param string $time   要验证的日期
	 * @param string $format 要验证的格式
	 *
	 * @return boolean
	 */
	public function check_date($time, $format = 'Y-m-d')
	{
	    $reg_arr = array(
	            'Y-m-d H:i:s' => "/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/",
	            'Y-m-d'       => "/^(\d{4})-(\d{2})-(\d{2})$/",
	            'Ymd'         => "/^(\d{4})(\d{2})(\d{2})$/",
	    );

	    if (!isset($reg_arr[$format])) {
	        return false;
	    }

	    if (preg_match($reg_arr[$format], $time, $matches)) {
	        if (checkdate($matches[2], $matches[3], $matches[1])) {
	            return true;
	        }
	    }

	    return false;
	}
}
