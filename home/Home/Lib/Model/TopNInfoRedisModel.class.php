<?php

/**
 * TopNInfoRedisModel
 *
 * @author : Jeff Liu<liuwy@imageco.com.cn>
 */
class TopNInfoRedisModel extends BaseModel
{
    protected $tableName = '__NONE__';
    const SELECT_TYPE_ALL = 0; // 0：获得所有满足条件的记录
    const SELECT_TYPE_ONE = 1; // 1：获得一条满足条件的记录
    const SELECT_TYPE_FIELD = 2; // 2:获得一条满足条件的记录中的某些字段
    protected $_pk;

    protected $_sk;

    // 该值不能为null
    public function _initialize()
    {
        parent::_initialize();
        import('@.Vendor.ModelConst');
    }

    /**
     * todo 还需要实现
     * @return string
     */
    public function getTopN()
    {
        $redis = $this->getRedis();
        $redis->set(
                'visit:top:1:3',
                'http://test.wangcaio2o.com/index.php?&g=Label&m=Poster&a=index&id=16593&wechat_card_js=1'
        );
        return 'http://test.wangcaio2o.com/index.php?&g=Label&m=Poster&a=index&id=16593&wechat_card_js=1';
    }
}