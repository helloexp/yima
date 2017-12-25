<?php
class EposHelpAction extends Action{
    public function helpList(){
        $like = I('post.like', '');
            $list = M('tym_news')->where(array('to_app'=>1,'news_name'=>array('like','%'.$like.'%')
                ))->select();
        $this->assign('list',$list);
        $this->assign('like',$like);
        $this->display();
    }
    public function helpDetail(){
        $id = I('id','');
        $news = M('tym_news')->where(array('news_id'=>$id))->find();
        if(!$news){
            $this->error('错误请求');
        }
        $this->assign('news',$news);
        $this->display();
    }
}