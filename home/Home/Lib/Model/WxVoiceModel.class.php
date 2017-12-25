<?php

class WxVoiceModel extends BaseModel {

    protected $tableName = 'twx_voice';

    public function __construct() {
        parent::__construct();
        import("@.Vendor.CommonConst");
    }

    /**
     * 添加记录声音的表
     *
     * @param unknown $nodeId
     * @param unknown $mId
     * @param unknown $path
     * @param unknown $fromOpenId
     * @param unknown $toOpenId
     * @param unknown $time 录制的语音时间长度
     */
    public function addRecord($nodeId, $mId, $path, $fromOpenId, $toOpenId, 
        $time) {
        $data = array(
            'node_id' => $nodeId, 
            'm_id' => $mId, 
            'path' => $path, 
            'from_wx_user_id' => $fromOpenId, 
            'to_wx_user_id' => $toOpenId, 
            'add_time' => date('YmdHis'), 
            'voice_length' => $time);
        $result = $this->add($data);
        if (! $result) {
            throw_exception('添加声音记录失败');
        }
    }

    public function getVoiceList($nodeId, $mId, $toOpenId, $currentOpenId, 
        $page = 1, $getTotalCount = false, $order = 'v.like_count desc'/*, $limit = ''*/) {
        $map = array(
            'node_id' => $nodeId, 
            'm_id' => $mId, 
            'to_wx_user_id' => $toOpenId);
        $result = $this->where($map)->select(false);
        $where = array(
            'r.open_id' => $currentOpenId, 
            'r.m_id' => $mId);
        // 每页条数(暂时前台做个假的分页 这里注释掉)
        // $page_count = 5;
        // // 初始行
        // $firstRow = $page_count * ($page - 1);
        // if (empty($limit)) {
        // $limit = $firstRow . ',' . $page_count;
        // }
        $t = M('twx_voice_trace')->alias('r')
            ->where($where)
            ->select(false);
        if ($getTotalCount == true) {
            $count = M()->table($t)
                ->alias('t')
                ->field(array(
                'v.id'))
                ->join('right join ' . $result . ' v on t.voice_id = v.id')
                ->join(
                'left join twx_wap_user u on u.openid = v.from_wx_user_id')
                ->count();
            return $count;
        }
        $record = M()->table($t)
            ->alias('t')
            ->field(
            array(
                'v.id', 
                'v.path', 
                'v.voice_length as length', 
                'v.like_count', 
                "case when t.listen_flag = '2' then '1' else '0' end as listened", 
                "t.click_flag as clicked", 
                'u.headimgurl', 
                'u.nickname'))
            ->join('right join ' . $result . ' v on t.voice_id = v.id')
            ->join('left join twx_wap_user u on u.openid = v.from_wx_user_id')
            ->
        // ->limit($limit)
        order($order)
            ->select(); // 跟按时间排一个样,小的在前面,第一个就是他最先录的那个
        log_write('record_sql' . M()->_sql());
        return $record;
    }

    public function hasOwnWall($nodeId, $mId, $currentOpenId) {
        $count = $this->where(
            array(
                'node_id' => $nodeId, 
                'm_id' => $mId, 
                'from_wx_user_id' => $currentOpenId, 
                'to_wx_user_id' => $currentOpenId))->count();
        return $count ? true : false;
    }

    /**
     * 转换amr成mp3
     *
     * @param string $from 'http://test.wangcaio2o.com/Home/Upload/.../xx.amr'
     * @param string $to './Home/Upload/.../xx.amr'
     * @return string './Home/Upload/.../xx.amr'或'./Home/Upload/.../xx.mp3'
     */
    public function transferToMp3($from) {
        $path = $from;
        $toPath = substr($path, 0, (strlen($path) - 3)) . 'mp3';
        $commond = 'ffmpeg -i ' . $path . ' -ab 8kbps -y ' . $toPath;
        $result = system($commond);
        // $file= base64_encode(base64_encode($from));
        // $back=file_get_contents("http://api.yizhancms.com/video/index.php?i=1&f=$file");
        // $result=(array)json_decode($back);
        // M('tdraft')->add(array('content' => $commond));
        @unlink($path);
        return $toPath;
    }

    public function updateLikeCount($data, $fromOpenId, $mId) {
        log_write('data:' . json_encode($data));
        foreach ($data as $v) {
            $click = (int) $v['clicknum'];
            $traceModel = M('twx_voice_trace');
            log_write(
                'click:' . $click . ',open_id:' . $fromOpenId . ',m_id:' . $mId);
            // 如果这个活动被这个用户点过赞了 就不能再点了
            $hasClicked = $traceModel->where(
                array(
                    'voice_id' => $v['voiceId'], 
                    'open_id' => $fromOpenId, 
                    'click_flag' => '2'))->find();
            if (! $hasClicked) {
                // trace表记录两种一个是有没有被听过,一个是有没有被点过赞(所以可能没有记录)
                $result = $traceModel->where(
                    array(
                        'm_id' => $mId, 
                        'open_id' => $fromOpenId, 
                        'voice_id' => $v['voiceId']))->find();
                if ($result) {
                    $traceModel->where(
                        array(
                            'm_id' => $mId, 
                            'open_id' => $fromOpenId, 
                            'voice_id' => $v['voiceId']))->save(
                        array(
                            'click_flag' => '2'));
                } else {
                    $insetRe = $traceModel->add(
                        array(
                            'm_id' => $mId, 
                            'open_id' => $fromOpenId, 
                            'voice_id' => $v['voiceId'], 
                            'click_flag' => '2'));
                }
                $re = M('twx_voice')->where(
                    array(
                        'id' => $v['voiceId']))->setInc('like_count', 1);
            }
        }
    }

    /**
     * 查看有没有自己给自己录音的那条
     *
     * @param unknown $openId
     * @param unknown $mId
     * @return Ambigous <mixed, boolean, NULL, multitype:, unknown, string>
     */
    public function getVoiceByToOpenId($openId, $mId) {
        $re = $this->where(
            array(
                'from_wx_user_id' => $openId, 
                'to_wx_user_id' => $openId, 
                'm_id' => $mId))->find();
        return $re;
    }

    public function getTopThree($nodeId, $mId, $toOpenId, $currentOpenId) {
        $map = array(
            'node_id' => $nodeId, 
            'm_id' => $mId, 
            'to_wx_user_id' => $toOpenId);
        $result = $this->where($map)->select(false);
        $where = array(
            'r.open_id' => $currentOpenId, 
            'r.listen_flag' => '2'); // 2表示听过
        
        $t = M('twx_voice_trace')->alias('r')
            ->where($where)
            ->select(false);
        $record = M()->table($t)
            ->alias('t')
            ->field(
            array(
                'v.id', 
                'v.path', 
                'v.voice_length as length', 
                'v.like_count', 
                "case when t.id is null then '0' else '1' end as listened", 
                'u.headimgurl', 
                'u.nickname'))
            ->join('right join ' . $result . ' v on t.voice_id = v.id')
            ->join('left join twx_wap_user u on u.openid = v.from_wx_user_id')
            ->order('v.like_count desc')
            ->limit('3')
            ->select(); // 跟按时间排一个样,小的在前面,第一个就是他最先录的那个
        log_write('topThree:' . json_encode($record));
        return $record;
    }
}