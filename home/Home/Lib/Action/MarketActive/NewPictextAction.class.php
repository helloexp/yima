<?php
/**
 * Created by PhpStorm.
 * author  wangpan
 * Date: 2016/5/12
 * Time: 14:07
 */
class NewPictextAction extends BaseAction{
    public $_authAccessMap = '*';
    public function _initialize() {
        parent::_initialize();
    }

    private $upload = "./Home/Upload/";
    private $channel_type = '6';
    private $channel_sns_type = '58';
    private $batch_type = '62';
    /**
     * 列表页
     */
    public function index()
    {
        $title     = I('post.key');
        $startTime = empty($_POST['start_time']) ? '' : date( 'YmdHis', strtotime(I('post.start_time')) );
        $endTime   = empty($_POST['end_time']) ? '' : date( 'YmdHis', strtotime(I('post.end_time')) );
        $status    = I('post.status');

        $where     = "a.`node_id`={$this-> node_id}";

        $data      = M('trich_text_info')
            -> where( $where )
            -> find();

        $show = 'on';

        if( empty( $data ))
            $show = 'off';
        if( !empty( $title ))
            $where .= " AND a.`title` LIKE '%".$title."%'";
        if( !empty( $startTime ))
            $where .= " AND a.`time` > {$startTime}";
        if( !empty( $endTime ))
            $where .= " AND a.`time` < {$endTime}";
        if( !empty( $status ))
            $where .= " AND a.`status` = {$status}";

        import('ORG.Util.Page');

        $count = M('trich_text_info')
            -> where( $where )
            -> count();

        $Page  = new Page( $count, 10 );
        $Page-> setConfig('theme',"%totalRow% %header% %nowPage%/%totalPage% 页 %upPage% %downPage% %first% %prePage% %linkPage% %nextPage% %end%");
        $pShow = $Page-> show();

        $Model = new Model(); // 实例化一个model对象 没有对应任何数据表
        $sql   = "  SELECT 
                      SUM(b.`click_count`) AS 'count',
                      a.* 
                    FROM
                      `trich_text_info` AS a 
                      INNER JOIN `tbatch_channel` AS b 
                        ON a.`marketing_id` = b.`batch_id` 
                    WHERE ". $where . " 
                    GROUP BY a.`marketing_id`
                    ORDER BY a.`id` DESC 
                    LIMIT {$Page->firstRow},{$Page->listRows}";
        $list = $Model-> query( $sql );

        $channelInfo = D('Channel')-> getChannelInfo(
            array(
                'node_id'  => $this-> node_id,
                'type'     => $this-> channel_type,
                'sns_type' => $this-> channel_sns_type,
            )
        );
        if (!$channelInfo) { // 不存在则添加渠道
            //不存在则新增
            $channelList = array(
                'name'        => '新版图文编辑',
                'type'        => $this-> channel_type,
                'sns_type'    => $this-> channel_sns_type,
                'status'      => '1',
                'start_time'  => date('YmdHis'),
                'end_time'    => date('YmdHis', strtotime("+3 year")),
                'add_time'    => date('YmdHis'),
                'click_count' => 0,
                'cj_count'    => 0,
                'send_count'  => 0,
                'node_id'     => $this->node_id,
            );

            $channelId = D('Channel')-> add($channelList);
            if ( !$channelId )
            {
                $this-> error('添加新版电子海报默认渠道号失败');
            }
        } else {
            $channelId = get_val( $channelInfo, 'id' );
        }
        if( empty( $list )) $list = '';
        $this-> assign( 'batch_type', $this-> batch_type );
        $this-> assign( 'show', $show );
        $this-> assign( 'list', $list );
        $this-> assign( 'page', $pShow );
        $this-> display();
    }

    /**
     * 编辑
     */
    public function add(){
        $id   = I('get.id');
        $info = M('trich_text_info')
            -> where( "`id`={$id} AND `node_id`={$this-> node_id}" )
            -> find();
        if( empty( $info )) $this-> error('这个模板不翼而飞');

        $temps = M('trich_text_temp')
            -> field( 'id, path' )
            -> where( "`node_id`={$this-> node_id}" )
            -> order( 'id DESC' )
            -> select();
        $data = [];

        foreach ( $temps as $k=> $v )
        {
            if( !empty( $v['path'] ))
            {
                $data[$k]['id'] = $v['id'];
                $json = stripslashes( file_get_contents( $this-> upload.$v['path'] ));
                $data[$k]['data'] = json_decode( $json, true );
            }
        }
        array_merge($data);

        if( !empty( $info['draft_path'] ))
        {
            $info['draft_path'] = stripslashes( file_get_contents( $this-> upload.$info['draft_path'] ));
        }
        elseif( !empty( $info['content_path'] ))
        {
            $info['content_path'] = stripslashes( file_get_contents( $this-> upload.$info['content_path'] ));
        }
        $this-> assign( 'temps', json_encode( $data, JSON_UNESCAPED_UNICODE ));
        $this-> assign( 'info', $info );
        $this->display();
    }

    /**
     * 保存草稿
     */
    public function draftSave()
    {
        $id   = I('post.id');
        $memo = htmlspecialchars_decode( I('post.memo'));
        if( empty( $id ) || empty( $memo )) $this->ajaxReturn(array('status' => '0', 'info' => '缺少必须参数'));

        $path     = "{$this-> node_id}/".date('Y').'/'.date('m').'/'.date('d').'/';
        $filename = "draft_text_info_{$id}.json";
        if( !is_dir( $this-> upload.$path ))
        {
            mkdir( $this-> upload.$path, 0777, true );
        }
        if( !file_put_contents( $this-> upload.$path.$filename, $memo )) $this-> ajaxReturn(array('status' => '0', 'info' => '文件写入失败'));

        $info = M('trich_text_info')
            -> where("`id`={$id} AND `node_id`={$this-> node_id}")
            -> find();

        $url = U('Label/NewPictext/index', array('id' => $info['batch_channel_id'], 'type'=> 'draft' ), '', '', true );

        try{
            M('trich_text_info')
                -> where("`id`={$id} AND `node_id`={$this-> node_id}")
                -> save(['draft_path'=> $path.$filename ]);
            $this-> ajaxReturn( array('status' => '1', 'url'=> $url, 'info' => '新版模板修改成功！'));
        }
        catch ( \Exception $e )
        {
            $this-> ajaxReturn(array('status' => '0', 'info' => '数据库写入失败'));
        }
    }

    /**
     * 保存正文
     */
    public function save()
    {
        $id   = I('post.id');
        $memo = htmlspecialchars_decode( I('post.memo'));
        if( empty( $id ) || empty( $memo )) $this->ajaxReturn(array('status' => '0', 'info' => '缺少必须参数'));

        $path     = "{$this-> node_id}/".date('Y').'/'.date('m').'/'.date('d').'/';
        $filename = "text_info_{$id}.json";
        if( !is_dir( $this-> upload.$path ))
        {
            mkdir( $this-> upload.$path, 0777, true );
        }
        if( !file_put_contents( $this-> upload.$path.$filename, $memo )) $this-> ajaxReturn(array('status' => '0', 'info' => '文件写入失败'));

        try{
            M('trich_text_info')
                -> where("`id`={$id} AND `node_id`={$this-> node_id}")
                -> save(['draft_path'=> '', 'content_path'=> $path.$filename ]);

            $this-> ajaxReturn( array('status' => '1', 'm_id'=> $id, 'info' => '新版模板修改成功！'));
        }
        catch ( \Exception $e )
        {
            $this->ajaxReturn(array('status' => '0', 'info' => '数据库写入失败'));
        }
    }

    /**
     * 保存基础信息
     */
    public function saveBasicInfo()
    {
        $textInfo = M('trich_text_info');
        $textInfo-> create();
        if( empty( $textInfo-> title ))  $this-> ajaxReturn([ 'code'=> 40001, 'data'=>'', 'msg'=>'缺少必要参数' ]);

        $textInfo-> node_id = $this-> node_id;
        if( empty( $textInfo-> id ))
        {
            try{
                M()->startTrans();

                //保存活动
                $data    = array(
                    'memo'       => '',
                    'name'       => $textInfo-> title,
                    'batch_type' => $this-> batch_type,
                    'status'     => 1,
                    'pay_status' => 0,
                    'node_id'    => $this-> node_id,
                    'add_time'   => date('YmdHis'),
                    'start_time' => date('YmdHis'),
                    'end_time'   => date('YmdHis', strtotime("+3 year"))
                );
                $batchId = D('MarketInfo')-> add($data);

                if( !$batchId ) M()-> rollback();

                //获取渠道id
                $channelInfo = D('Channel')-> getChannelInfo(
                    array(
                        'node_id'  => $this-> node_id,
                        'type'     => $this-> channel_type,
                        'sns_type' => $this-> channel_sns_type,
                    )
                );

                $channelId = get_val( $channelInfo, 'id' );

                //保存渠道与活动的关系
                $param = [
                    'batch_type'=> $this-> batch_type,
                    'batch_id'  => $batchId,
                    'channel_id'=> $channelId,
                    'add_time'  => date('YmdHis'),
                    'node_id'   => $this-> node_id,
                    'status'    => 1,
                ];
                $batchChannelId = M('tbatch_channel')-> add($param);

                if( !$batchChannelId ) M()-> rollback();

                //保存图文编辑
                $textInfo-> batch_channel_id = $batchChannelId;
                $textInfo-> marketing_id     = $batchId;
                $textInfo-> time             = date('YmdHis');
                $id = $textInfo-> add();

                if( !$id ) M()-> rollback();
                else M()-> commit();
            }
            catch ( \Exception $e )
            {
                M()-> rollback();
                $this-> ajaxReturn(array('status' => '0', 'info' => '数据库写入失败'));
            }

        }
        else
        {
            $textInfo-> save();
            $id = $textInfo-> id;
        }

        $this-> ajaxReturn([ 'code'=> 0, 'data'=>['id'=> $id ], 'msg'=>'' ]);
    }

    /**
     * 删除正文
     */
    public function delete()
    {
        $id = I('post.id');
        if( empty( $id )) $this-> ajaxReturn([ 'code'=> 40001, 'data'=>'', 'msg'=>'缺少必要参数' ]);
        if( M('trich_text_info')-> where("`node_id`={$this-> node_id} AND `id`={$id}") ->delete())
        {
            $this-> ajaxReturn([ 'code'=> 0, 'data'=>'ok', 'msg'=>'' ]);
        }
    }

    /**
     * 模板保存
     */
    public function tempSave()
    {
        $memo = htmlspecialchars_decode( I('post.memo'));
        if( empty( $memo )) $this-> ajaxReturn(array('status' => '0', 'info' => '缺少必要参数'));

        try{
            $id = M('trich_text_temp')
                -> add([ 'node_id'=> $this-> node_id,'path'=> '', 'time'=> date('YmdHis') ]);
            if( $id === false ) $this-> ajaxReturn( array('status' => '0', 'info' => '模板添加失败！'));
        }
        catch ( \Exception $e )
        {
            $this-> ajaxReturn(array('status' => '0', 'info' => '数据库写入失败'));
        }

        $path     = "{$this-> node_id}/".date('Y').'/'.date('m').'/'.date('d').'/';
        $filename = "temp_{$id}.json";

        if( !is_dir( $this-> upload.$path ))
        {
            mkdir( $this-> upload.$path, 0777, true );
        }
        if( !file_put_contents( $this-> upload.$path.$filename, $memo )) $this-> ajaxReturn(array('status' => '0', 'info' => '文件写入失败'));
        try{
            $return = M('trich_text_temp')
                -> where( "`id`={$id}" )
                -> save([ 'path'=> $path.$filename ]);
            if( $return ) $this-> ajaxReturn( array('status' => '1','data'=> $id, 'info' => '模板添加成功！'));
        }
        catch ( \Exception $e )
        {
            $this-> ajaxReturn(array('status' => '0', 'info' => '数据库写入失败'));
        }
    }

    /**
     * 删除模板
     */
    public function tempDel()
    {
        $id = I('post.id');
        if( empty( $id )) $this-> ajaxReturn([ 'code'=> 40001, 'data'=>'', 'msg'=>'缺少必要参数' ]);
        $temp = M('trich_text_temp')
            -> where( "`node_id`={$this->node_id} AND `id`={$id} " )
            -> find();
        if( empty($temp)) $this-> ajaxReturn([ 'code'=> 40001, 'data'=>'', 'msg'=>'模板不存在' ]);

        try{
            $return = M('trich_text_temp')
                -> where( "`id`={$id}" )
                -> delete();
            if( $return ) $this-> ajaxReturn( array('status' => '1','data'=> $id, 'info' => '模板删除成功！'));
        }
        catch ( \Exception $e )
        {
            $this-> ajaxReturn(array('status' => '0', 'info' => '数据库写入失败'));
        }
    }
    public function saveSuccess()
    {
        $id = I('get.id');

        if( empty( $id )) $this-> error('缺少必要参数');

        $data = M('trich_text_info')
            -> where( "`id`={$id}" )
            -> find();

        $this-> assign( 'batch_type', $this-> batch_type );
        $this-> assign( 'batch_channel_id', $data['batch_channel_id'] );
        $this-> assign( 'batchId', $data['marketing_id'] );
        $this-> assign( 'id', $id );
        $this-> display();
    }
}