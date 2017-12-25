<?php

// 广西石油首页
class StoreAction extends CnpcGXAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
    }

    public function label() {
        $map = array();
        import("ORG.Util.Page");
        $count = M()->table("tfb_cnpcgx_storelabel")
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        $page = $p->show();
        $list = M()->table("tfb_cnpcgx_storelabel a")
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('a.id desc')
            ->field("a.*")
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function add_label() {
        if (IS_POST) {
            $model = M('tfb_cnpcgx_storelabel');
            $count = $model->where("label='" . trim(I('label')) . "'")->count();
            if ($count > 0) {
                $this->error("标签名称重复！");
            }
            $data = array(
                'label' => trim(I('label')), 
                'add_time' => date('YmdHis'));
            $flag = $model->add($data);
            if ($flag === false) {
                $this->error('标签添加失败！');
            }
            
            $this->success('标签添加成功！');
            exit();
        }
        $this->display();
    }

    public function edit_label() {
        $id = I('id');
        $info = M('tfb_cnpcgx_storelabel')->where("id=" . (int) $id)->find();
        if (IS_POST) {
            $model = M('tfb_cnpcgx_storelabel');
            $count = $model->where(
                "label='" . trim(I('label')) . "' and id <>" . $id)->count();
            if ($count > 0) {
                $this->error('标签名称重复！');
            }
            $data = array(
                'label' => trim(I('label')));
            $flag = $model->where("id=" . (int) $id)->save($data);
            if ($flag === false) {
                $this->error('标签编辑失败！');
            }
            
            $this->success('标签编辑成功！');
            exit();
        }
        $this->assign("info", $info);
        $this->display();
    }

    public function delete_label() {
        $id = I('id');
        $map = array(
            'id' => $id);
        $count = M('tfb_cnpcgx_store_label')->where("label_id=" . $id)->count();
        if ($count > 0) {
            $this->ajaxReturn(0, "有店铺正在使用标签，不能删除！", 0);
        } else {
            $del = M('tfb_cnpcgx_storelabel')->where($map)->delete(); /* 删除标签 */
            $del_p = M('tfb_cnpcgx_store_label')->where("label_id=" . $id)->delete(); /*
                                                                                       * 删除对应关系
                                                                                       */
            if (false === $del || $del_p === false) {
                $this->ajaxReturn(0, "删除失败！", 0);
            } else {
                $this->ajaxReturn(1, "删除成功！", 1);
            }
        }
    }

    public function set_label() {
        $store_id = I("store_id");
        $list = M()->table("tfb_cnpcgx_storelabel a")
            ->order('a.id desc')
            ->field("a.*")
            ->select();
        if (IS_POST) {
            $label = I('label');
            $model = M('tfb_cnpcgx_store_label');
            $del = $model->where("store_id=" . $store_id)->delete();
            foreach ($label as $v) {
                $data = array(
                    'label_id' => $v, 
                    'store_id' => $store_id);
                $flag = $model->add($data);
                if ($flag === false) {
                    $this->error('设置标签编辑失败！');
                }
            }
            $this->ajaxReturn(1, "设置标签成功！", 1);
        }
        $select_id = M('tfb_cnpcgx_store_label')->where("store_id=" . $store_id)
            ->field("label_id")
            ->select();
        $ids = array();
        foreach ($select_id as $vo) {
            $ids[] = $vo['label_id'];
        }
        $this->assign('list', $list);
        $this->assign("store_id", $store_id);
        $this->assign('select_id', $ids);
        $this->display();
    }
}
