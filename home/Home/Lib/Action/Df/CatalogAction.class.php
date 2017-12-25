<?php

class CatalogAction extends BaseAction {
    
    // public $_authAccessMap = '*';
    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M('tfb_df_pointshop_catalog')->where(
            array(
                'node_id' => $this->node_id))->count(); // 查询满足要求的总记录数
        
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $classifyInfo = M('tfb_df_pointshop_catalog')->where(
            array(
                'node_id' => $this->node_id))
            ->order('sort')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $m_id = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => '1001'))->getField('id');
        $this->assign('classifyInfo', $classifyInfo);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('m_id', $m_id);
        $this->display('Catalog/index');
    }

    public function add() {
        $name = I('post.name', null);
        $sort = I('post.sort', null);
        if (! $name)
            $this->error('数据错误', 
                array(
                    '返回' => 'javascript:history.go(-1);'));
        
        $count = M('tfb_df_pointshop_catalog')->where(
            array(
                'node_id' => $this->node_id, 
                'class_name' => $name))->count();
        if ($count > 0) {
            $this->error('分组名称已存在', 
                array(
                    '返回' => 'javascript:history.go(-1);'));
        }
        
        $result = M('tfb_df_pointshop_catalog')->add(
            array(
                'node_id' => $this->node_id, 
                'class_name' => $name, 
                'add_time' => date('YmdHis'), 
                'sort' => $sort));
        if ($result === false)
            $this->error('分组添加失败', 
                array(
                    '返回' => 'javascript:history.go(-1);'));
        redirect(U('index'));
    }

    public function edit() {
        $name = I('post.name', null);
        $sort = I('post.sort', null);
        $id = I('post.id', null);
        if (! $id || ! $name)
            $this->error('数据错误', 
                array(
                    '返回' => 'javascript:history.go(-1);'));
        
        $count = M('tfb_df_pointshop_catalog')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $id))->count();
        if ($count <= 0) {
            $this->error('编辑的分组不存在', 
                array(
                    '返回' => 'javascript:history.go(-1);'));
        }
        
        $count = M('tfb_df_pointshop_catalog')->where(
            array(
                'node_id' => $this->node_id, 
                'class_name' => $name, 
                'id' => array(
                    'neq', 
                    $id)))->count();
        if ($count > 0) {
            $this->error('分组名称已存在', 
                array(
                    '返回' => 'javascript:history.go(-1);'));
        }
        
        $result = M('tfb_df_pointshop_catalog')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $id))->save(
            array(
                'class_name' => $name, 
                'sort' => $sort));
        if ($result === false)
            $this->error('分组编辑失败', 
                array(
                    '返回' => 'javascript:history.go(-1);'));
        redirect(U('index'));
    }
    
    // 删除验证
    public function del() {
        $id = I('get.id', null);
        if (! $id)
            $this->ajaxReturn(0, '数据错误', 0);
            // 判断是否存在
        $count = M('tfb_df_pointshop_catalog')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $id))->count();
        if ($count <= 0) {
            $this->ajaxReturn(0, '删除的分组不存在', 0);
        }
        // 判断是否存在商品
        $count = M('tfb_df_pointshop_goods_ex')->where(
            array(
                'node_id' => $this->node_id, 
                'ecshop_classify' => $id))->count();
        if ($count > 0) {
            $status = M()->table("tfb_df_pointshop_goods_ex a")->field('b.status')
                ->where(
                array(
                    'a.node_id' => $this->node_id, 
                    'a.ecshop_classify' => $id, 
                    'b.status' => 0))
                ->join('tbatch_info b on a.b_id=b.id')
                ->select();
            if ($status) {
                $this->ajaxReturn(0, '分组下存在有效商品，不可删除！', 0);
            } else {
                $this->ajaxReturn(1, '是否删除此分组？', 1);
            }
        } else {
            $this->ajaxReturn(1, '是否删除此分组？', 1);
        }
    }
    
    // 删除
    public function delClass() {
        $id = I('get.id', null);
        $result = M('tfb_df_pointshop_catalog')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $id))->delete();
        if ($result === false) {
            $this->ajaxReturn(0, '删除失败', 0);
        }
        $this->ajaxReturn(1, '删除成功', 1);
    }
}
