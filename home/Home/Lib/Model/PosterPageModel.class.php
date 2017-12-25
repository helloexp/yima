<?php

/**
 * 新版海报相关 model
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn> Date: 2015/09/08
 */
class PosterPageModel extends BaseModel {

    protected $tableName = 'tposter_page';

    public function getPosterPage($where) {
        return $this->getData($this->tableName, $where);
    }

    public function deletePosterPage($where) {
        return $this->where($where)->delete();
    }
}