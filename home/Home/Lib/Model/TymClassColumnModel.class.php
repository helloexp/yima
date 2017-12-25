<?php

/**
 * 帮助中心
 *
 * @author wang pan
 */
class TymClassColumnModel extends Model {

    protected $tableName = 'tym_class_column';

    /**
     * 文章内容
     *
     * @return TymNewsModel
     */
    private function getTymNewsModel() {
        if (empty($this->TymNewsModel)) {
            $this->TymNewsModel = D('TymNews');
        }
        return $this->TymNewsModel;
    }

    /**
     * 获取栏目
     *
     * @param $where array  查询条件
     * @param $isXinShou bool 是否来自新手入门
     */
    public function wangCaiHelp($where, $isXinShou) {
        $fields = 'id,class_name,parent_class_id';
        
        $result = $this->field($fields)
            ->where($where)
            ->order('parent_class_id asc')
            ->select();
        
        if (! $isXinShou) {
            
            $tymNewsModel = $this->getTymNewsModel();
            $other = $tymNewsModel->getArticleTitle(54);
            if(is_array($other)){
                foreach ($other as $key => $value) {
                    array_push($result, $value);
                }
            }
        }
        
        return $result;
    }

    /**
     * 添加栏目
     */
    public function addColumn() {
    }

    /**
     * 修改栏目
     */
    public function modColumn() {
    }

    /**
     * 删除栏目
     */
    public function delColumn() {
    }
}