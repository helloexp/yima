<?php

/**
 * 抽奖
 *
 * @author Jeff Liu<liuwy@imageco.com.cn>
 */
class FormCommonService
{

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


    public function __construct()
    {
        $this->FormInfoModel         = D('FormInfo');
        $this->FormElementInfoModel  = D('FormElementInfo');
        $this->FormElementValueModel = D('FormElementValue');
        $this->FormRelationModel     = D('FormRelation');
    }

    /**
     * @return FormElementInfoModel|Model
     */
    public function getFormElementModel()
    {
        if (empty($this->FormElementInfoModel)) {
            $this->FormElementInfoModel = D('FormElementInfo');
        }

        return $this->FormElementInfoModel;
    }

    /**
     *
     * @param $formId
     * @param $formOriginData
     *
     * @return array
     */
    public function generateFormElementData($formId, $elementOrigin, $withId = false)
    {
        $formElementList = array();
        $originFormId    = get_val($formOriginData, 'form');
        if (is_numeric($originFormId)) {
            $formId = $originFormId;
        }

        foreach ($elementOrigin as $index => $item) {
            $formElementList[] = $this->generateSingleFormElementData($formId, $item, $withId);
        }
        return $formElementList;
    }

    /**
     * @param      $formId
     * @param      $elementInfo
     * @param bool $withId
     *
     * @return array
     */
    public function generateSingleFormElementData($formId, $elementInfo, $withId = false)
    {
        $return = [
                'form_id'       => $formId,
                'input_type'    => get_val($elementInfo, 'type'),
                'input_name'    => get_val($elementInfo, 'text'),
                'is_required'   => get_val($elementInfo, 'must'),
                'input_order'   => get_val($elementInfo, 'order'),
                'default_value' => get_val($elementInfo, 'default_value', ''),
                'placeholder'   => get_val($elementInfo, 'placeholder', ''),
        ];
        if ($withId) {
            $return['id'] = get_val($elementInfo, 'id');
        }

        return $return;
    }


    /**
     * @param $mId
     * @param $batchType
     * @param $formOrder
     * @param $nodeId
     * @param $formOriginData
     *
     * @return array
     */
    public function generateFormData($mId, $batchType, $formOrder, $nodeId, $formOriginData)
    {
        $elementOrigin   = $formOriginData['list'];
        $formElementList = array();
        $formId          = $formOriginData['id'];

        $formFinalData = array(
                'id'         => $formId,
                'node_id'    => $nodeId,
                'ref_id'     => $mId,
                'ref_type'   => $batchType,
                'form_order' => $formOrder,
        );

        foreach ($elementOrigin as $index => $item) {
            $formElementList[] = $this->generateSingleFormElementData($formId, $item);
        }

        return array(
                'form'            => $formFinalData,
                'formElementList' => $formElementList,
        );
    }


    /**
     * @param      $formId
     * @param bool $format
     *
     * @return mixed
     */
    public function getFormElementByFormId($formId, $format = true)
    {
        $return = $this->FormElementInfoModel->getList(['form_id' => $formId]);
        if ($format) {
            foreach ($return as $index => $item) {
                $return[$index] = $this->formatSingleFormElement($item);
            }
        }
        return $return;
    }

    /**
     * @param $formElement
     */
    public function formatSingleFormElement($formElement)
    {
        $input_order = $formElement['input_order'];
        if ($input_order === '0') {
            $input_order = '';
        }
        $formElement['input_order'] = $input_order;
        $is_required                = $formElement['is_required'];
        if ($input_order === '1') {
            $is_required = true;
        }
        $formElement['is_required'] = $is_required;

        return $formElement;
    }

    /**
     *
     * 单个form处理
     *
     * @param $formId
     * @param $formElementList
     *
     * @return array
     */
    public function diffFormElement($formId, $formElementList)
    {
        $finalData = [];
        if ($formElementList) {
            $originFormElementList = $this->getFormElementByFormId($formId);
            if (empty($originFormElementList)) { //全部为新增
                $finalData['add'] = $formElementList;
            } else {
                foreach ($formElementList as $index => $elementData) {
                    $id = get_val($elementData, 'id');
                    if (empty($id)) { //新增
                        $finalData['add'][$index] = $elementData;
                        continue;
                    }
                    if (is_array($originFormElementList) && $originFormElementList) {
                        foreach ($originFormElementList as $originIndex => $originElementData) {
                            $originId = get_val($originElementData, 'id');
                            if ($originId == $id) { //
                                if ($elementData != $originElementData) { //有修改
                                    $finalData['modify'][$id] = $elementData;
                                }
                                unset($originFormElementList[$originIndex]);
                                continue;
                            }
                        }
                    }
                }
                if (is_array($originFormElementList) && $originFormElementList) { //删除的表单元素
                    foreach ($originFormElementList as $originIndex => $originElementData) {
                        $originId              = get_val($originElementData, 'id');
                        $finalData['delete'][] = $originId;
                    }
                }
            }
        }

        return $finalData;
    }

    /**
     * @param $ids
     *
     * @return mixed
     */
    public function deleteForm($ids)
    {
        if (is_array($ids)) {
            $where = implode(',', $ids);
        } else {
            $where = $ids;
        }
        return $this->FormInfoModel->delete($where);
    }

    /**
     * @author Jeff.Liu<liuwy@.imageco.com.cn>
     *
     * @param $data
     *
     * @return mixed
     */
    public function addForm($data)
    {
        return $this->FormInfoModel->add($data);
    }

    /**
     *
     * @author Jeff.Liu<liuwy@.imageco.com.cn>
     *
     * @param $data
     *
     * @return bool|false|int|string
     */
    public function addFormElement($data)
    {
        return $this->FormElementInfoModel->addAll($data);
    }

    /**
     *
     * @author Jeff.Liu<liuwy@.imageco.com.cn>
     *
     * @param $data
     * @param $where
     *
     * @return bool
     */
    public function updateFormElement($data, $where)
    {
        return $this->FormElementInfoModel->saveData($data, $where);
    }

    /**
     * @author Jeff.Liu<liuwy@.imageco.com.cn>
     *
     * @param $ids
     *
     * @return mixed
     */
    public function deleteFormEement($ids)
    {
        if (is_array($ids)) {
            $where = implode(',', $ids);
        } else {
            $where = $ids;
        }
        return $this->FormElementInfoModel->delete($where);
    }

    /**
     * 保存form相关内容
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $data
     * @param $where
     *
     * @return bool
     */
    public function saveFormAndElement($data, $where)
    {
        if (empty($this->FormInfoModel)) {
            $this->FormInfoModel = D('FormInfo');
        }

        $cleanedFromData = $this->generateFormDataFromOriginData($data);

        //save form start
        $formData = get_val($cleanedFromData, 'form');
        $ret      = $this->saveForm($formData, $where);
        //save form end

        //save form element start
        $formElementData = get_val($cleanedFromData, 'formElementList');
        $ret             = $this->saveFormElementInfo($formElementData, $where); //todo 需要处理
        //save form element end

        return true;
    }

    /**
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $formData
     * @param $options
     *
     * @return array
     */
    public function saveFormAndElementByData($formData)
    {
        $finalData = [];
        foreach ($formData as $formId => $form) {
            $finalData[$formId] = $this->diffFormElement($formId, $form);
        }

        return array();
    }

    /**
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $oldFormIdList
     * @param $newFormIdList
     *
     * @return array
     */
    public function diffForm($oldFormIdList, $newFormIdList)
    {
        $diffForm     = [];
        $deleteIdList = array_diff($oldFormIdList, $newFormIdList);
        $newIdList    = array_diff($newFormIdList, $oldFormIdList);
        $modify       = array_intersect($newFormIdList, $oldFormIdList);
        if ($deleteIdList) {
            $diffForm['deleteIdList'] = $deleteIdList;
        }
        if ($newIdList) {
            $diffForm['newIdList'] = $newIdList;
        }

        if ($modify) {
            $diffForm['modify'] = $modify;
        }

        return $diffForm;
    }

    /**
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $formData
     * @param $options
     */
    public function processFormAndElement(&$formData, $options)
    {
        $batchId         = get_val($options, 'batch_id');
        $where           = ['ref_id' => $batchId];
        $oldFormInfoList = $this->FormInfoModel->getList($where);
        $oldFormIdList   = [];
        if (is_array($oldFormInfoList)) {
            foreach ($oldFormInfoList as $index => $item) {
                $oldFormIdList[] = $item['id'];
            }
        }

        $newFormIdList = array_keys($formData);
        if (empty($oldFormIdList)) {
            $oldFormIdList = [];
        }
        $diffForm = $this->diffForm($oldFormIdList, $newFormIdList);
        if (issetAndNotEmpty($diffForm, 'deleteIdList')) {
            foreach ($diffForm['deleteIdList'] as $deleteIdx => $deleteFormId) {
                if (is_array($oldFormIdList)) {
                    foreach ($oldFormIdList as $oldIdx => $oldForm) {
                        if ($deleteFormId === $oldForm['id']) {
                            unset($oldFormIdList);
                        }
                    }
                }
            }
        }
        foreach ($diffForm as $operateType => $formIdList) {
            switch ($operateType) {
                case 'deleteIdList':
                    $formIdStr = implode(',', $formIdList);
                    $this->FormInfoModel->delete($formIdStr);
                    $this->FormElementInfoModel->deleteData('form_id in (' . $formIdStr . ')');
                    break;
                case 'newIdList':
                    $newFormData = $this->generateSingleFormData($options);
                    foreach ($formIdList as $formId) {
                        $name                          = isset($formData[$formId][0]['posterPageName']) ? $formData[$formId][0]['posterPageName'] : '';
                        $newFormData['extension_info'] = $name;
                        $newFormId                     = $this->FormInfoModel->add($newFormData);
                        $formOriginData                = get_val($formData, $formId);
                        $formElementData               = $this->generateFormElementData($newFormId, $formOriginData);
                        foreach ($formElementData as $index => $singleFormElement) {
                            if (verify_val($singleFormElement, 'input_type', 'formbtn', '!=')) {
                                $newElementId                    = $this->FormElementInfoModel->add(
                                        $singleFormElement
                                );
                                $formData[$formId][$index]['id'] = $newElementId;
                            }
                            $formData[$formId][$index]['newForm'] = $newFormId;
                        }
                    }
                    break;
                case 'modify':
                default:
                    foreach ($formIdList as $formId) {
                        $formOriginData = get_val($formData, $formId);
                        $name           = isset($formData[$formId][0]['posterPageName']) ? $formData[$formId][0]['posterPageName'] : '';

                        $currentFormData['extension_info'] = $name;
                        $this->FormInfoModel->saveData($currentFormData, ['id' => $formId]);

                        $formElementData = $this->generateFormElementData($formId, $formOriginData, true);
                        $tmpData         = $this->diffFormElement($formId, $formElementData);
                        $this->processSingleFormElement($tmpData, $formId, $formData);
                    }
            }
        }
    }

    /**
     * @param $tmpData
     * @param $formId
     * @param $formData
     *
     * @return int
     */
    public function processSingleFormElement($tmpData, $formId, &$formData)
    {
        foreach ($tmpData as $opt => $formELementList) {
            if ($formELementList) {
                switch ($opt) {
                    case 'add':
                        foreach ($formELementList as $index => $singleFormElement) {
                            $newElementId                    = $this->FormElementInfoModel->add($singleFormElement);
                            $formData[$formId][$index]['id'] = $newElementId;
                        }
                        break;
                    case 'modify':
                        foreach ($formELementList as $index => $formElement) {
                            $elementId = get_val($formElement, 'id');
                            $this->FormElementInfoModel->saveData($formElement, ['id' => $elementId]);
                        }
                        break;
                    case 'delete':
                        $this->FormElementInfoModel->delete(implode(',', $formELementList));
                        break;
                    default:
                        //todo 有错
                }
            }
        }

        return 0;
    }

    /**
     * 保存tform_info表数据
     *
     * @param $data
     * @param $where
     *
     * @return bool
     */
    public function saveForm($data, $where)
    {
        return $this->FormInfoModel->update($data, $where);
    }


    public function generateSingleFormData($data)
    {
        $formData               = [];
        $formData['node_id']    = get_val($data, 'node_id');
        $formData['ref_id']     = get_val($data, 'ref_id');
        $formData['ref_type']   = get_val($data, 'ref_type');
        $formData['form_order'] = get_val($data, 'form_order');
        $formData['add_time']   = date('YmdHis');
        return $formData;
    }


    /**
     *
     * @param $data
     *
     * @return array
     */
    public function generateFormDataFromOriginData($data)
    {
        $mId            = get_val($data, 'm_id');
        $batchType      = get_val($data, 'batch_type');
        $formOrder      = get_val($data, 'form_order');
        $nodeId         = get_val($data, 'node_id');
        $formOriginData = get_val($data, 'origin');

        return $this->generateFormData($mId, $batchType, $formOrder, $nodeId, $formOriginData);

    }

    /**
     * 保存form input 相关内容
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $data
     * @param $where
     *
     * @return bool
     */
    public function saveFormElementInfo($data, $where)
    {
        if (empty($this->FormElementInfoModel)) {
            $this->FormElementInfoModel = D('FormElementInfo');
        }

        return $this->FormElementInfoModel->update($data, $where);
    }

    /**
     * 保存form input值相关内容
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $data
     * @param $where
     *
     * @return bool
     */
    public function saveFormELementValue($data, $where)
    {
        if (empty($this->FormElementValueModel)) {
            $this->FormElementValueModel = D('FormElementValue');
        }

        return $this->FormElementValueModel->update($data, $where);
    }

    /**
     * @param $data
     *
     * @param $platformData
     *
     * @return bool|false|int|string
     */
    public function addFormELementValue($data, $platformData)
    {
        $finalData        = $this->parseFormElementValueData($data);
        $formRelationData = $this->buildFormRelationData($finalData, $platformData);
        $relationId       = $this->FormRelationModel->add($formRelationData);
        $formId           = get_val($finalData, 'formId');
        $formElementData  = get_val($finalData, 'elementList');
        if ($relationId) {
            $formatedFormElementData = $this->buildFormElementValueData($formElementData, $formId, $relationId);
            $ret                     = $this->FormElementValueModel->addAll($formatedFormElementData);
            $ret                     = (bool)$ret;
        } else {
            $ret = false;
        }

        return $ret;
    }

    private function buildFormRelationData($finalData, $platformData)
    {
        $formId           = get_val($finalData, 'formId');
        $refId            = get_val($finalData, 'refId');
        $refType          = get_val($finalData, 'refType');
        $nodeId           = get_val($platformData, 'node_id');
        $formRelationData = [
                'form_id'  => $formId,
                'node_id'  => $nodeId,
                'ref_id'   => $refId,
                'ref_type' => $refType,
                'add_time' => date('YmdHis'),
        ];
        return $formRelationData;
    }

    /**
     * @param $formElementData
     * @param $formId
     * @param $relationId
     *
     * @return array
     */
    private function buildFormElementValueData($formElementData, $formId, $relationId)
    {
        $formatedFormElementData = [];
        foreach ($formElementData as $elementId => $value) {
            $formatedFormElementData[] = [
                    'form_id'         => $formId,
                    'relation_id'     => $relationId,
                    'form_element_id' => $elementId,
                    'value'           => $value,
            ];
        }

        return $formatedFormElementData;
    }

    private function parseFormElementValueData($data)
    {
        $data                     = get_val($data, 'formData');
        $finalData                = [];
        $formId                   = get_val($data, 'formId');
        $elementList              = get_val($data, 'elementList');
        $finalData['formId']      = $formId;
        $finalData['elementList'] = $elementList;
        return $finalData;
    }
}