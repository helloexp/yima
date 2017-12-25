<?php

/**
 * todo 权限问题 这个上线的时候需要注意 SELECT * FROM tauth_node_role WHERE powers LIKE '%204%'
 * 需要在 tauth_node_role 添加相关数据 新表 tposter_info 用于存储新版海报相关内容 todo list 1、保存 基本信息功能
 * (自动保存功能) 2、基本信息音乐相关部分 3、前段页面展示 新版电子海报
 *
 * @author Jeff Liu Class NewPosterAction
 */
class NewPosterAction extends MarketBaseAction
{

    // 活动类型
    public $BATCH_TYPE = '58';

    public $CHANNEL_TYPE = '6';
    public $CHANNEL_SNS_TYPE = '58';

    // 图片路径
    public $img_path;

    const COUNT_PRE_PAGE = 8;

    const NEW_POSTER_DRAFT_TYPE = 4;
    // 新版电子海报草稿类型
    const BASIC_INFO_FIELD = 'defined_one_name';

    /**
     *
     * @var MarketInfoModel
     */
    public $MarketInfoModel;

    /**
     * @var PosterPageModel
     */
    public $PosterPageModel;

    /**
     *
     * @var ChannelModel
     */
    public $ChannelModel;

    /**
     *
     * @var DraftModel
     */
    public $DraftModel;

    /**
     *
     * @var BatchChannelModel
     */
    public $BatchChannelModel;

    /**
     *
     * @var NodeInfoModel
     */
    public $NodeInfoModel;

    /**
     *
     * @var PosterInfoModel
     */
    public $PosterInfoModel;

    /**
     * @var FormCommonService
     */
    private $FormCommonService;

    /**
     * @var PreviewChannelService
     */
    public $PreviewChannelService;

    private $uniqueFormPerPage = true;

    /**
     * @var NewPosterService
     */
    protected $NewPosterService;

    public $_authAccessMap = '*';
    protected $needCheckUserPower = false;

    public function _initialize()
    {
        //         $userService = D('UserSess', 'Service');

        if (ACTION_NAME == 'add') {

            $mId  = I('id');
            $info = D('MarketInfo')->getSingleField(['id' => $mId], 'node_id');
            if ($info['node_id'] == C('NEW_POSTER_VISITER.node_id')) {
                $userSessInfo = ['node_id' => $info['node_id'], 'token' => '0000000'];
                session('userSessInfo', $userSessInfo);
            }
        }
        $userSessInfo  = session('userSessInfo');
        $this->node_id = $userSessInfo['node_id'];
        //         $userInfo = $userService->getUserInfo();
        //         if ($userInfo['node_id'] == C('NEW_POSTER_VISITER.node_id')) {
        //             $this->_visiter_flag = true;
        //         }
        parent::_initialize();

        $pathList       = C('BATCH_IMG_PATH_NAME');
        $imgPath        = $pathList[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $imgPath;

        // 临时路径
        $tmpPath = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $imgPath);
        $this->assign('tmp_path', $tmpPath);

        $this->assign('batch_type', $this->BATCH_TYPE);

        $this->MarketInfoModel       = D('MarketInfo');
        $this->ChannelModel          = D('Channel');
        $this->DraftModel            = D('Draft');
        $this->BatchChannelModel     = D('BatchChannel');
        $this->NodeInfoModel         = D('NodeInfo');
        $this->PosterInfoModel       = D('PosterInfo');
        $this->PosterPageModel       = D('PosterPage');
        $this->FormCommonService     = D('FormCommon', 'Service');
        $this->NewPosterService      = D('NewPoster', 'Service');
        $this->PreviewChannelService = D('PreviewChannel', 'Service');
    }

    /**
     * @param $mapCount
     * @param $data
     *
     * @return Page
     */
    public function getPageData($mapCount, $data, $withBtnScript = false)
    {
        $pager = $this->NewPosterService->getPageData($mapCount, $data, self::COUNT_PRE_PAGE);
        $show  = $pager->show($withBtnScript); // 分页显示输出
        $this->assign('page', $show); // 赋值分页输出
        return $pager;
    }

    public function _before_index()
    {
        if ($this->node_id == D('TempLogin', 'Service')->getTempNodeId()) {
            redirect('/index.php');
        }
    }

    public function _before_add()
    {
        if ($this->node_id == D('TempLogin', 'Service')->getTempNodeId()) {
            //王严上的时候请用这个
            $newPosterTempNodeId = cookie('newPosterVisiterNode');
            //$newPosterTempNodeId = cookie('newPosterTempNodeId');
            $TempUserSequenceModel = D('TempUserSequence');
            $re                    = $TempUserSequenceModel->where(['sequence' => $newPosterTempNodeId])->find();
            if (!$re) {
                $this->error('没有找到对应的海报活动');
            }
            $mId = I('id');
            if ($re['m_id'] != $mId) {
                if (IS_AJAX) {
                    $this->showErrorByErrno(-1047, true);
                } else {
                    $this->showErrorByErrno(-1047, false);
                }
            }
        }
    }

    /**
     * 新版海报列表页
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function index()
    {
        $data       = $_REQUEST;
        $tableAlias = 't1';
        $map        = $this->NewPosterService->buildFullPosterListMap(
                $this->BATCH_TYPE,
                $this->nodeIn(),
                $data,
                $tableAlias
        );
        $mapcount   = $this->NewPosterService->getPosterCount($map, $tableAlias);
        $Page       = $this->getPageData($mapcount, $data);

        $limit = $Page->firstRow . ',' . $Page->listRows;

        $channelInfo = $this->ChannelModel->getChannelInfo(
                array(
                        'node_id'  => $this->node_id,
                        'type'     => $this->CHANNEL_TYPE,
                        'sns_type' => $this->CHANNEL_SNS_TYPE,
                )
        );
        if (!$channelInfo) { // 不存在则添加渠道
            //不存在则新增
            $channelList = array(
                    'name'        => '新版电子海报默认渠道',
                    'type'        => $this->CHANNEL_TYPE,
                    'sns_type'    => $this->CHANNEL_SNS_TYPE,
                    'status'      => '1',
                    'start_time'  => date('YmdHis'),
                    'end_time'    => date('YmdHis', strtotime("+1 year")),
                    'add_time'    => date('YmdHis'),
                    'click_count' => 0,
                    'cj_count'    => 0,
                    'send_count'  => 0,
                    'node_id'     => $this->node_id,
            );

            $channelId = $this->ChannelModel->add($channelList);
            if (!$channelId) {
                $this->error('添加新版电子海报默认渠道号失败');
            }
        } else {
            $channelId = get_val($channelInfo, 'id');
        }

        $list = $this->NewPosterService->getFullPosterList($map, $limit, $tableAlias, $channelId);

        $basicInfo = $this->buildDefaultBasicInfo();
        $this->assign('basicInfo', $basicInfo);

        $this->assign('defaultPage', $this->NewPosterService->getDefaultPageConf());

        $this->assign('posterList', $list);
        $this->display(); // 输出模板
    }

    /**
     * 自动保存草稿
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function autoSaveDraft($ret = false)
    {
        $id       = I('batch_id');
        $data     = I('post.', '', 'mysql_real_escape_string');
        $draft    = $this->buildMemo($data);
        $where    = array('node_id' => $this->node_id, 'type' => self::NEW_POSTER_DRAFT_TYPE, 'org_id' => $id);
        $hasDraft = $this->DraftModel->getDraftInfo($where);
        $draft    = json_encode($draft);
        if ($hasDraft) {
            $draftOk = $this->DraftModel->where($where)->save(
                    array('content' => $draft, 'add_time' => date('Y-m-d H:i:s'),)
            );
        } else {
            $draftOk = $this->DraftModel->add(
                    array(
                            'node_id'  => $this->node_id,
                            'content'  => $draft,
                            'add_time' => date('Y-m-d H:i:s'),
                            'type'     => self::NEW_POSTER_DRAFT_TYPE,
                            'org_id'   => $id,
                    )
            );
        }
        if ($ret) {
            return $draftOk;
        }
        if ($draftOk) {
            $this->ajaxReturn(array('status' => '1',));
        } else {
            $this->ajaxReturn(array('status' => '-1',));
        }
    }


    /**
     * 删除草稿
     * @author Jeff Liu<liuwy@imageco.com.cn
     * @return mixed
     */
    public function deleteDraft()
    {
        $where['type']    = self::NEW_POSTER_DRAFT_TYPE;
        $where['node_id'] = $this->node_id;
        return $this->DraftModel->deleteData($where);
    }


    public function buildStaticFile()
    {
        return false;
        $id         = I('id');
        $staticPath = makeStaticPath('Label/NewPoster/index', array('id' => '123'), true);

        // 活动
        $row = $this->MarketInfoModel->getMarketingInfo(array('id' => $id));
        if (!$row) {
            $this->error('未找到活动信息');
        }
        $content = $this->PosterInfoModel->getPosterInfo(array('batch_id' => $id));

        $memo = isset($content['memo']) ? $content['memo'] : '';

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
        $content = $this->fetch(TMPL_PATH . 'Label/NewPoster_index.html');
        file_put_contents($staticPath, $content);
    }

    /**
     * 新增/修改海报
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function add()
    {
        set_time_limit(0); //因为添加的时候有可能海报内容比较多，从而导致超时和内存不足的问题
        ini_set('memory_limit', '512M');
        $id          = I('id', null, 'mysql_real_escape_string');
        $ignoreDraft = I('ignore_draft', 0);
        if (empty($id)) { // 如果有传marketing表的id过来表示是编辑
            $this->error('id is empty:' . var_export($_REQUEST, 1));
        }
        $this->_checkPosterPermission($id);

        // 生成m_id和t_id和page_number，{"参数对应"=>"参数内容"}
        if (IS_POST) {
            $postMemo = $this->checkPostData();
            //处理form start
            $form = $this->filterPosterFormElement($postMemo);
            if (isset($form['error'])) {
                $this->ajaxReturn(array('status' => '0', 'info' => '新版海报修改失败--同一个页面不允许多个表单存在！', 'm_id' => $id,));
            }
            if ($form) {
                $options = [
                        'id'       => $id,
                        'node_id'  => $this->node_id,
                        'batch_id' => $id,
                        'ref_id'   => $id,
                        'ref_type' => $this->BATCH_TYPE
                ];
                $this->FormCommonService->processFormAndElement($form, $options);
                $this->rebuildMemo($postMemo, $form);
            }
            $this->PosterInfoModel->where(array('batch_id' => $id,))->save(
                    array('memo' => json_encode($postMemo),)
            );
            //处理form end
            // 删除草稿
            $this->_removePosterDraft($id);

            $this->buildStaticFile(); //创建静态页面

            $this->ajaxReturn(array('status' => '1', 'info' => '新版海报修改成功！', 'm_id' => $id,));
        }

        //尝试获得草稿里面的内容 start
        $posterDraft = $this->NewPosterService->getDraftContent($id, $this->node_id, self::NEW_POSTER_DRAFT_TYPE);
        //尝试获得草稿里面的内容 end

        $map        = array('batch_id' => $id,);
        $posterInfo = $this->PosterInfoModel->getList($map);

        list (, $posterInfo) = each($posterInfo);
        if (IS_GET && $posterDraft && $ignoreDraft == 0) {
            $memo = $posterDraft;
        } else {
            $memo = isset($posterInfo['memo']) ? $posterInfo['memo'] : '';
        }
        $tplId = I('tplId', 0);

        if (empty($memo)) {
            $memo = $this->NewPosterService->buildDefaultPage($tplId);
        }

        unset($posterInfo['memo']);
        $basicInfo  = $this->getBasicInfo($posterInfo);
        $posterPage = $this->PosterPageModel->getPosterPage(array('node_id' => $this->node_id));
        $posterPage = $this->formatPosterPage($posterPage);
        $this->assign('poster_id', $posterInfo['id']);
        $this->assign('posterPage', $posterPage);
        $this->assign('pages', $memo);
        $this->assign('basicInfo', $basicInfo);
        $this->assign('batch_id', $id);
        $this->assign('batch_type', $this->BATCH_TYPE);
        $this->assign('id', $id);

        $TempLoginService = D('TempLogin', 'Service');
        $isTempUser       = $TempLoginService->isTempUser($this->node_id);
        $this->assign('isTempUser', $isTempUser);
        $this->assign('node_id', $this->node_id);
        $this->display();
    }

    /**
     *
     * 删除电子海报
     */
    public function deletePoster()
    {
        $id = I('id', null, 'mysql_real_escape_string');
        if ($id) {
            $ret = $this->NewPosterService->deletePoster($id, $this->node_id);
            if ($ret) {
                $this->ajaxReturn(array('status' => '1', 'info' => '删除海报成功！', 'm_id' => $id,));
            }
        }
        $this->ajaxReturn(array('status' => '0', 'info' => '删除海报失败！', 'm_id' => $id,));
    }

    /**
     * @param $posterMemo
     * @param $formDataList
     */
    private function rebuildMemo(&$posterMemo, $formDataList)
    {
        foreach ($formDataList as $formId => $formElementListData) {
            foreach ($formElementListData as $index => $formElement) {

                if (issetAndNotEmpty($formElement, 'formbtn')) {
                    $key = 'formbtn';
                } else {
                    $key = 'input';
                }
                $pageIndex = get_val($formElement, 'posterPageIndex');
                $modIndex  = get_val($formElement, 'modIndex');
                $elementId = get_val($formElement, 'id');
                $formId    = get_val($formElement, 'newForm', get_val($formElement, 'form'));
                unset($formElement['modIndex'], $formElement['posterPageIndex'], $formElement['newForm']);
                $posterMemo[$pageIndex]['mod'][$modIndex]['id']         = $elementId;
                $posterMemo[$pageIndex]['mod'][$modIndex][$key]['form'] = $formId;
            }
        }
    }

    /**
     * @param $posterMemo
     *
     * @return array
     */
    private function mappingModPageAndFormId($posterMemo)
    {
        $modPageMappingFormId = [];
        if (is_array($posterMemo)) {
            foreach ($posterMemo as $pageIndex => $posterPage) {
                foreach ($posterPage['mod'] as $modIndex => $item) {
                    if (issetAndNotEmpty($item, 'name') && $item['name'] === 'input') {  //form元素
                        $formId = get_val($input, 'form');
                        if ($formId) {
                            if ($this->uniqueFormPerPage) {
                                if (!isset($modPageMappingFormId[$pageIndex])) {
                                    $modPageMappingFormId[$pageIndex] = $formId;
                                } else if ($modPageMappingFormId[$pageIndex] != $formId) { //报错
                                    return ['error' => 'multiple', 'pageIndex' => $pageIndex];
                                }
                            } else {
                                $modPageMappingFormId[$pageIndex][] = $formId;
                            }
                        }
                    }
                }
            }
        }

        return $modPageMappingFormId;
    }

    /**
     * 格式化form (form相关数据和其他的元素混在一起，type为input的为form元素)
     *
     * @param $posterMemo
     *
     * @return array
     */
    private function filterPosterFormElement($posterMemo)
    {
        $form = [];
        if (is_array($posterMemo)) {
            $modPageMappingFormId = $this->mappingModPageAndFormId($posterMemo);
            if (!isset($modPageMappingFormId['error'])) {
                foreach ($posterMemo as $pageIndex => $posterPage) {
                    $pageName = get_val($posterPage, 'name');
                    foreach ($posterPage['mod'] as $modIndex => $item) {
                        if (issetAndNotEmpty($item, 'name') && $item['name'] === 'input') {  //form元素
                            $inputId          = get_val($item, 'id');
                            $input            = get_val($item, 'input');
                            $formId           = get_val($input, 'form', $modPageMappingFormId[$pageIndex]);
                            $inputName        = get_val($input, 'text');
                            $inputType        = get_val($input, 'type', 'text');
                            $inputIsRequired  = get_val($input, 'must', 'false');
                            $inputOrder       = get_val($input, 'order');
                            $inputPlaceholder = get_scalar_val($inputName, '');
                            $form[$formId][]  = [
                                    'id'              => $inputId,
                                    'text'            => $inputName,
                                    'type'            => $inputType,
                                    'must'            => $inputIsRequired,
                                    'order'           => $inputOrder,
                                    'placeholder'     => $inputPlaceholder,
                                    'form'            => $formId,
                                    'modIndex'        => $modIndex,
                                    'posterPageIndex' => $pageIndex,
                                    'posterPageName'  => $pageName,
                                    'formbtn'         => false,
                            ];
                        } else if (issetAndNotEmpty($item, 'name') && $item['name'] === 'formbtn') {
                            $formbtnId          = get_val($item, 'id');
                            $formbtn            = get_val($item, 'formbtn');
                            $formId             = get_val($formbtn, 'form', $modPageMappingFormId[$pageIndex]);
                            $formbtnName        = get_val($formbtn, 'text');
                            $formbtnType        = get_val($formbtn, 'type', 'formbtn');
                            $formbtnIsRequired  = get_val($formbtn, 'must', 'false');
                            $formbtnOrder       = get_val($formbtn, 'order');
                            $formbtnPlaceholder = get_scalar_val($inputName, '');
                            $form[$formId][]    = [
                                    'id'              => $formbtnId,
                                    'text'            => $formbtnName,
                                    'type'            => $formbtnType,
                                    'must'            => $formbtnIsRequired,
                                    'order'           => $formbtnOrder,
                                    'placeholder'     => $formbtnPlaceholder,
                                    'formbtn'         => true,
                                    'form'            => $formId,
                                    'modIndex'        => $modIndex,
                                    'posterPageIndex' => $pageIndex,
                                    'posterPageName'  => $pageName,
                            ];
                        }
                    }
                }
            } else {
                $form = $modPageMappingFormId;
            }
        }

        return $form;
    }

    /**
     * @param $posterPage
     *
     * @return string
     */
    private function formatPosterPage($posterPage)
    {
        $posterPageJSONArray = array();
        if (is_array($posterPage)) {
            foreach ($posterPage as $index => $item) {
                $content               = get_val($item, 'memo');
                $content               = json_decode($content, true);
                $content['id']         = get_val($item, 'id');
                $posterPageJSONArray[] = json_encode($content);
            }
        }
        $posterPageJSON = implode(',', $posterPageJSONArray);

        return $posterPageJSON;
    }

    /**
     * 新版电子海报默认基础信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param bool $jsonEncode
     *
     * @return string
     */
    public function buildDefaultBasicInfo($jsonEncode = true)
    {
        if ($jsonEncode) {
            return '{"title":"电子海报","share_descript":"用户通过微信分享时显示","loop":0,"cover_img":"__PUBLIC__/Image/poster/jichu.jpg","music":"预约地址"}';
        } else {
            return [
                    'title'          => '电子海报',
                    'share_descript' => '用户通过微信分享时显示',
                    'loop'           => true,
                    'cover_img'      => "__PUBLIC__/Image/poster/jichu.jpg",
                    'music'          => '预约地址',
            ];
        }
    }

    /**
     *
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param      $posterInfo
     * @param bool $jsonEncode
     *
     * @return array|string
     */
    public function getBasicInfo($posterInfo, $jsonEncode = true)
    {
        if (is_array($posterInfo)) {
            $basecInfo = $this->buildBasicInfo($posterInfo, $jsonEncode);
        } else {
            $basecInfo = $this->buildDefaultBasicInfo($jsonEncode);
        }
        return $basecInfo;
    }


    /**
     * 保存成功画面
     */
    public function saveSuccess()
    {
        $batch_id  = I('batch_id');
        $map       = array('id' => $batch_id, 'node_id' => $this->node_id,);
        $batchInfo = $this->MarketInfoModel->where($map)->find();
        if (!$batchInfo) {
            $this->error('无效链接！');
        }
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $this->BATCH_TYPE);
        $edit_url = 'MarketActive/NewPoster/add';
        $this->assign('edit_url', $edit_url);
        $labelId    = $this->getLabelIdAndTryAddLabelInfo($batch_id, $this->BATCH_TYPE); // 获取默认渠道id
        $previewUrl = $this->getPreviewUrl($batch_id);
        $this->assign('returnListUrl', U('MarketActive/NewPoster/index'));
        $this->assign('labelId', $labelId);
        $this->assign('previewUrl', $previewUrl);
        $this->assign('isNewPoster', 1);
        $this->display();
    }

    /**
     *
     */
    public function createPosterLogo()
    {
        $labelId = I('labelId');
        import('@.Vendor.phpqrcode.phpqrcode') or die('include file fail.');
        $codeText = 'http://' . $_SERVER['HTTP_HOST'] . U('Label/NewPoster/index', array('id' => $labelId,));
        QRcode::png($codeText);
    }

    /**
     *
     */
    public function ajaxGetLabelId()
    {
        $batchType = I('batchType');
        $batchId   = I('batchId');
        $labelId   = $this->getLabelIdAndTryAddLabelInfo($batchId, $batchType);
        $this->success($labelId, '', true);
    }

    /**
     * 新增基本信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function addBasicInfo()
    {
        $data      = I('post.', '', 'mysql_real_escape_string');
        $basicInfo = $this->buildBasicInfo($data, false);
        $title     = get_val($data, 'title', '');

        if (empty($title)) {
            $this->ajaxReturn(array('status' => '0', 'info' => '新版海报基本信息添加失败！',));
        }
        $batchId = I('post.batch_id');
        if (empty($batchId)) {
            $data    = array(
                    'memo'       => '',
                    'name'       => $title,
                    'batch_type' => $this->BATCH_TYPE,
                    'status'     => 1,
                    'pay_status' => 0,
                    'node_id'    => $this->node_id,
                    'add_time'   => date('YmdHis'),
                    'start_time' => date('YmdHis'),
                    'end_time'   => date('YmdHis', strtotime('20301231235959'))
            );
            $batchId = $this->MarketInfoModel->add($data);
        }

        if ($batchId) {
            $basicInfo['batch_id'] = $batchId;
            $this->PosterInfoModel->add($basicInfo);
            $this->NewPosterService->initDefaultPageConf();
            $this->ajaxReturn(
                    array(
                            'status'          => '1',
                            'info'            => '新版海报基本信息添加成功！',
                            'url'             => U('MarketActive/NewPoster/add', array('id' => $batchId,)),
                            'defaultPageConf' => $this->NewPosterService->getDefaultPageConf(),
                    )
            );
        } else {
            $this->ajaxReturn(array('status' => '0', 'info' => '新版海报基本信息添加失败！',));
        }
    }

    /**
     * 新增模板内容
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function addPosterPage()
    {
        $data       = I('post.', '', 'mysql_real_escape_string');
        $posterPage = $this->buildPosterPage($data);
        $id         = 0;
        if ($posterPage) {
            $id = $this->PosterPageModel->add($posterPage);
        }

        if ($id) {
            $this->ajaxReturn(array('status' => '1', 'info' => '新版海报模板页面添加成功！', 'data' => $id));
        } else {
            $this->ajaxReturn(array('status' => '0', 'info' => '新版海报模板页面添加失败！',));
        }
    }

    /**
     * 新增模板内容
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function deletePosterPage()
    {
        $id  = I('post.page_id');
        $res = false;
        if ($id) {
            $where = array('id' => $id, 'node_id' => $this->node_id,);
            $res   = $this->PosterPageModel->deletePosterPage($where);
        }

        if ($res) {
            $this->ajaxReturn(array('status' => '1', 'info' => '新版海报模板页面删除成功！',));
        } else {
            $this->ajaxReturn(array('status' => '0', 'info' => '新版海报模板页面删除失败！',));
        }
    }


    /**
     * 保存基本信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function saveBasicInfo()
    {
        $posterId = I('post.poster_id');
        if (empty($posterId)) { // 新增
            $this->addBasicInfo();
        } else {
            $this->editBasicInfo();
        }
    }

    /**
     * 更新音乐
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function updateBasicInfoMusic()
    {
        $posterId = I('post.poster_id');
        if (empty($posterId)) {
            if (empty($posterId)) {
                $this->ajaxReturn(array('status' => 0, 'info' => 'id is empty : ' . var_export($_REQUEST, 1),));
            }
        } else {
            $posterMap = array('id' => $posterId,);
            $music     = I('post.music');
            $ret       = $this->PosterInfoModel->where($posterMap)->save(['music' => $music]);
            if ($ret) {
                $this->ajaxReturn(array('status' => 1, 'info' => '音乐保存成功',));
            } else {
                $this->ajaxReturn(array('status' => 0, 'info' => '音乐保存失败',));
            }
        }
    }

    /**
     * 保存海报模板页面
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function savePosterPage()
    {
        $this->addPosterPage();
    }

    /**
     * 修改新版海报 基础信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function editBasicInfo()
    {
        $posterId = I('post.poster_id'); // 循环浏览
        if (empty($posterId)) {
            $this->ajaxReturn(array('status' => 0, 'info' => 'id is empty : ' . var_export($_REQUEST, 1),));
        }

        $data             = I('post.', '', 'mysql_real_escape_string');
        $basicInfo        = $this->buildBasicInfo($data, false);
        $title            = get_val($data, 'title', '');
        $mId              = I('post.batch_id');
        $marketingInfoMap = array('id' => $mId, 'node_id' => $this->node_id,);
        $this->MarketInfoModel->where($marketingInfoMap)->save(
                array('name' => $title,)
        );//todo 需要优化处理，不是每次都修改了name 这个没有必要每次都更新
        $posterMap = array('id' => $posterId,);
        $this->PosterInfoModel->where($posterMap)->save($basicInfo);
        $this->ajaxReturn(array('status' => '1', 'info' => '海报基本信息修改成功！', 'id' => $posterId,));
    }

    /**
     * 数据导出
     */
    public function export()
    {
        // 查询条件组合
        $where = "WHERE batch_type='" . $this->BATCH_TYPE . "' ";
        if (!empty($_POST)) {
            $filter    = array();
            $condition = array_map('trim', $_POST);
            if (issetAndNotEmpty($condition, 'key')) {
                $filter[] = "name LIKE '%{$condition['key']}%'";
            }
            if (issetAndNotEmpty($condition, 'status')) {
                $filter[] = "status = '{$condition['status']}'";
            }
            if (issetAndNotEmpty($condition, 'start_time')) {
                $condition['start_time'] = $condition['start_time'] . '000000';
                $filter[]                = "start_time >= '{$condition['start_time']}'";
            }
            if (issetAndNotEmpty($condition, 'end_time')) {
                $condition['end_time'] = $condition['end_time'] . '235959';
                $filter[]              = "end_time <= '{$condition['end_time']}'";
            }
        }
        if (!empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }

        $filter[] = "batch_type=" . $this->BATCH_TYPE;
        $filter[] = "node_id in({$this->nodeIn()})";
        $count    = $this->MarketInfoModel->count($filter);
        if ($count <= 0) {
            $this->error('无订单数据可下载');
        }

        $sql  = "SELECT name,add_time,start_time,end_time,
		CASE status WHEN '1' THEN '正常' ELSE '停用' END status,
		click_count
		FROM
		tmarketing_info {$where} AND node_id in({$this->nodeIn()})";
        $cols = array(
                'name'        => '活动名称',
                'add_time'    => '添加时间',
                'start_time'  => '活动开始时间',
                'end_time'    => '活动结束时间',
                'status'      => '状态',
                'click_count' => '访问量',
        );
        if (querydata_download($sql, $cols, $this->MarketInfoModel) == false) {
            $this->error('下载失败');
        }
    }

    /**
     * 状态修改
     */
    public function editStatus()
    {
        $orderId = I('order_id', null);
        if (is_null($orderId)) {
            $this->error('缺少订单号');
        }
        $result = M('ttg_order_info')->where("order_id='{$orderId}'")->find();
        if (!$result) {
            $this->error('未找到订单信息');
        }
        if ($this->isPost()) {
            $status = I('post.status', null);
            if (is_null($status)) {
                $this->error('缺少配送状态');
            }
            $data   = array('order_id' => $orderId, 'delivery_status' => $status,);
            $result = M('ttg_order_info')->save($data);
            if ($result) {
                $message = array('respId' => 0, 'respStr' => '更新成功',);
                $this->success($message);
            } else {
                $this->error('更新失败');
            }
        }

        $deliveryStatus = array('1' => '待配送', '2' => '配送中', '3' => '已配送',);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('order_id', $result['order_id']);
        $this->assign('delovery_status', $result['delivery_status']);
        $this->display();
    }

    /**
     * 删除草稿内容
     */
    public function removePosterData()
    {
        $this->_removePosterDraft();
        $this->ajaxReturn(array('status' => 1,));
    }

    /**
     *
     * @author Jeff Liu<liuwy@iamgeco.com.cn>
     * @return array
     */
    private function checkPostData()
    {
        $data = I('post.', '', 'mysql_real_escape_string');
        $memo = $this->buildMemo($data);
        if ($memo == array()) {
            $this->error('至少添加一页');
        }

        return $memo;
    }

    /**
     * 检测海报的权限
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $id
     *
     * @return mixed
     */
    private function _checkPosterPermission($id)
    {
        // 通过node_id,查一下是不是他自己的活动
        $map = array(
                'id'         => $id,
                'node_id'    => $this->node_id,  // 商户id
                'batch_type' => $this->BATCH_TYPE,
        ); // 活动类型

        $count = $this->MarketInfoModel->count($map);
        if ($count == 0) { // 不是自己的活动不能查看
            if (IS_AJAX) {
                $this->showErrorByErrno(-1047, true);
            } else {
                $this->showErrorByErrno(-1047, false);
            }
        }

        return true;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $data
     *
     * @return array
     */
    private function buildBasicInfo($data, $jsonEncode = true)
    {
        $labelId = $this->getLabelIdAndTryAddLabelInfo($data['batch_id'], $data['batch_type']);
        $title   = get_val($data, 'title');
        if ($title) {
            $title = str_replace("''", "'", $title);
        }
        $share_descript = get_val($data, 'share_descript');
        if ($share_descript) {
            $share_descript = str_replace("''", "'", $share_descript);
        }
        $basicInfo = array(
                'title'          => $title,
                'cover_img'      => isset($data['cover_img']) ? $data['cover_img'] : '',  // 海报活动封面图
                'share_descript' => $share_descript,
                'share_img'      => isset($data['img_resp']) ? $data['img_resp'] : '',
                'music'          => isset($data['music']) ? $data['music'] : '',
                'loop'           => isset($data['loop']) ? $data['loop'] : '',
                'label_id'       => $labelId,
                'batch_id'       => get_val($data, 'batch_id'),
                'node_id'        => $this->node_id,
                'batch_type'     => get_val($data, 'batch_type'),
        );

        if ($jsonEncode) {
            $basicInfo = json_encode($basicInfo);
        }
        return $basicInfo;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $data
     *
     * @return array
     */
    private function buildPosterPage($data)
    {
        $memo = get_val($data, 'memo', '');
        $name = get_val($data, 'name', 'newmod');
        if ($memo && $name && $this->node_id) {
            $add_time    = date('YmdHis');
            $memo_unique = md5($memo);
            return array(
                    'name'        => $name,
                    'memo'        => $memo,
                    'node_id'     => $this->node_id,
                    'memo_unique' => $memo_unique,
                    'add_time'    => $add_time,
            );
        } else {
            return [];
        }

    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $data
     *
     * @return array
     */
    private function buildMemo($data)
    {
        // 活动主参数
        $memo = isset($data['memo']) ? $data['memo'] : '';
        if (empty($memo)) {
            $memo = $this->NewPosterService->buildDefaultPage();
        }

        $readyData = json_decode($memo, true);

        return $readyData;
    }

    /**
     * 删除草稿内容
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param int $id
     *
     * @return mixed
     */
    private function _removePosterDraft($id = 0)
    {
        $id    = I('id', $id);
        $where = ['node_id' => $this->node_id, 'type' => self::NEW_POSTER_DRAFT_TYPE, 'org_id' => $id];
        $i     = 0;
        $d     = $this->DraftModel->getOne($where);
        $r     = true;
        if ($d) {
            do {
                $r = $this->DraftModel->deleteData($where, false);
                $i++;
                $sql      = $this->DraftModel->_sql();
                $linkConf = '';
                if (empty($r)) {
                    $dberr    = $this->DraftModel->getDbError();
                    $err      = $this->DraftModel->getError();
                    $linkConf = $this->DraftModel->getCurrentLinkConf();
                    log_write(
                            'removePosterDraftSingleErr ' . $i . ':$linkConf:' . var_export(
                                    $linkConf,
                                    1
                            ) . ': sql' . $sql . '$r:' . var_export($r, 1) . '$dberr:' . $dberr . ' $err:' . $err,
                            'WARN',
                            'deleteDraftErr'
                    );
                    $r = false;
                } else {
                    log_write('removePosterDraftSingleSucc first: sql' . $sql, 'INFO', 'deleteDraftErr');
                }
            } while ($i < 3 && empty($r));
            if ($r === false) {
                log_write(
                        'removePosterDraftAllErr: all sql:' . $sql . ' $linkConf:' . var_export(
                                $linkConf,
                                1
                        ) . PHP_EOL,
                        'WARN',
                        'deleteDraftErr'
                );

                $r = M('tdraft')->where($where)->delete();
                $sql = M('tdraft')->_sql();
                $linkConf = M('tdraft')->getCurrentLinkConf();
                if (empty($r)) {
                    log_write(
                            'removePosterDraftAllErr: final sql:' . $sql . ' $linkConf:' . var_export(
                                    $linkConf,
                                    1
                            ) . PHP_EOL,
                            'WARN',
                            'deleteDraftErr'
                    );
                } else {
                    log_write(
                            'removePosterDraftAllSucc: final sql:' . $sql . ' $linkConf:' . var_export(
                                    $linkConf,
                                    1
                            ) . PHP_EOL,
                            'WARN',
                            'deleteDraftErr'
                    );
                }

            }
        }
        return $r;
    }

    /**
     * 下载form相关数据
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     */
    public function downloadFormData()
    {
        $batchId = I('batch_id', null, 'mysql_real_escape_string');
        $status  = I('status', null, 'mysql_real_escape_string');
        if (is_null($batchId)) {
            $this->error('缺少参数');
        }
        $sql      = "SELECT mobile,add_time,
		CASE status WHEN '1' THEN '未中奖' ELSE '中奖' END status,prize_level
		FROM
		tcj_trace WHERE batch_id='{$batchId}' AND batch_type=5 AND node_id in({$this->nodeIn()})
		ORDER by status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type=5 AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
                'mobile'      => '手机号',
                'add_time'    => '中奖时间',
                'status'      => '是否中奖',
                'prize_level' => '奖品等级',
        );
        // 获取活动名称
        $batchName = M('tmarketing_info')->where("id='{$batchId}' AND node_id in({$this->nodeIn()})")->getField('name');
        $fileName  = $batchName . '-' . date('Y-m-d') . '-' . '中奖名单';
        $count     = M()->query($countSql);
        if ($count[0]['count'] <= 0) {
            $this->error('没有中奖数据');
        }
        if (empty($status)) {
            $this->ajaxReturn('', '', 1);
        }
        if (querydata_download($sql, $cols_arr, M(), $fileName) == false) {
            $this->error('下载失败或没有中奖数据');
        }
    }

    /**
     * 预览
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     */
    public function preview()
    {
        $autoSaveDraft = I('autoSave');
        if ($autoSaveDraft) {
            $this->autoSaveDraft(true);
        }
        $url  = $this->getPreviewUrl();
        $data = array(
                'status' => 1,
                'url'    => $url
        );
        $this->ajaxReturn($data, 'json');
    }

    /**
     * 获得预览url
     *
     * @param string $id
     *
     * @return string
     */
    public function getPreviewUrl($id = '')
    {
        if (empty($id)) {
            $id = I('id');
        }

        return $this->PreviewChannelService->getOrUpdatePreviewUrl(
                $id,
                $this->BATCH_TYPE,
                $this->node_id
        );
    }


    /**
     * 显示表单数据
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     */
    public function showFormInfo()
    {
        $finalData = $this->getFormInfo();

        $formList        = get_val($finalData, 'formContentList');
        $formElementInfo = get_val($finalData, 'formElementList');
        $formIdList      = get_val($finalData, 'formIdList');
        $formInfoList    = get_val($finalData, 'formInfoList');
        $isAjax          = I('isAjax');
        $this->assign('formIdList', $formIdList);
        $this->assign('formList', $formList);
        $this->assign('formInfoList', $formInfoList);
        $this->assign('formElementInfo', $formElementInfo);
        $this->assign('form_id', I('form_id'));
        $this->assign('id', I('id'));
        $this->assign('elementCount', count($formElementInfo));
        $tpl = '';
        if ($isAjax) {
            $tpl = './Home/Tpl/LabelAdmin/Public_showList.html';
        }
        $this->display($tpl);
    }


    /**
     * 获得form相关数据
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     * @return array
     */
    public function getFormInfo()
    {
        $finalData    = [];
        $formId       = I('form_id');
        $batchId      = I('id', 0);
        $formInfoList = $this->NewPosterService->getFormInfoListById($batchId, $this->node_id);
        $formIdList   = $this->NewPosterService->getFormIdListByFormInfo($formInfoList);
        if (empty($formId) && is_array($formIdList)) {
            $formId = get_val($formIdList, 0);
        }
        if ($formId) {
            if ($formIdList) {
                $map      = ['form_id' => $formId, 'node_id' => $this->node_id];
                $mapCount = $this->NewPosterService->getSpecialFormCollectCount($map);
                $data     = $_REQUEST;
                $pager    = $this->getPageData($mapCount, $data);
                $limit    = $pager->firstRow . ',' . $pager->listRows;

                $finalData = $this->NewPosterService->getSingleFormContentList($formId, $this->node_id, $limit);

                $finalData['pager']        = $pager;
                $finalData['formIdList']   = $formIdList;
                $finalData['formInfoList'] = $formInfoList;
            }
        }
        return $finalData;
    }

    /**
     * 临时用户添加海报信息
     */
    public function addBasicInfoForTempUser()
    {
        $newPosterTempNodeId = cookie('newPosterVisiter' . 'Node');
        if (!$newPosterTempNodeId) {
            $this->error('生成临时用户失败，用户可能未开启cookie');
        }
        $data                  = [
                'batch_id'       => '',
                'batch_type'     => $this->BATCH_TYPE,
                'poster_id'      => '',
                'cover_img'      => get_upload_url('./Home/Public/Image/poster/jichu.jpg'),
                'title'          => '电子海报',
                'share_descript' => '用户通过微信分享时显示',
                'loop'           => 0
        ];
        $basicInfo             = $this->buildBasicInfo($data, false);
        $title                 = get_val($data, 'title', '');
        $data                  = array(
                'memo'       => '',
                'name'       => $title,
                'batch_type' => $this->BATCH_TYPE,
                'status'     => 1,
                'pay_status' => 0,
                'node_id'    => $this->node_id,
                'add_time'   => date('YmdHis'),
        );
        $batchId               = $this->MarketInfoModel->add($data);
        $TempUserSequenceModel = D('TempUserSequence');
        $cookieId              = cookie('cookieId');
        $re                    = $TempUserSequenceModel->add(
                ['sequence' => $newPosterTempNodeId, 'm_id' => $batchId, 'cookie_id' => $cookieId]
        );
        if ($batchId) {
            $basicInfo['batch_id'] = $batchId;
            $this->PosterInfoModel->add($basicInfo);
            $this->NewPosterService->initDefaultPageConf();
            $this->redirect('MarketActive/NewPoster/add', array('id' => $batchId, 'tplId' => 7));//临时用户默认用这个7类型的模版
        } else {
            $this->error('临时用户新增海报出错');
        }
    }

    public function revisePosterDataToNewNode()
    {
        $newPosterTempNodeId = cookie('newPosterVisiterNode');
        cookie('newPosterVisiterNode', null);
        $TempUserSequenceModel = D('TempUserSequence');
        $re                    = $TempUserSequenceModel->where(['sequence' => $newPosterTempNodeId])->find();
        if (!$re) {
            $this->error('没有找到对应的海报活动');
        }
        // 活动
        $saveMarketRe = $this->MarketInfoModel->saveData(
                array('id' => $re['m_id']),
                array('node_id' => $this->node_id)
        );
        if (false === $saveMarketRe) {
            $this->error('存储活动到新注册用户失败');
        }
        $savePosterRe = $this->PosterInfoModel->where(array('batch_id' => $re['m_id']))->save(
                array('node_id' => $this->node_id)
        );
        if (false === $savePosterRe) {
            $this->error('存储海报内容到新注册用户失败');
        }
        D('FormInfo')->where(array('ref_id' => $re['m_id']))->save(array('node_id' => $this->node_id));
        //新增默认渠道
        $channelInfo = $this->ChannelModel->getChannelInfo(
                array(
                        'node_id'  => $this->node_id,
                        'type'     => $this->CHANNEL_TYPE,
                        'sns_type' => $this->CHANNEL_SNS_TYPE,
                )
        );
        if (!$channelInfo) { // 不存在则添加渠道
            //不存在则新增
            $channelList = array(
                    'name'        => '新版电子海报默认渠道',
                    'type'        => $this->CHANNEL_TYPE,
                    'sns_type'    => $this->CHANNEL_SNS_TYPE,
                    'status'      => '1',
                    'start_time'  => date('YmdHis'),
                    'end_time'    => date('YmdHis', strtotime("+1 year")),
                    'add_time'    => date('YmdHis'),
                    'click_count' => 0,
                    'cj_count'    => 0,
                    'send_count'  => 0,
                    'node_id'     => $this->node_id,
            );

            $channelId = $this->ChannelModel->add($channelList);
            if (!$channelId) {
                $this->error('添加新版电子海报默认渠道号失败');
            }
        }
        //修改草稿表
        $this->DraftModel->where(array('org_id' => $re['m_id']))->save(array('node_id' => $this->node_id));
        session('tempNodeType', null);
        $backToEditPoster = I('backToEditPoster');
        if ($backToEditPoster == 1) {
            $this->redirect('MarketActive/NewPoster/add', array('id' => $re['m_id'], 'tplId' => 7));
        } else {
            $this->redirect('saveSuccess', array('batch_id' => $re['m_id']));
        }
    }
}