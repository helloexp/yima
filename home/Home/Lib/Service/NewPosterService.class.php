<?php

/**
 * 新版海报
 *
 * @author Jeff Liu<liuwy@imageco.com.cn>
 */
class NewPosterService
{

    /**
     * @var MarketInfoModel
     */
    protected $MarketInfoModel;

    /**
     * @var PosterInfoModel
     */
    protected $PosterInfoModel;

    /**
     * @var DraftModel
     */
    protected $DraftModel;

    /**
     * @var array
     */
    protected $defaultPageConf;

    /**
     * @var FormInfoModel
     */
    protected $FormInfoModel;

    /**
     * @var FormElementInfoModel
     */
    protected $FormElementInfoModel;

    /**
     * @var FormElementValueModel
     */
    protected $FormElementValueModel;

    /**
     * @var FormRelationModel
     */
    protected $FormRelationModel;

    /**
     * @var BatchChannelModel
     */
    protected $BatchChannelModel;

    public function __construct()
    {
        $this->MarketInfoModel = D('MarketInfo');
        $this->PosterInfoModel = D('PosterInfo');
        $this->DraftModel      = D('Draft');

        $this->FormInfoModel         = D('FormInfo');
        $this->FormElementInfoModel  = D('FormElementInfo');
        $this->FormElementValueModel = D('FormElementValue');
        $this->FormRelationModel     = D('FormRelation');
        $this->BatchChannelModel     = D('BatchChannel');
    }

    public function makretingInfoJoinPosterInfo()
    {
        return $this->PosterInfoModel->getTableName() . ' t2 on t1.id=t2.batch_id';
    }

    /**
     * @param        $batchType
     * @param        $nodeIn
     * @param        $data
     * @param string $tableAlias
     *
     * @return array
     */
    public function buildFullPosterListMap($batchType, $nodeIn, $data, $tableAlias = '')
    {
        if ($tableAlias) {
            $tableAlias .= '.';
        }
        $map = array($tableAlias . 'node_id' => array('exp', "in (" . $nodeIn . ")",),);
        if (issetAndNotEmpty($data, 'key')) {
            $map[$tableAlias . 'name'] = array('like', '%' . $data['key'] . '%',);
        }
        if (issetAndNotEmpty($data, 'status')) {
            $map[$tableAlias . 'status'] = $data['status'];
        }
        // 处理特殊查询字段
        $beginDate = I('start_time', null, 'mysql_real_escape_string,trim');
        if (!empty($beginDate)) {
            $map[$tableAlias . 'add_time'] = array('egt', $beginDate . '000000',);
        }
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (!empty($endDate)) {
            $map[$tableAlias . 'add_time'] = array('elt', $endDate . '235959',);
        }
        $map[$tableAlias . 'batch_type'] = $batchType;
        return $map;
    }

    /**
     * @param $map
     * @param $tableAlias
     *
     * @return mixed
     */
    public function getPosterCount($map, $tableAlias)
    {
        $t1 = $this->MarketInfoModel->getTableName() . ' ' . $tableAlias;
        $this->PosterInfoModel->join($this->makretingInfoJoinPosterInfo());
        $return = $this->PosterInfoModel->table($t1)->where($map)->count();
        return $return;
    }

    /**
     * 获得form搜集资料次数
     *
     * @param $where
     *
     * @return mixed
     */
    public function getSpecialFormCollectCount($where)
    {
        return $this->FormRelationModel->where($where)->count();
    }

    /**
     * @param        $where
     * @param string $limit
     * @param string $field
     *
     * @return array|mixed
     */
    public function getSpecialFormCollectInfo($where, $limit = '', $field = 'id')
    {
        $relationIdList = [];
        if ($limit) {
            $relationList = $this->FormRelationModel->getListWithLimit($where, $limit, $field);
        } else {
            $relationList = $this->FormRelationModel->getList($where, $field);
        }

        $formCollectList = [];
        if ($relationList) {
            foreach ($relationList as $idInfo) {
                $relationIdList[] = $idInfo['id'];
            }
            $where           = 'relation_id in (' . implode(',', $relationIdList) . ')';
            $formCollectList = $this->FormElementValueModel->getList($where);
        }
        return $formCollectList;
    }

    public function formatFormCollectList($formCollectList)
    {
        $formatCollectList = [];
        if (is_array($formCollectList) && $formCollectList) {
            foreach ($formCollectList as $item) {
                $formatCollectList[$item['relation_id']][] = [
                        'form_element_id' => $item['form_element_id'],
                        'value'           => $item['value']
                ];
            }
        }

        return $formatCollectList;
    }

    public function getSpecialFormElementInfo($where, $withoutFormBtn = true)
    {
        $return = $this->FormElementInfoModel->getList($where, 'id,input_name,input_type');
        if ($withoutFormBtn) {
            foreach ($return as $index => $item) {
                if ($item['input_type'] === 'formbtn') {
                    unset($return[$index]);
                }
            }
        }
        return $return;
    }

    /**
     * @param $map
     * @param $limit
     * @param $tableAlias
     *
     * @return mixed
     */
    public function getFullPosterList($map, $limit, $tableAlias, $channelId)
    {
        $t1      = $this->MarketInfoModel->getTableName() . ' ' . $tableAlias;
        $orderBy = 't1.id desc';

        $this->PosterInfoModel->join($this->makretingInfoJoinPosterInfo());
        $list = $this->PosterInfoModel->getData(
                $t1,
                $map,
                0,
                't1.*,t2.cover_img,t2.form_collect_count',
                $orderBy,
                $limit
        );

        $batchIdList = [];
        if (is_array($list)) {
            foreach ($list as $item) {
                $batchIdList[] = get_val($item, 'id');
            }
        }

        $labelList = $this->BatchChannelModel->getList(
                'batch_id IN(' . implode(',', $batchIdList) . ') AND channel_id='.$channelId,
                'id,batch_id'
        );
        $labelList = $this->formatLabelInfo($labelList);
        foreach ($list as $key => $marketingInfo) {
            if (issetAndNotEmpty($marketingInfo, 'cover_img')) {
                $list[$key]['cover_img'] = get_upload_url($marketingInfo['cover_img']);
            } else {
                $list[$key]['cover_img'] = C('TMPL_PARSE_STRING.__PUBLIC__') . '/Image/poster/poster.png';
            }
            $list[$key]['label_id'] = get_val($labelList, $marketingInfo['id']);
        }
        return $list;
    }

    private function formatLabelInfo($labelList)
    {
        $finalData = [];
        if (is_array($labelList)) {
            foreach ($labelList as $index => $item) {
                $finalData[$item['batch_id']] = $item['id'];
            }
        }
        return $finalData;
    }

    /**
     * @param $id
     * @param $nodeId
     * @param $type
     *
     * @return array|mixed|null
     */
    public function getDraftContent($id, $nodeId, $type)
    {
        // 如果有草稿的覆盖掉前面assign的值
        $posterDraft = $this->DraftModel->getOne(
                array('org_id' => $id, 'node_id' => $nodeId, 'type' => $type,)
        );
        if ($posterDraft) {
            $expireTime = (time() - 86400);
            $addTime    = get_val($posterDraft, 'add_time', 0);
            $addTime    = strtotime($addTime);
            if ($expireTime > $addTime) { //删除一天的草稿内容 (其他人的也会一并删除掉)
                $where['add_time'] = array('lt', date('Y-m-d H:i:s', (time() - 86400)),);
                $where['type']     = $type;
                $this->DraftModel->deleteData($where);
                $posterDraft = [];
            } else {
                $posterDraft = get_val($posterDraft, 'content', '');
            }
        }
        return $posterDraft;
    }


    /**
     *
     */
    public function getDefaultPageConf()
    {
        $this->initDefaultPageConf();

        return $this->defaultPageConf;
    }

    /**
     * 初始化默认模板页面conf信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function initDefaultPageConf()
    {
        if (empty($this->defaultPageConf)) {
            $defaultPageConfFile = CONF_PATH . '/LabelAdmin/configNewPoster.php';
            if (file_exists($defaultPageConfFile)) {
                $defaultPageConf       = include $defaultPageConfFile;
                $this->defaultPageConf = isset($defaultPageConf['defaultPage']) ? $defaultPageConf['defaultPage'] : array();
            }
        }
    }

    /**
     * 新版电子海报默认模板页面
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param int $tplId
     *
     * @return string
     */
    public function buildDefaultPage($tplId = 1)
    {
        $this->initDefaultPageConf();

        if (isset($this->defaultPageConf[$tplId]) && $this->defaultPageConf[$tplId]) {
            return $this->defaultPageConf[$tplId]['content'];
        } else {
            return '';
        }
    }

    /**
     * @param $id
     * @param $nodeId
     */
    public function deletePoster($id, $nodeId)
    {
        $formList   = $this->FormInfoModel->getList(['node_id' => $nodeId, 'ref_id' => $id]);
        $formIdList = [];
        if ($formList) {
            foreach ($formList as $index => $form) {
                $formIdList[] = get_val($form, 'id');
            }
            $where = 'form_id in (' . implode(',', $formIdList) . ')';
            $this->FormElementInfoModel->deleteData($where);
            $this->FormElementValueModel->deleteData($where);
            $this->FormRelationModel->deleteData($where);
        }
        $where = ['id' => $id, 'node_id' => $nodeId];
        $this->MarketInfoModel->deleteData($where);
        $this->PosterInfoModel->deleteData($where);

        $where = ['node_id' => $nodeId, 'ref_id' => $id];
        $this->FormInfoModel->deleteData($where);

        return true;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $formId
     * @param $nodeId
     *
     * @return array
     */
    public function getSingleFormContentList($formId, $nodeId, $limit)
    {
        $map             = ['form_id' => $formId, 'node_id' => $nodeId];
        $formList        = $this->getSpecialFormCollectInfo($map, $limit);
        $formatFormList  = $this->formatFormCollectList($formList);
        $formElementInfo = $this->getSpecialFormElementInfo(['form_id' => $formId]);

        return ['formContentList' => $formatFormList, 'formElementList' => $formElementInfo];
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param     $mapCount
     * @param     $data
     * @param int $countPerPage
     *
     * @return Page
     */
    public function getPageData($mapCount, $data, $countPerPage = 10)
    {
        import('ORG.Util.Page'); // 导入分页类
        $pager = new Page($mapCount, $countPerPage); // 实例化分页类
        // 传入总记录数和每页显示的记录数
        if (defined('PHP_QUERY_RFC3986')) {
            // RFC3986会将空格转化为 %20 @link http://php.net/manual/en/function.http-build-query.php
            $param = http_build_query($data, null, '&', PHP_QUERY_RFC3986);
        } else {
            $param = http_build_query($data);
        }
        $pager->parameter .= $param;
        return $pager;
    }

    /**
     * @param $batchId
     * @param $nodeId
     *
     * @return mixed
     */
    public function getFormInfoListById($batchId, $nodeId)
    {
        return $this->FormInfoModel->getList('ref_id = ' . $batchId . ' AND node_id=' . $nodeId);
    }

    public function updateFormExtensionInfo($formId, $nodeId, $extensionInfo)
    {
        return $this->FormInfoModel->saveData(
                ['extension_info' => $extensionInfo],
                ['form_id' => $formId, 'node_id' => $nodeId]
        );
    }

    /**
     * @param $formList
     *
     * @return array
     */
    public function getFormIdListByFormInfo($formList)
    {
        $finalIdList = [];
        if (is_array($formList)) {
            foreach ($formList as $id) {
                $finalIdList[] = get_val($id, 'id');
            }
        }

        return $finalIdList;
    }
}