<?php

/**
 * Class AuthAccessService
 */
class AuthAccessService
{
    private $bindChannelBatchType = array(58,8); //所有人都可以发布定的batch_type

    private $previewBatchType = array(19,58);//所有可以预览的batch_type


    /**
     * AuthAccessService constructor.
     */
    public function __construct()
    {

    }

    public function needVerifyPreviewPower($batchType)
    {
        return !in_array($batchType, $this->previewBatchType);
    }

    public function needVerifyBindChannelPower($batchType)
    {
        return !in_array($batchType, $this->bindChannelBatchType);
    }

}
