<?php

// 广西石油首页
class MerchantAction extends CnpcGXAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $post = I('request.');
        $map = array(
            "a.status" => 1);
        $id = I('id');
        $parent_id = I('parent_id', '', 'trim,mysql_real_escape_string');
        $catalog_id = I('catalog_id', '', 'trim,mysql_real_escape_string');
        $merchant_name = I('merchant_name', '', 'trim,mysql_real_escape_string');
        $begin_time = I('start_time', '', 'trim,mysql_real_escape_string');
        $end_time = I('end_time', '', 'trim,mysql_real_escape_string');
        $status = I('status', '', 'trim,mysql_real_escape_string');
        
        if ($id != '') {
            $map['a.id'] = (int) $id;
        }
        if ($parent_id != '') {
            $map['a.parent_id'] = $parent_id;
        }
        if ($catalog_id != '') {
            $map['a.catalog_id'] = $catalog_id;
        }
        if ($merchant_name != '') {
            $map['a.merchant_name'] = array(
                'like', 
                '%' . $merchant_name . '%');
        }
        if ($status != '') {
            $map['a.show_status'] = $status;
        }
        
        if ($begin_time != '') {
            $map['a.add_time'][] = array(
                'EGT', 
                $begin_time . "000000");
        }
        if ($end_time != '') {
            $map['a.add_time'][] = array(
                'ELT', 
                $end_time . "232929");
        }
        
        import("ORG.Util.Page");
        $count = M()->table("tfb_cnpcgx_node_info a")
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        $page = $p->show();
        $list = M()->table("tfb_cnpcgx_node_info a")
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('a.order_sort desc,a.id desc')
            ->field('a.*')
            ->select();
        
        $parentInfo = M('tfb_cnpcgx_catalog')->field('id,catalog_name')
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $catalogInfo = array();
        foreach ($parentInfo as $v) {
            $catalogInfo[$v['id']] = M('tfb_cnpcgx_catalog')->field(
                'id,catalog_name')
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        $this->assign('post', $post);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign("catalogInfo", $catalogInfo);
        $this->assign("parentInfo", $parentInfo);
        $this->display();
    }

    public function download() {
        $post = I('request.');
        $map = array(
            "a.status" => 1);
        $id = I('id');
        $parent_id = I('parent_id', '', 'trim,mysql_real_escape_string');
        $catalog_id = I('catalog_id', '', 'trim,mysql_real_escape_string');
        $merchant_name = I('merchant_name', '', 'trim,mysql_real_escape_string');
        $begin_time = I('start_time', '', 'trim,mysql_real_escape_string');
        $end_time = I('end_time', '', 'trim,mysql_real_escape_string');
        $status = I('status', '', 'trim,mysql_real_escape_string');
        
        if ($id != '') {
            $map['a.id'] = (int) $id;
        }
        if ($parent_id != '') {
            $map['a.parent_id'] = $parent_id;
        }
        if ($catalog_id != '') {
            $map['a.catalog_id'] = $catalog_id;
        }
        if ($merchant_name != '') {
            $map['a.merchant_name'] = array(
                'like', 
                '%' . $merchant_name . '%');
        }
        if ($status != '') {
            $map['a.show_status'] = $status;
        }
        
        if ($begin_time != '') {
            $map['a.add_time'][] = array(
                'EGT', 
                $begin_time);
        }
        if ($end_time != '') {
            $map['a.add_time'][] = array(
                'ELT', 
                $end_time);
        }
        $count = M()->table("tfb_cnpcgx_node_info a")
            ->where($map)
            ->count();
        
        $list = M()->table("tfb_cnpcgx_node_info a")
            ->where($map)
            ->order('a.order_sort desc,a.id desc')
            ->field('a.*')
            ->select();
        if ($count == 0) {
            $this->error('无查寻结果下载！');
        }
        $fileName = date("YmdHis") . "-中国石油广西销售商户列表.csv";
        $fileName = iconv('utf-8', 'gbk', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title = "编号,商户名称,商户简称,注册时间,一级分类,二级分类,服务热线,负责人,手机号码,商户简介,状态\r\n";
        echo $title = iconv('utf-8', 'gbk', $title);
        if ($list) {
            foreach ($list as $vo) {
                $vo['id'] = sprintf('%05s', $vo['id']);
                
                if ($vo['show_status'] == 0) {
                    $vo['show_status'] = "未展示";
                } elseif ($vo['status'] == 1) {
                    $vo['show_status'] = "展示中";
                } else {
                    $vo['show_status'] = "未知";
                }
                $vo['parent_id'] = $this->catalog_array[$vo['parent_id']];
                $vo['catalog_id'] = $this->catalog_array[$vo['catalog_id']];
                iconv_arr('utf-8', 'gbk', $vo);
                echo "=\"{$vo['id']}\",=\"{$vo['merchant_name']}\",=\"{$vo['merchant_short_name']}\",=\"{$vo['add_time']}\",=\"{$vo['parent_id']}\",=\"{$vo['catalog_id']}\",=\"{$vo['hot_line_tel']}\",=\"{$vo['contact_name']}\",=\"{$vo['mobile']}\",=\"{$vo['description']}\",=\"{$vo['show_status']}\"" .
                     "\r\n";
            }
        }
    }

    public function add() {
        $parentInfo = M('tfb_cnpcgx_catalog')->field('id,catalog_name')
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $catalogInfo = array();
        foreach ($parentInfo as $v) {
            $catalogInfo[$v['id']] = M('tfb_cnpcgx_catalog')->field(
                'id,catalog_name')
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        $this->assign("catalogInfo", $catalogInfo);
        $this->assign("parentInfo", $parentInfo);
        $this->display();
    }

    public function edit() {
        $id = I('id');
        $parentInfo = M('tfb_cnpcgx_catalog')->field('id,catalog_name')
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $catalogInfo = array();
        foreach ($parentInfo as $v) {
            $catalogInfo[$v['id']] = M('tfb_cnpcgx_catalog')->field(
                'id,catalog_name')
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        $merchant = M('tfb_cnpcgx_node_info')->where("id=" . $id)->find();
        $this->assign("merchant", $merchant);
        $this->assign("catalogInfo", $catalogInfo);
        $this->assign("parentInfo", $parentInfo);
        $this->display();
    }

    public function queryName() {
        $name = I('fieldValue', '', 'trim');
        if ($name) {
            
            $result = M('tfb_cnpcgx_node_info')->where(
                array(
                    'merchant_name' => $name))->select();
            if ($result) {
                $data = array(
                    trim(I('fieldId')), 
                    false);
            } else {
                $data = array(
                    trim(I('fieldId')), 
                    true);
            }
        }
        $this->ajaxReturn($data);
    }
    
    // 用户添加
    public function nodeadd() {
        if ($this->isPost()) {
            /* 信息校验 */
            $user_name = I('post.regemail', null, 'mysql_escape_string');
            $true_name = I('merchant_name', null, 'mysql_escape_string');
            
            /* 把商户信息存到广西石油的商户信息表里面 */
            $node_info = array(
                "merchant_name" => $true_name, 
                "merchant_short_name" => I('post.merchant_short_name'), 
                "catalog_id" => I('post.catalog_id'), 
                "parent_id" => I('post.parent_id'), 
                "hot_line_tel" => I('post.hot_line_tel'), 
                "contact_name" => I('post.contact_name'), 
                "mobile" => I('post.contact_phone'), 
                "image_link" => I('post.image_link'), 
                "description" => I('post.description'), 
                "login_name" => $user_name, 
                "add_time" => date("YmdHis"), 
                "province_code" => I('post.province_code'), 
                "city_code" => I('post.city_code'), 
                "town_code" => I('post.town_code'), 
                "address" => I('post.address'));
            $result_info = M('tfb_cnpcgx_node_info')->add($node_info);
            if (! $result_info) {
                $this->error('系统出错，添加失败');
            }
            $this->success('用户添加成功');
            
            exit();
        }
    }
    
    // 商户修改
    public function nodeedit() {
        $id = I('id', null, 'mysql_escape_string');
        if ($this->isPost()) {
            $node_info = array(
                "merchant_short_name" => I('post.merchant_short_name'), 
                "catalog_id" => I('post.catalog_id'), 
                "parent_id" => I('post.parent_id'), 
                "hot_line_tel" => I('post.hot_line_tel'), 
                "contact_name" => I('post.contact_name'), 
                "mobile" => I('post.contact_phone'), 
                "description" => I('post.description'), 
                "image_link" => I('post.image_link'), 
                "province_code" => I('post.province_code'), 
                "city_code" => I('post.city_code'), 
                "town_code" => I('post.town_code'), 
                "address" => I('post.address'));
            
            // 数据更新
            $where = array(
                'id' => $id);
            $result = M('tfb_cnpcgx_node_info')->where($where)->save($node_info);
            if ($result === false) {
                $this->error('系统出错，更新失败');
            }
            $this->success('用户更新成功');
            exit();
        }
    }

    public function change_status() {
        $id = I('id');
        $merchant = M('tfb_cnpcgx_node_info')->where("id=" . $id)->find();
        $status = I('status', null);
        $map = array(
            'id' => $id);
        $res = M("tfb_cnpcgx_node_info")->where($map)->save(
            array(
                'show_status' => $status));
        if ($res === false) {
            $this->ajaxReturn(0, "操作失败！" . M()->_sql(), 0);
        } else {
            $this->ajaxReturn(1, "操作成功！", 1);
        }
    }

    public function delete() {
        $id = I('id');
        $merchant = M('tfb_cnpcgx_node_info')->where("id=" . $id)->find();
        $map = array(
            'id' => $id);
        if ($merchant['show_status'] == 1) {
            $this->ajaxReturn(0, "展示中的商户无法删除", 0);
        }
        $goods_count = M('tfb_cnpcgx_goods')->where("merchant_id=" . $id)->count();
        if ($goods_count > 0) {
            $this->ajaxReturn(0, "已添加产品的商户无法删除", 0);
        }
        $res = M("tfb_cnpcgx_node_info")->where($map)->save(
            array(
                'status' => 0));
        if ($res === false) {
            $this->ajaxReturn(0, "操作失败！" . M()->_sql(), 0);
        } else {
            $this->ajaxReturn(1, "操作成功！", 1);
        }
    }

    public function chg_order() {
        $id = I('id', null);
        $order = I('order', 0, 'intval');
        if (is_null($id) || is_null($order)) {
            $this->error('缺少必要参数');
        }

        $result = M()->table("tfb_cnpcgx_node_info m")->where(
            array(
                "m.id" => $id))->find();
        
        if (! $result) {
            $this->error('未找到该记录');
        }
        
        $flag = M('tfb_cnpcgx_node_info')->where(
            array(
                "id" => $id))->save(
            array(
                'order_sort' => $order));
        if ($flag === false) {
            $this->error('排序失败！请重试');
        }
        
        $this->success('更新成功');
    }

    public function catalog() {
        $queryList = M()->table("tfb_cnpcgx_catalog")
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $child_catalog = array();
        foreach ($queryList as $v) {
            $child_catalog[$v['id']] = M()->table("tfb_cnpcgx_catalog")
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        $this->assign("queryList", $queryList);
        $this->assign("child_catalog", $child_catalog);
        $this->display();
    }

    public function add_catalog() {
        $parent_id = I('parent_id');
        if (IS_POST) {
            $model = M('tfb_cnpcgx_catalog');
            $count = $model->where(
                "parent_id=" . $parent_id . " and catalog_name='" .
                     trim(I('catalog_name')) . "'")->count();
            if ($count > 0) {
                $this->error("分类名称重复！");
            }
            $data = array(
                'catalog_name' => trim(I('catalog_name')), 
                'parent_id' => $parent_id, 
                'addtime' => date('YmdHis'));
            $flag = $model->add($data);
            if ($flag === false) {
                $this->error('分类添加失败！');
            }
            
            $this->success('分类添加成功！');
            exit();
        }
        $this->assign("parent_id", $parent_id);
        $this->display();
    }

    public function edit_catalog() {
        $id = I('id');
        $catalog_info = M('tfb_cnpcgx_catalog')->where("id=" . (int) $id)->find();
        $parent_id = (int) I('parent_id');
        if (IS_POST) {
            $model = M('tfb_cnpcgx_catalog');
            $count = $model->where(
                "parent_id=" . $parent_id . " and catalog_name='" .
                     trim(I('catalog_name')) . "' and id <>" . $id)->count();
            if ($count > 0) {
                $this->error('分类名称重复！');
            }
            $data = array(
                'catalog_name' => I('catalog_name'));
            $flag = $model->where("id=" . (int) $id)->save($data);
            if ($flag === false) {
                $this->error('分类编辑失败！');
            }
            
            $this->success('分类编辑成功！');
            exit();
        }
        $this->assign("catalog_info", $catalog_info);
        $this->display();
    }

    public function delete_catalog() {
        // 数据库中增加新字段 is_delete
        $id = I('id');
        $data['is_delete'] = 1;
        $map = array(
            'id' => $id);
        $count = M('tfb_cnpcgx_node_info')->where(
            "catalog_id=" . $id . " or parent_id=" . $id)->count();
        if ($count > 0) {
            $this->ajaxReturn(0, "有商户正在使用该类，不能删除！", 0);
        }
        $count = M('tfb_cnpcgx_catalog')->where("parent_id = " . $id)->count();
        if ($count > 0) {
            $this->ajaxReturn(0, "该分类有二级分类，不能删除！", 0);
        }
        $res = M("tfb_cnpcgx_catalog")->where($map)->save($data);
        if ($res) {
            $this->ajaxReturn(1, "删除成功！", 1);
        } else {
            $this->ajaxReturn(0, "删除失败！", 0);
        }
    }

    public function AjaxCatalog() {
        $data = I('request.');
        $query_arr = $model = M('tfb_cnpcgx_catalog');
        $parent_id = $data['parent_id'];
        $field_str = '';
        $map = array(
            'parent_id' => (int) $parent_id);
        $field_str = 'id,catalog_name';
        $query_arr = $model->field($field_str)
            ->where($map)
            ->select() or $query_arr = array();
        // $this->ajaxReturn($query_arr, "查询成功", 0);
        echo json_encode($query_arr);
    }
}
