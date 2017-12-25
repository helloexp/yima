<?php

/**
 * @author lwb
 * 临时用户对应表
 */
class TempUserSequenceModel extends BaseModel {

    protected $tableName = 'ttemp_user_sequence';

    public function getSeqInfo($where) {
        $info = $this->getData($this->tableName, $where, 
            BaseModel::SELECT_TYPE_ONE);
        return $info;
    }
    public function getUrl($tempNodeType){
    	$url = '';
    	switch ($tempNodeType) {
    		case 'newPosterVisiter': // 新电子海报
    			{
    				$where = array('sequence' => cookie($tempNodeType.'Node'));
			    	$info = $this->getSeqInfo($where);
	    			$url = U('MarketActive/NewPoster/add', array('id' => $info['m_id'], 'tplId' => 7));
    			}
    			break;
    		case 'newVisualCodeVisiter': // 炫码
    			$url = U('MarketActive/VisualCode/selectType');
    			break;
    		default: 
    			break;
    	}
    	return $url;
    }
}