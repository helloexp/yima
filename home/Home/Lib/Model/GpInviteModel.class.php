<?php
class GpInviteModel extends GpBaseModel
{
    public function wethinfo( )
    {
       $list=M()->table('tfb_gp_invite_config')->find();
       return $list;
    }

    public function addcard($data)
    {
        $result=M()->table('tfb_gp_invite_config')->add($data);

        return $result;
    }

    public function changecard($data,$condition)
    {
        $result=M()->table('tfb_gp_invite_config')->where($condition)->save($data);
        return $result;
    }
}
