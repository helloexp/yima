<?php

/**
 * 营帐行业类型变更同步旺财: 营帐定时会将所有旺号的行业类型ftp到指定目录,旺财抓取ftp数据,进行全量更新 根据 旺号 更新 tnode_info表
 * trade_type行业类型
 *
 * @author bao
 */
class YzIndustryAction extends Action {

    protected $ftpIp = '222.44.51.34';

    protected $ftpUser = 'mdlftp';

    protected $ftpPwd = 'mdlftp!1';

    protected $ftpFileName;
    // 要下载文件的文件名
    protected $savePath = '';
    // 保存目录
    public function _initialize() {
        set_time_limit(0);
        $this->savePath = C('UPLOAD') . 'yz_indeustry/';
        $this->ftpFileName = 'dw_client_industry_' . date('Ymd') . '.txt';
        log_write("YzIndustryMessage:营帐行业同步开始");
        // 检查同步文件夹是否存在,不存在创建
        if (! is_dir($this->savePath)) {
            if (! mkdir($this->savePath)) {
                log_write("YzIndustryMessage:{$this->savePath}目录不存在");
                exit();
            }
        }
    }

    /**
     * 开始同步
     */
    public function index() {
        // 本地文件是否存在,不存在下载
        if (! is_file($this->savePath . $this->ftpFileName)) {
            $this->ftpDownFile();
        }
        // 处理下载文件,更新数据
        $dataCount = substr_count(
            file_get_contents($this->savePath . $this->ftpFileName), ','); // 计算更新数据数量
        $handle = fopen($this->savePath . $this->ftpFileName, "r");
        $sql = "UPDATE tnode_info SET trade_type= CASE client_id";
        $row = 0;
        $inArr = array(); // sql语句in条件数组
        if ($handle) {
            while (($data = fgets($handle)) !== false) {
                $row ++;
                $data = trim($data);
                $sql .= " WHEN " . substr($data, 0, strpos($data, ',')) .
                     " THEN " . substr(strstr($data, ','), 1);
                $inArr[] = substr($data, 0, strpos($data, ','));
                if ($row % 100 == 0 || $row == $dataCount) { // 每100条执行一次
                    $sql .= " END WHERE client_id IN (" . implode(',', $inArr) .
                         ")";
                    $resutl = M()->query($sql);
                    if ($resutl === false) {
                        log_write(
                            "YzIndustryMessage:数据更新失败,错误:" . M()->getDbError());
                        exit();
                    }
                    // 重置
                    $sql = "UPDATE tnode_info SET trade_type= CASE client_id";
                    $inArr = array();
                }
            }
            log_write("YzIndustryMessage:更新结束,更新数量:{$row}");
            exit();
        } else {
            log_write(
                "YzIndustryMessage:" . $this->savePath . $this->ftpFileName .
                     '文件打开失败');
            exit();
        }
        fclose($handle);
    }

    /**
     * ftp下载文件
     */
    public function ftpDownFile() {
        // 连接ftp
        if (function_exists('ftp_connect')) {
            $conn = ftp_connect($this->ftpIp);
            if (! $conn) {
                log_write("YzIndustryMessage:ftp服务器无法连接");
                exit();
            }
        } else {
            log_write("YzIndustryMessage:ftp函数不可用");
            exit();
        }
        // 登陆ftp
        if (! ftp_login($conn, $this->ftpUser, $this->ftpPwd)) {
            log_write("YzIndustryMessage:ftp登陆失败");
            exit();
        }
        ftp_pasv($conn, TRUE);
        // 下载目标文件
        if (! ftp_get($conn, $this->savePath . $this->ftpFileName, 
            $this->ftpFileName, FTP_ASCII)) {
            log_write("YzIndustryMessage:文件下载失败或未找到下载文件");
            exit();
        }
        ftp_close($conn);
    }

    /**
     * 将测试tnode_info表导入txt(测试用);
     */
    public function exportTnodeTxt() {
        // 创建文件
        $testFile = fopen($this->savePath . $this->ftpFileName, "w") or
             die("文件创建失败");
        // 获取tnode_info数据
        $listId = M('tnode_info')->order("id desc")
            ->limit(1)
            ->getField('id');
        for ($i = 1; $i <= $listId; $i ++) {
            $info = M('tnode_info')->field('client_id,trade_type')
                ->where("id='{$i}'")
                ->find();
            if (! empty($info)) {
                if (empty($info['client_id']) || is_null($info['trade_type'])) {
                    continue;
                }
                $txt = "{$info['client_id']},{$info['trade_type']}\n";
                fwrite($testFile, $txt);
            }
        }
        fclose($testFile);
        echo '写入完成';
    }
}