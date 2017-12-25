<?php

/**
 * ���� �������ĵ���������
 *
 * @author wang pan
 */
class TymNewsModel extends Model {

    protected $tableName = 'tym_news';

    /**
     * ��ȡ���µ�10������ (����)
     *
     * @return mixed
     */
    public function newProblemTitleTen() {
        $fields = 'a.news_id,a.news_name,a.is_commend,b.class_name';
        $where = array(
            'a.status' => '1', 
            'a.parent_class_id' => 44);
        
        $result = $this->field($fields)
            ->join('a inner join tym_class_column b on a.class_id=b.id')
            ->where($where)
            ->order('add_time desc')
            ->limit(10)
            ->select();
        
        return $result;
    }

    /**
     * ��ȡָ�������µ��������� (����)
     *
     * @param $classId number ����ID
     * @param $isPage bool �Ƿ������ܼ�¼��
     * @param $firstRow string limit��ʼ����
     * @param $listRows string limit������
     * @return mixed
     */
    public function getArticleTitle($classId, $isPage = false, $firstRow = '', 
        $listRows = '') {
        $fields = 'news_id,news_name,is_commend';
        $where = array(
            'class_id' => $classId, 
            'status' => 1);
        
        // �����ܼ�¼��
        if ($isPage) {
            return $this->where($where)->count();
        }
        $result = $this->field($fields)
            ->where($where)
            ->limit($firstRow, $listRows)
            ->order('is_commend desc ,news_id desc')
            ->select();
        
        return $result;
    }

    /**
     * �������ư���
     *
     * @param $content string ��ѯ��
     * @param $isPage bool �Ƿ������ܼ�¼��
     * @param $firstRow string limit��ʼ����
     * @param $listRows string limit������
     * @return mixed
     */
    public function queryArticle($content, $isPage = false, $firstRow = '', 
        $listRows = '') {
        $fields = 'news_id,news_name,is_commend';
        $where = array(
            'status' => 1, 
            'parent_class_id' => 44, 
            '_complex' => array(
                'news_name' => array(
                    'like', 
                    '%' . $content . '%'), 
                'content' => array(
                    'like', 
                    '%' . $content . '%'), 
                '_logic' => 'or'));
        
        // �����ܼ�¼��
        if ($isPage) {
            return $this->where($where)->count();
        }
        
        $result = $this->field($fields)
            ->where($where)
            ->limit($firstRow, $listRows)
            ->order('is_commend desc ,news_id desc')
            ->select();
        
        return $result;
    }

    /**
     * ��ȡ��ϸ��������
     *
     * @param $newsId number ����ID
     * @return mixed
     */
    public function getDetailsArticle($newsId) {
        $fields = 'a.news_name,a.content,b.class_name';
        $where = array(
            'a.news_id' => $newsId, 
            'a.status' => 1, 
            'a.parent_class_id' => 44);
        
        $result = $this->field($fields)
            ->join(' a inner join tym_class_column b on a.class_id = b.id')
            ->where($where)
            ->find();
        
        return $result;
    }

    /**
     * ��¼����а�������Ч�����Ĵ���
     *
     * @param $newsId number ����ID
     * @param $num bool �а��� | ��Ч����
     */
    public function recordNum($newsId, $num) {
        if ($num === "true") {
            return $this->where(
                array(
                    'news_id' => $newsId))->setInc('useful_num'); // ���� �� 1
        } else {
            return $this->where(
                array(
                    'news_id' => $newsId))->setInc('useless_num'); // ���� �� 1
        }
    }

    /**
     * �����������
     */
    public function addArticle() {
    }

    /**
     * �޸���������
     */
    public function modArticle() {
    }

    /**
     * ɾ����������
     */
    public function delArticle() {
    }
}