<?php 
/**
 * 多乐互动小工具
 */
class ToolAction extends MarketBaseAction{

	public function _initialize() {
        parent::_initialize();
    }

    /**
     * [beforeCheckAuth 提前校验权限]
     */
    public function beforeCheckAuth(){
    	// 跳过系统权限校验
    	$this->_authAccessMap = '*';
    }

	public function website(){
        $this->assign('MicroWeb',self::getMicroWeb());
    	$this->display();
    }
    public function poster(){
        $this->assign('Poster',self::getPoster());
    	$this->display();
    }
    public function pictext(){
        $requestInfo = I('request.');
        $this->assign('PicText',self::getPicText($requestInfo));
        $this->assign('show_status_arr',array('1'=>'停用','2'=>'启用'));
    	$this->display();
    }

    /*************以下为私有***************/

    private function getMicroWeb(){
        $map = ['node_id'=>$this->nodeId,'batch_type'=>'13'];
        $MicroWeb = M('tmarketing_info')->where($map)->find();
        if($MicroWeb){
            $bc_id = self::getBatchChannelId('MicroWeb',$MicroWeb['id']);
            $url = U('Label/Label/index',['id'=>$bc_id],'', '', true);
            $ret = D('RemoteRequest','Service')->getSinaShortUrl($url);
            if($ret['Status']['StatusCode'] == '0000')
            {
                $shortUrl = $ret['ShortUrl'];
            }
            return array_merge($MicroWeb,['bc_id'=>$bc_id,'shorturl'=>get_val($shortUrl)]);
        }
        return array();
    }
    private function getPoster(){
        $map = ['node_id'=>$this->nodeId,'batch_type'=>'37'];
        $count = M('tmarketing_info')->where($map)->count();
        // 分页
        import('ORG.Util.Page'); 
        $Page = new Page($count, 8);
        $show = $Page->show();

        $Poster = M('tmarketing_info')->where($map)->order("id desc")
                ->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if(!empty($Poster)){
            foreach ($Poster as $k => $v) {
                $memo = json_decode($v['memo'],true);
                if (isset($memo['cover_img'])) {
                    $Poster[$k]['cover_img'] = get_upload_url(
                        $memo['cover_img']);
                } else {
                    $Poster[$k]['cover_img'] = C('TMPL_PARSE_STRING.__PUBLIC__') .
                         '/Image/poster/poster.png';
                }
            }
        }
        $this->assign('page',$show);
        return $Poster;
    }
    private function getPicText($requestInfo){
        $map = ['m.node_id'=>$this->nodeId,'m.batch_type'=>'19'];
        // 图文编辑名称
        $picTextName = get_val($requestInfo,'pic_name');
        if($picTextName)
        {
            $map['m.name'] = ['like','%'.$picTextName.'%'];
        }
        // 创建日期
        $startTime  = get_val($requestInfo,'start_time');
        $endTime    = get_val($requestInfo,'end_time');
        if($startTime)
        {
            $map['m.add_time'] = ['EGT',$startTime.'000000'];
        }
        if($endTime)
        {
            $map['m.add_time'] = ['ELT',$endTime.'235959'];
        }
        // 状态
        $status = get_val($requestInfo,'status');
        if($status)
        {
            $map['m.status'] = $status;
        }
        $count = M('tmarketing_info m')->where($map)->count();
        // 分页
        import('ORG.Util.Page'); 
        $Page = new Page($count, 8);
        $show = $Page->show();
        $this->assign('page',$show);
        $channelId = parent::getDefaultCId();
        $joinPart = $channelId ? " AND c.channel_id=".$channelId : "";
        $PicText = M('tmarketing_info m')->field('m.*,c.id AS bc_id')
                ->join('tbatch_channel c ON c.batch_id=m.id'.$joinPart)
                ->where($map)->order("m.id desc")
                ->limit($Page->firstRow . ',' . $Page->listRows)->select();
        return $PicText;
    }

    private function getChannelId($typename){
        $map  = array();
        $data = array();
        switch ($typename) {
            case 'MicroWeb':
                {
                    $map = array(
                        'sns_type' => '43',
                        'type'     => '4'
                        );
                    $data = array(
                        'name'       => '微官网渠道',
                        'status'     => '1',
                        'start_time' => date('YmdHis'), 
                        'end_time'   => date('YmdHis', strtotime("+1 year")), 
                        'add_time'   => date('YmdHis'),
                        );
                    $error = '添加默认微官网渠道号失败';
                }
                break;
            default:
                // code...
                break;
        }
        $map['node_id'] = $this->nodeId;
        $channelInfo = M('tchannel')->where($map)->find();
        $oneSelect = function($select,$errorInfo){
            if(!$select){
                $this->error($errorInfo);
            }
            return $select;
        };
        if(empty($channelInfo)){
            return $oneSelect(M('tchannel')->add(array_merge($map,$data)),$error);
        }else{
            return $channelInfo['id'];
        }
        
    }

    private function getBatchChannelId($typename,$mid){
        $map  = array();
        $data = array();
        switch ($typename) {
            case 'MicroWeb':
                {
                    $map = array(
                        'batch_type' => '13',
                        );
                    $data = array(
                        'status'     => '1',
                        'start_time' => date('YmdHis'), 
                        'end_time'   => date('YmdHis', strtotime("+1 year")), 
                        'add_time'   => date('YmdHis'),
                        );
                    $error = '微官网发布到默认渠道失败';
                }
                break;
            default:
                // code...
                break;
        }
        $map['node_id']    = $this->nodeId;
        $map['batch_id']   = $mid;
        $map['channel_id'] = self::getChannelId($typename);
        $bChannelInfo = M('tbatch_channel')->where($map)->find();
        $oneSelect = function($select,$errorInfo){
            if(!$select){
                $this->error($errorInfo);
            }
            return $select;
        };
        if(empty($bChannelInfo)){
            return $oneSelect(M('tbatch_channel')->add(array_merge($map,$data)),$error);
        }else{
            return $bChannelInfo['id'];
        }

    }
}














