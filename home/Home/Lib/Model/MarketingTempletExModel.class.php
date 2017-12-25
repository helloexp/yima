<?php

/**
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class MarketingTemplateExModel
 */
class MarketingTempletExModel extends Model {

    protected $tableName = 'tmarketing_templet_ext';

    public function get($where, $field, $order) {
        $list = M($this->tableName)->field(
            array(
                'page_content'))
            ->where(array(
            'm_id' => $mInfo['id']))
            ->order('page_number asc')
            ->select();
        
        return $list;
    }
}