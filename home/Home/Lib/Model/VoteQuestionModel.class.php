<?php

/**
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class VoteQuestionModel
 */
class VoteQuestionModel extends BaseModel {
    protected $tableName = 'tvote_question';
    public function getQuestionList($condtion) {
        return $this->getData('tvote_question_info', $condtion);
    }

    public function getAskList($condtion, $field = '') {
        return $this->getData('tvote_question_stat', $condtion, 
            BaseModel::SELECT_TYPE_ALL, $field);
    }
}