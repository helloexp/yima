<?php

/**
 *
 * @author Jeff.Liu<liuwy@imageco.com.cn>
 */
class WxUserModel extends BaseModel
{

    protected $tableName = 'twx_user';

    protected $_pk = 'node_id';
    protected $_sk = 'openid';
}