<?php

class PreviewChannelService
{

    /**
     *
     * @var CheckStatus
     */
    public $checkStatusObj;

    /**
     * @var ChannelModel
     */
    public $ChannelModel;

    /**
     * @var BatchChannelModel
     */
    public $BatchChannelModel;

    /**
     * @var TsystemParamModel
     */
    public $TsystemParamModel;

    /**
     * PreviewChannelService constructor.
     */
    public function __construct()
    {
        $this->ChannelModel      = D('Channel');
        $this->BatchChannelModel = D('BatchChannel');
        $this->TsystemParamModel = D('TsystemParam');
    }

    /**
     * 当前是否为预览渠道
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param        $nodeId
     * @param string $id
     *
     * @return bool
     */
    public function isPreviewChannel($nodeId, $id = '')
    {
        if (empty($this->checkStatusObj)) {
            if (empty($id)) {
                $id = $this->getId();
            }

            $this->checkStatusObj = new CheckStatus();
            $this->checkStatusObj->checkId($id);
        }
        $previewChannelId = $this->ChannelModel->getPreviewChannelId($nodeId);
        $batchChannelInfo = $this->checkStatusObj->batchChannelInfo;
        $channelId        = isset($batchChannelInfo['channel_id']) ? $batchChannelInfo['channel_id'] : 0;
        $previewChannelId = (int)$previewChannelId;
        $channelId        = (int)$channelId;
        if ($previewChannelId === $channelId) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        // 标签
        $id = I('get.id', I('post.id'), 'trim');
        if (empty($id)) {
            $id = session('id');
        }
        return $id;
    }

    public function getPreviewChannelId($nodeId)
    {
        return $this->ChannelModel->getSpecialField(
                array(
                        'type'     => 1,
                        'sns_type' => 61,
                        'node_id'  => $nodeId,
                        'status'   => 1
                ),
                'id'
        );
    }

    /**
     * @param $nodeId
     * @param $batchId
     *
     * @return mixed
     */
    public function addPreviewChannelId($nodeId, $batchId)
    {
        $data = array(
                'type'       => 1,
                'name'       => '默认预览',
                'sns_type'   => 61,
                'status'     => 1,
                'node_id'    => $nodeId,
                'add_time'   => date('YmdHis'),
                'send_count' => 0,
                'batch_id'   => $batchId
        );
        return $this->ChannelModel->add($data);
    }

    /**
     * @param $batchId
     * @param $channelId
     *
     * @return mixed
     */
    public function getPreviewBatchChannelId($batchId, $channelId)
    {
        return $this->BatchChannelModel->getSpecialField(
                ['batch_id' => $batchId, 'channel_id' => $channelId],
                'id'
        );
    }

    /**
     * @param $batchId
     * @param $batchType
     * @param $nodeId
     * @param $channelId
     * @param $previewmTime
     *
     * @return mixed
     */
    public function addPreviewBatchChannel($batchId, $batchType, $nodeId, $channelId, $previewmTime)
    {
        $data = array(
                'batch_type' => $batchType,
                'batch_id'   => $batchId,
                'channel_id' => $channelId,
                'add_time'   => date('YmdHis'),
                'node_id'    => $nodeId,
                'status'     => 1,
                'end_time'   => date(
                        'YmdHis',
                        strtotime(date('YmdHis')) + 60 * $previewmTime
                )
        );

        // 绑定渠道
        return $this->BatchChannelModel->add($data);
    }

    public function updatePreviewBatchChannel($batchId, $batchChannelId, $previewmTime)
    {
        $where = ['batch_id' => $batchId, 'id' => $batchChannelId];
        $data  = ['end_time' => date('YmdHis', time() + 60 * $previewmTime)];
        $this->BatchChannelModel->saveData($data, $where);
    }

    public function addOrUpdatePreviewChannel($batchId, $batchType, $nodeId)
    {
        if (empty($batchId)) {
            return false;
        }
        $channelId = $this->getPreviewChannelId($nodeId);
        if (!$channelId) {
            $channelId = $this->addPreviewChannelId($nodeId, $batchId);
            if (empty($channelId)) {
                return false;
            }
        }

        // 预览时间
        $previewmTime = M('tsystem_param')->where(['param_name' => 'BATCH_PREVIEW_TIME'])->getField('param_value');

        $batchChannelId = $this->getPreviewBatchChannelId($batchId, $channelId);

        if (!$batchChannelId) {
            // 绑定渠道
            $batchChannelId = $this->addPreviewBatchChannel($batchId, $batchType, $nodeId, $channelId, $previewmTime);
            if (!$batchChannelId) {
                return false;
            }
        } else {
            $this->updatePreviewBatchChannel($batchId, $batchChannelId, $previewmTime);
        }

        return $batchChannelId;
    }

    /**
     * @param $batchId
     * @param $batchType
     * @param $nodeId
     *
     * @return bool|mixed
     */
    public function getPreviewBatchChannelIdBySpecialParam($batchId, $batchType, $nodeId)
    {
        return $this->addOrUpdatePreviewChannel($batchId, $batchType, $nodeId);
    }

    /**
     * @param $batchId
     * @param $batchType
     * @param $nodeId
     *
     * @return string
     */
    public function getOrUpdatePreviewUrl($batchId, $batchType, $nodeId)
    {
        $batchChannelId = $this->getPreviewBatchChannelIdBySpecialParam($batchId, $batchType, $nodeId);
        return $this->getPreviewUrl($batchChannelId);
    }

    /**
     * @param        $batchChannelId
     * @param string $goodssale
     * @param string $fromUserId
     * @param string $formType
     *
     * @return string
     */
    public function getPreviewUrl($batchChannelId, $goodssale = '', $fromUserId = '', $formType = '')
    {
        $param = [];
        if ($goodssale) {
            $param['id']        = $batchChannelId;
            $param['goodssale'] = '1';
        } else {
            $param = array(
                    'id' => $batchChannelId
            );
            if ($fromUserId && $formType) {
                $param['from_user_id'] = $fromUserId;
                $param['from_type']    = $formType;
            }
        }
        return U('Label/Label/index', $param, '', '', true);
    }

    /**
     * @param $batchChannelId
     *
     * @return string
     */
    public function getPreviewQRCodeUrl($batchChannelId)
    {
        return U('LabelAdmin/ShowCode/index', array('id' => $batchChannelId));
    }
}