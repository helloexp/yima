<?php

/**
 * 新版电子海报wap
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class NewPosterAction
 */
class NewPosterAction extends MyBaseAction
{

    public $channel_type = 4;

    public $channel_sns_type = 48;

    public $shop_type = 29;

    public $node_short_name = '';

    const BATCH_TYPE = '58';

    const NEW_POSTER_DRAFT_TYPE = 4;

    /**
     *
     * @var NodeInfoModel
     */
    public $NodeInfoModel;

    /**
     *
     * @var MarketInfoModel
     */
    public $MarketInfoModel;

    /**
     *
     * @var PosterInfoModel
     */
    public $PosterInfoModel;

    /**
     * @var NewPosterService
     */
    public $NewPosterService;

    /**
     * @var FormCommonService
     */
    public $FormCommonService;

    public function _initialize()
    {
        parent::_initialize();

        $this->NodeInfoModel     = D('NodeInfo');
        $this->MarketInfoModel   = D('MarketInfo');
        $this->PosterInfoModel   = D('PosterInfo');
        $this->NewPosterService  = D('NewPoster', 'Service');
        $this->FormCommonService = D('FormCommon', 'Service');

        $nodeInfo              = $this->NodeInfoModel->getNodeInfo(array('node_id' => $this->node_id));
        $this->node_short_name = $nodeInfo['node_short_name'];
    }

    public function logVisit()
    {
        // 更新访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->recordSeq();
        echo 1111;exit;
    }

    public function index()
    {
        $id = $this->id;
        if ($this->batch_type != self::BATCH_TYPE) {
            $this->error(
                    array(
                            'errorImg'     => '__PUBLIC__/Label/Image/waperro5.png',
                            'errorTxt'     => '错误访问！',
                            'errorSoftTxt' => '你的访问地址出错啦~'
                    )
            );
        }

        // 活动
        $row = $this->MarketInfoModel->getMarketingInfo(array('id' => $this->batch_id));
        if (!$row) {
            $this->error('未找到活动信息');
        }
        $content = $this->PosterInfoModel->getPosterInfo(array('batch_id' => $this->batch_id));

        $memo = isset($content['memo']) ? $content['memo'] : '';
        // 预览读取草稿的内容
        $isPreviewChannel = $this->isPreviewChannel($this->node_id);
        if ($isPreviewChannel) {
            //尝试获得草稿里面的内容 start
            $posterDraft = $this->NewPosterService->getDraftContent(
                    $this->batch_id,
                    $this->node_id,
                    self::NEW_POSTER_DRAFT_TYPE
            );
            if ($posterDraft) {
                $memo = $posterDraft;
            }
            //尝试获得草稿里面的内容 end
        }

        if (empty($memo)) {
            $memo = $this->NewPosterService->buildDefaultPage(0);
        }

        unset($content['memo']);
        $basicInfo = json_encode($content);

        //微信分享 start
        $wechatShareConfig = $this->getWechatShareConfig();
        $shareImg          = get_upload_url($content['share_img']);
        $coverImg          = get_upload_url($content['cover_img']);
        $desc              = get_val($content, 'share_descript');
        $name              = get_val($content, 'title');
        $shareArr          = array(
                'config' => $wechatShareConfig,
                'link'   => U('index', ['id' => $this->id], '', '', true),
                'title'  => $name,
                'desc'   => $desc,
                'imgUrl' => $shareImg ? $shareImg : $coverImg
        );
        //微信分享 end

        $this->assign('basicInfo', $basicInfo);
        $this->assign('pages', $memo);
        $this->assign('row', $row);
        $this->assign('shareData', $shareArr);
        $this->assign('id', $id);
        $this->assign('content', $content['json']);

        $showAd = 1;
        $nodeInfo = get_node_info($this->node_id);
        if ($nodeInfo['pay_module']) {//如果有m1权限默认屏蔽广告
            $mArr = explode(',', $v['pay_module']);
            if (in_array('m1', $mArr)) {
                $showAd = 0;
            }
        }
        //是否显示广告
        $this->assign('showAdvertisement', get_val($this->node_cfg, 'h5ShowAd', $showAd));
        $this->display();
    }

    /**
     * 分享次数
     */
    public function visit($cookieName)
    {
        $visitId = $this->marketInfo['click_count'] + 1;
        cookie($cookieName, $visitId, 3600 * 24 * 365);
        return $visitId;
    }

    public function shareArr($visitId)
    {
        $row = $this->MarketInfoModel->getMarketingInfo(
                array(
                        'id' => $this->batch_id
                )
        );
        if (!$row) {
            $this->error('未找到该码上买活动信息');
        }
        $content                   = $this->PosterInfoModel->getPosterInfo(
                array(
                        'batch_id' => $this->batch_id
                )
        );
        $wx_share_config           = D('WeiXin', 'Service')->getWxShareConfig();
        $shareImg                  = get_upload_url($content['share_img']);
        $coverImg                  = get_upload_url($content['cover_img']);
        $row['name']               = str_replace("#N#", $visitId, $row['name']);
        $content['share_descript'] = str_replace(
                "#N#",
                $visitId,
                $content['share_descript']
        );
        $shareArr                  = array(
                'config' => $wx_share_config,
                'link'   => U(
                        'index',
                        array(
                                'id' => $this->id
                        ),
                        '',
                        '',
                        true
                ),
                'title'  => $row['name'],
                'desc'   => $content['share_descript'],
                'imgUrl' => $shareImg ? $shareImg : $coverImg
        );
        return $shareArr;
    }

    /**
     * 提交表单
     */
    public function formSubmit()
    {
        $platformData = ['node_id' => $this->node_id,];
        $ret          = $this->FormCommonService->addFormELementValue($_POST, $platformData);
        $ret          = (int)$ret;
        if ($ret) { //统计次数
            $this->PosterInfoModel->where(['batch_id' => $this->batch_id])->setInc('form_collect_count');
        }
        echo json_encode(['status' => (int)$ret]);
        exit;
    }
}