<?php

/**
 * 图片上传工具 @auther:tr
 */
class UploadAction extends Action {

    public $UPLOAD_DIR;

    public $_authAccessMap = '*';
    private $node_id;
    public function _initialize() {
        $nodeId = I('userid');
        if ($nodeId) {
            $_SESSION['userSessInfo']['node_id'] = $nodeId;
        }
        if(isset($_SESSION['Fj114']['node_id'])){
            $this->node_id = $_SESSION['Fj114']['node_id'];
        }elseif(isset($_SESSION['userSessInfo']['node_id'])){
            $this->node_id = $_SESSION['userSessInfo']['node_id'];
        }else{
            $user = D('UserSess', 'Service');
            if (I('IS_AJAX', 0, 'intval') == 1) {
                $data = array(
                    'info' => "您尚未登录或登录已超时：" . $user->getErrorMsg(), 
                    'status' => 1, 
                    'rcode' => '900',
                    'url' => array('请立即登录' => 'javascript:openlogin();')
                    );
                $this->ajaxReturn($data, 'json');
            } else {
                $this->error("您尚未登录或登录已超时：" . $user->getErrorMsg(), 
                    array('请立即登录' => 'javascript:openlogin();'), 
                    array('rcode' => '900'));
            }
        }
        $this->UPLOAD_DIR = C('UPLOAD');
        $img_tpl = $this->UPLOAD_DIR;
        $this->prefix = "thumb_";
        $this->assign("img_tpl", $img_tpl);
    }

    public function index() {
        $targetWidth = I('ratioX'); // 比例
        $targetHeight = I('ratioY'); // 需要的高度
        $callback = I('callback'); // 回调函数名
        $suggestX = I('suggestX'); // 建议宽度
        $suggestY = I('suggestY'); // 建议高度
                                   // $oldImg = I('origin_img'); //原先保存的图片（绝对路径）
        $resizeFlag = I('resizeflag', 'false'); // 是否强允许缩放
        $menuType = I('menuType'); // 是否强允许缩放
        
        $cropPresets = I('cropPresets');
        $notice = '';
        if ($cropPresets) {
            $cropPresetsArr = explode('x', $cropPresets);
            $cropWidth = $cropPresetsArr[0] * 1;
            $cropHeight = $cropPresetsArr[1] * 1;
            $cropPresets = $cropWidth . 'x' . $cropHeight;
            $notice = '建议上传图片比例为' . $cropPresets . ',如果图片不符合规格，请使用"剪裁"功能';
        }
        $this->assign('notice', $notice);
//        $this->assign('thumb', get_val($_REQUEST,'thumb',''));
        $uploadUrl = U('ImgResize/Upload/uploadFile', '', '', '', true);
        // $uploadUrl = I('uploadUrl',$upLoadUrl,'urldecode');//绝对路径);
        // echo $uploadUrl;
        $_globalJs = array(
            'width' => $targetWidth, 
            'height' => $targetHeight, 
            'callback' => $callback, 
            'resizeFlag' => $resizeFlag, 
            'uploadUrl' => $uploadUrl, 
            'cropPresets' => $cropPresets, 
            'menuType' => $menuType);
        
        // 计算图片列表
        $where = array(
            'node_id' => $this->node_id, 
            'attachment_type' => 0, 
            'type' => '0');
        // 分页
        $p = I('p', 1, 'intval');
        $listRows = 21;
        $firstRow = ($p - 1) * $listRows;
        $queryList = M('tnode_attachment')->where($where)
            ->order("id desc")
            ->field('id, attachment_path, attachment_width, attachment_height, attachment_name')
            ->limit($firstRow, $listRows)
            ->select() or $queryList = array();
        foreach ($queryList as &$v) {
            $v['img_url'] = get_upload_url($v['attachment_path']);
            $v['smallsrc'] = get_upload_url($v['attachment_path'], 
                '!100x100.jpg');
        }
        unset($v);
        $node_images = $queryList;
        $this->assign('_globalJs', json_encode($_globalJs));
        $this->assign('node_images', $node_images);

        //添加 系统图片 start
        $attachment_category = I('attachment_category', 1);
        $where               = array(
                'node_id'             => '00001111',
                'attachment_type'     => 0,
                'type'                => '1',
                'attachment_category' => $attachment_category,
        );
        // 分页
        $p        = I('p', 1, 'intval');
        $listRows = 21;
        $firstRow = ($p - 1) * $listRows;
        $sysImgList = M('tnode_attachment')->where($where)->order("id desc")->field(
                'id, attachment_path, attachment_width, attachment_height, attachment_name'
        )->limit($firstRow, $listRows)->select() or $sysImgList = array();
        foreach ($queryList as &$v) {
            $v['img_url']  = get_upload_url($v['attachment_path']);
            $v['smallsrc'] = get_upload_url(
                    $v['attachment_path'],
                    '!100x100.jpg'
            );
        }
        $this->assign('attachment_category', $attachment_category);
        $this->assign('sysImgList', $sysImgList);
        //添加 系统图片 end

        $this->display();
    }

    /**
     * 删除已经上传过的文件。
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     */
    public function deleteUploadFile()
    {
        $id = I('request.id');
        $r = false;
        if ($id) {
            $where = ['node_id' => $this->node_id,
                      'attachment_type' => 0,
                      'type' => '0','id' => $id];
            $r     = M('tnode_attachment')->where($where)->delete();
        }
        if ($r) {
            $this->ajaxReturn(
                    array(
                            'id' => $id,
                            'sql' => M('tnode_attachment')->_sql(),
                    ),
                    '删除成功！',
                    1
            );
        } else {
            $this->ajaxReturn(
                    array(
                            'id' => $id,
                            'sql' => M('tnode_attachment')->_sql(),
                    ),
                    '删除失败！',
                    0
            );
        }
    }

    public function upload() {
        // 如果是表单模式
        if ($_FILES) {
            $upfile = '';
        } else {
            $upfile = file_get_contents('input://');
        }
    }

    public function uploadFile() {
        import('@.ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1024 * 5; // 设置附件上传大小 5兆
        $upload->getImageInfo = true; // 获取图片信息
        $upload->allowExts = array(
            'jpg', 
            'gif', 
            'png', 
            'jpeg'); // 设置附件上传类型
        $s = getimagesize($_FILES['upload_file']['tmp_name']);
        $upload->savePath = $this->UPLOAD_DIR; // 设置附件上传目录
        $upload->autoSub = true; // 自动子目录
        $upload->subType = 'custom'; // 自动子目录
        $upload->subDir = $this->node_id . '/' . date('Y/m/d/'); // 自动子目录
                                                                 // 生成缩图
        $upload->thumb = true;
        $upload->thumbExt = 'jpg';
        $upload->thumbPrefix = '';
        $upload->thumbSuffix = '!100x100';
        $upload->thumbMaxWidth = 100; // 缩略图最大宽度
        $upload->thumbMaxHeight = 100; // 缩略图最大高度
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if ($info)
                $info = $info[0];
        }
        if ($this->errormsg) { // 上传错误提示错误信息
            exit(
                json_encode(
                    array(
                        'msg' => $this->errormsg, 
                        'code' => - 1)));
        }
        // 上传成功
        // todo 记录到附件表中
        log_write(print_r($info, true));
        $data = array(
            'node_id' => $this->node_id, 
            'attachment_name' => $info['name'], 
            'attachment_type' => 0, 
            'attachment_size' => $info['size'], 
            'attachment_height' => $info['imageinfo']['height'], 
            'attachment_width' => $info['imageinfo']['width'], 
            'attachment_path' => $info['savename'], 
            'add_time' => date('YmdHis'));
        $result = M('tnode_attachment')->add($data);
        exit(
            json_encode(
                array(
                    'msg' => "success", 
                    'code' => 0, 
                    'data' => array(
                        'fileId' => $result, 
                        'name' => $info['name'], 
                        'src' => get_upload_url($info['savename']), 
                        'savename' => $info['savename'], 
                        'smallsrc' => get_upload_url($info['savename'], '', 
                            '!100x100.jpg'), 
                        'width' => $info['imageinfo']['width'], 
                        'height' => $info['imageinfo']['height']))));
    }

    public function musicList() {
        $callback = I('callback'); // 回调函数名
        $uploadUrl = U('ImgResize/Upload/uploadFile', '', '', '', true);
        $_globalJs = array(
            'callback' => $callback, 
            'uploadUrl' => $uploadUrl);
        // 计算图片列表
        $where = array(
            'node_id' => $this->node_id, 
            'attachment_type' => 1);
        // 分页
        $p = I('p', 1, 'intval');
        $listRows = 21;
        $firstRow = ($p - 1) * $listRows;
        $queryList = M('tnode_attachment')->where($where)
            ->order("id desc")
            ->limit($firstRow, $listRows)
            ->select() or $queryList = array();
        foreach ($queryList as &$v) {
            $v['music_url'] = get_upload_url($v['attachment_path']);
        }
        unset($v);
        $this->assign('_globalJs', json_encode($_globalJs));
        $this->assign('queryList', $queryList);
        $this->display();
    }

    public function uploadMusic() {
        import('@.ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1024 * 2; // 设置附件上传大小 2兆
        $upload->allowExts = array(
            'mp3'); // 设置附件上传类型
        $upload->savePath = $this->UPLOAD_DIR; // 设置附件上传目录
        $upload->autoSub = true; // 自动子目录
        $upload->subType = 'custom'; // 自动子目录
        $upload->subDir = $this->node_id . '/' . date('Y/m/d/'); // 自动子目录
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if ($info)
                $info = $info[0];
        }
        if ($this->errormsg) { // 上传错误提示错误信息
            exit(
                json_encode(
                    array(
                        'msg' => $this->errormsg, 
                        'code' => - 1)));
        }
        // 上传成功
        // todo 记录到附件表中
        log_write(print_r($info, true));
        $data = array(
            'node_id' => $this->node_id, 
            'attachment_name' => $info['name'], 
            'attachment_type' => 1, 
            'attachment_size' => $info['size'], 
            'attachment_path' => $info['savename'], 
            'add_time' => date('YmdHis'));
        $result = M('tnode_attachment')->add($data);
        exit(
            json_encode(
                array(
                    'msg' => "success", 
                    'code' => 0, 
                    'data' => array(
                        'fileId' => $result, 
                        'name' => $info['name'], 
                        'src' => get_upload_url($info['savename'])))));
    }

    public function video() {
        $this->display();
    }
}
