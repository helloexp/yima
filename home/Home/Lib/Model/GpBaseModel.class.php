<?php

class GpBaseModel extends BaseModel
{
    protected $tableName = '__NONE__';
    public $limit = 0;
    public $merchant_id = false;
    public $new_role_id;
    public function _initialize()
    {
        parent::_initialize();
        $this->new_role_id = session('new_role_id');
        if ($this->new_role_id == 2 && session('node_id') === C('GpEye.node_id')) {
        } else {
            $this->limit = true;
            $this->merchant_id = (int) session('merchant_id');
            $this->user_id=(int) session('user_id');

        }


    }
}
