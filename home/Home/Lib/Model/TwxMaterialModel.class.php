<?php

class TwxMaterialModel extends Model {
    // 根据ID获取 ID(如果id是数组，则 可以是列表)
    /*
     * $id 字符串或者数组，如果是字符串，则只返回单个，如果是数组，则返回多个 $getSub 计算是否返回下级子素材，true 需要，false
     * 不需要 ，默认不需要 false
     */
    public function getMaterialInfoById($id, $node_id = '', $getSub = false) {
        if (! $id)
            return null;
        $ids = $id;
        if (is_array($id)) {
            $ids = implode("','", $id);
        }
        $resultArr = $this->where("id in ('" . $ids . "')")->select();
        if (! $resultArr)
            return $resultArr;
        foreach ($resultArr as &$result) {
            // 如果是多图文,且要获取下级
            if ($getSub && ($result['material_type'] == '2' ||
                 $result['material_type'] == '1')) {
                $result2 = $this->where("parent_id='" . $result['id'] . "'")->select() or
                 $result2 = array();
            foreach ($result2 as &$vv) {
                // 处理一下图片地址
                $vv['img_url'] = $this->_getImgUrl($vv['material_img']);
            }
            unset($vv);
            $result['sub_material'] = $result2;
        }
        $result['img_url'] = $this->_getImgUrl($result['material_img']);
    }
    unset($result);
    
    if (is_array($id)) {
        return $resultArr;
    } else {
        return $resultArr ? $resultArr[0] : $resultArr;
    }
}

private function _getImgUrl($imgname) {
    if (! $imgname) {
        return '';
    }
    $img_upload_path = './Home/Upload/Weixin/'; // 设置附件上传目录
                                                // 旧版
    if (basename($imgname) == $imgname) {
        return $img_upload_path . $imgname;
    } else {
        return get_upload_url($imgname);
    }
}
}
