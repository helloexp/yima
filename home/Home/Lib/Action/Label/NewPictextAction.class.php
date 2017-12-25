<?php
// 新模板手机页
class NewPictextAction extends MyBaseAction {
    public $_authAccessMap = '*';
    public function _initialize() {

    }
    private $upload = "./Home/Upload/";

    public function index()
    {
        $id   = I('get.id');
        $type = I('get.type');
        if( empty( $id )) $this-> error('缺少必要参数');

        $data = M()-> table('`tbatch_channel` as a ')
                -> join('`trich_text_info` as b on a.`batch_id`=b.`marketing_id` ')
            -> where( "a.`id`={$id} AND b.`status`=1" )
            -> field( 'b.*' )
            -> find();

        if( empty( $data )) $this-> error('ID不存在');

        if( empty( $data['title'] ) || empty( $data['share_descript'] ) || empty( $data['cover_img'] )) $this-> error('页面未配置');

        if( empty( $data['draft_path'] ) && empty( $data['content_path'] ) ) $this-> error('页面未配置');

        $info = [
            'title'         => $data['title'],
            'share_descript'=> $data['share_descript'],
            'cover_img'     => $data['cover_img'],
            'loop'          => 0,
            'music'         => '',
        ];

        import('@.Vendor.DataStat');
        $opt = new DataStat( $id, $id);
        $opt-> recordSeq();

        if( !empty( $type ) && $type == 'draft' )
        {
            if( empty( $data['draft_path'] )) $this-> error('页面未配置');
            $content = file_get_contents( $this-> upload.$data['draft_path'] );
        }
        else
        {
            $content = file_get_contents( $this-> upload.$data['content_path'] );
        }

        //微信分享 start
        $wechatShareConfig = $this->getWechatShareConfig();
        $coverImg          = get_upload_url($info['cover_img']);
        $desc              = get_val( $info, 'share_descript' );
        $name              = get_val( $info, 'title' );
        $shareArr          = array(
            'config' => $wechatShareConfig,
            'link'   => U('index', ['id' => $this->id], '', '', true),
            'title'  => $name,
            'desc'   => $desc,
            'imgUrl' => $coverImg
        );
        //微信分享 end

        $this->assign('shareData', $shareArr);
        $this-> assign( 'info', json_encode( $info, JSON_UNESCAPED_UNICODE ));
        $this-> assign( 'content', stripslashes( $content ));
        $this-> display();
    }
}