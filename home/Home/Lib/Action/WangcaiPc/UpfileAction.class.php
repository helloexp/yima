<?php
// 文件上传
class UpfileAction extends BaseAction {

    public $errormsg = '';

    public $imgurl = '';

    public $md5 = '';

    public $maxSize = '';

    public $allowExts = '';

    public function _initialize() {
        parent::_initialize();
        $this->_checkLogin();
    }

    public function index() {
        $this->maxSize = 1024 * 1024 / 10;
        $this->allowExts = array(
            'csv');
        
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = $this->maxSize;
        $upload->allowExts = $this->allowExts;
        $upload->savePath = APP_PATH . '/Upload/import/'; // 设置附件
        
        $upload->saveRule = time() . sprintf('%04s', mt_rand(0, 1000));
        
        if (! $upload->upload()) { // 上传错误提示错误信息
            $this->errormsg = $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            
            $info = $upload->getUploadFileInfo();
            
            $this->md5 = $info[0]['hash'];
            $m_ = M('tbatch_import');
            $query = $m_->where(
                array(
                    'file_md5' => $this->md5))->find();
            if ($query) {
                $this->errormsg = '已上传过同样的文件，为了防止您提交错误的文件，请改动后再重新上传';
                @unlink(APP_PATH . '/Upload/import/' . $info[0]['savename']);
                $arr = array(
                    'msg' => '-1',  // 通信是否成功
                    'error' => $this->errormsg); // 返回错误
                
                echo json_encode($arr);
                exit();
            }
            // 行数是否大于1000
            $handle = fopen(APP_PATH . '/Upload/import/' . $info[0]['savename'], 
                'r');
            $sum_arr = array();
            $row = 0;
            while ($file = fgetcsv($handle, 5000, ",")) {
                $num = count($file);
                if ($num > 1) {
                    $this->errormsg = '文件内容格式错误！';
                    fclose($handle);
                    @unlink(APP_PATH . '/Upload/import/' . $info[0]['savename']);
                    $arr = array(
                        'msg' => '-1',  // 通信是否成功
                        'error' => $this->errormsg); // 返回错误
                    
                    echo json_encode($arr);
                    exit();
                }
                $row ++;
                for ($c = 0; $c < $num; $c ++) {
                    $sum_arr[] = $file[$c];
                    if (! is_numeric($file[$c]) || strlen($file[$c]) != '11') {
                        $this->errormsg = '第' . $row . '行手机号错误:';
                        fclose($handle);
                        @unlink(
                            APP_PATH . '/Upload/import/' . $info[0]['savename']);
                        $arr = array(
                            'msg' => '-1',  // 通信是否成功
                            'error' => $this->errormsg); // 返回错误
                        
                        echo json_encode($arr);
                        exit();
                    }
                }
            }
            fclose($handle);
            if ($row > 1000) {
                @unlink(APP_PATH . '/Upload/import/' . $info[0]['savename']);
                $this->errormsg = '不能超过1000行！';
                $arr = array(
                    'msg' => '-1',  // 通信是否成功
                    'error' => $this->errormsg); // 返回错误
                
                echo json_encode($arr);
                exit();
            }
            $this->imgurl = $info[0]['savename'] . '-' . $this->md5;
        }
        
        $arr = array(
            'msg' => '0000',  // 通信是否成功
            'error' => $this->errormsg,  // 返回错误
            'imgurl' => $this->imgurl); // 返回图片名
        
        echo json_encode($arr);
        exit();
    }
}