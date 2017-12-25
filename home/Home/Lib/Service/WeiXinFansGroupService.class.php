<?php
// 微信粉丝分组接口
import('@.Service.WeiXinBaseService') or die('导入包失败');

class WeiXinFansGroupService extends WeiXinBaseService
{

    public $appId;

    public $appSecret;

    public $accessToken;

    public $weixinInfo;

    public $error = '';
    // 初始化

    /*
     * 获取用户增减数据
     */
    public function apiGetusersummary($begin_time = '', $end_time = '')
    {
        if (!$begin_time) {
            $begin_time = date('Y-m-d', strtotime('-1 day'));
        } else {
            $begin_time = date('Y-m-d', $begin_time);
        }
        if (!$end_time) {
            $end_time = date('Y-m-d', strtotime('-1 day'));
        } else {
            $end_time = date('Y-m-d', $end_time);
        }
        $apiUrl  = 'https://api.weixin.qq.com/datacube/getusersummary';
        $dataArr = array(
                "begin_date" => $begin_time,
                "end_date"   => $end_time
        );
        $result  = $this->send($apiUrl, $dataArr);

        return $result;
    }

    /*
     * 获取累计用户数据
     */
    public function apiGetusercumulate($begin_time = '', $end_time = '')
    {
        if (!$begin_time) {
            $begin_time = date('Y-m-d', strtotime('-1 day'));
        } else {
            $begin_time = date('Y-m-d', $begin_time);
        }
        if (!$end_time) {
            $end_time = date('Y-m-d', strtotime('-1 day'));
        } else {
            $end_time = date('Y-m-d', $end_time);
        }
        $apiUrl  = 'https://api.weixin.qq.com/datacube/getusercumulate';
        $dataArr = array(
                "begin_date" => $begin_time,
                "end_date"   => $end_time
        );
        $result  = $this->send($apiUrl, $dataArr);

        return $result;
    }

    /*
     * 创建粉丝分组
     */
    public function apiCreateGroup($groupName)
    {
        $apiUrl                   = 'https://api.weixin.qq.com/cgi-bin/groups/create';
        $dataArr['group']['name'] = $groupName;
        $result                   = $this->send($apiUrl, $dataArr);

        if ($result['errcode'] == 0 || !isset($result['errcode'])) {
            return $result['group']['id'];
        } else {
            return false;
        }
    }

    /*
     * 查询所有分组 返回报文示例 { "groups": [ { "id": 0, "name": "未分组", "count": 72596 },
     * ...
     */
    private function apiQueryGroup()
    {
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/groups/get';
        $result = $this->send($apiUrl, null);

        if ($result['errcode'] == 0 || !isset($result['errcode'])) {
            return $result;
        } else {
            return false;
        }
    }

    /*
     * 修改分组名
     */
    public function apiModifyGroupName($groupId, $name)
    {
        $apiUrl                   = 'https://api.weixin.qq.com/cgi-bin/groups/update';
        $dataArr['group']['id']   = $groupId;
        $dataArr['group']['name'] = $name;
        $result                   = $this->send($apiUrl, $dataArr);

        if ($result['errcode'] == 0 || !isset($result['errcode'])) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * 移动用户分组
     */
    public function apiMoveGroupByOpenId($groupId, $openId)
    {
        $apiUrl                = 'https://api.weixin.qq.com/cgi-bin/groups/members/update';
        $dataArr['to_groupid'] = $groupId;
        $dataArr['openid']     = $openId;
        $result                = $this->send($apiUrl, $dataArr);

        if ($result['errcode'] == 0 || !isset($result['errcode'])) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * 批量移动用户分组
     */
    public function apiBatchMoveGroupByOpenIdArray($groupId, $openIdArray)
    {
        $apiUrl                 = 'https://api.weixin.qq.com/cgi-bin/groups/members/batchupdate';
        $dataArr['openid_list'] = $openIdArray;
        $dataArr['to_groupid']  = $groupId;
        $result                 = $this->send($apiUrl, $dataArr);

        if ($result['errcode'] == 0 || !isset($result['errcode'])) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * 删除用户分组
     */
    public function apiDeleteGroup($groupId)
    {
        // 0-未分组 1-黑名单 2-星标组 不删除
        if ($groupId == 0 || $groupId == 1 || $groupId == 2) {
            return true;
        }
        $apiUrl                 = 'https://api.weixin.qq.com/cgi-bin/groups/delete';
        $dataArr['group']['id'] = $groupId;
        $result                 = $this->send($apiUrl, $dataArr);

        if ($result['errcode'] == 0 || !isset($result['errcode'])) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * 删除本地所有分组
     */
    private function deleteWangcaiAllGroup()
    {
        $sql = "DELETE FROM twx_user_group WHERE node_id = '" . $this->weixinInfo['node_id'] . "' AND node_wx_id = '" . $this->weixinInfo['node_wx_id'] . "'";
        $rs  = M()->table('twx_user_group')->execute($sql);
        if ($rs === false) {
            log_write('删除本地所有分组失败 sql: ' . M()->_sql());
            return false;
        }
        return true;
    }

    /*
     * 第一次绑定或重新绑定微信公众号的时候调用该方法对粉丝分组进行处理 拉回微信所有分组，删除并重建本地分组
     */
    public function reCreateWangcaiAllGroupByWeixin()
    {
        // 获取微信所有分组
        $result = $this->apiQueryGroup();
        if ($result === false) {
            log_write("获取微信分组失败！");
            return $result;
        }

        // 删除本地所有分组
        $ret = $this->deleteWangcaiAllGroup();
        if ($ret === false) {
            log_write("删除本地所有分组失败！");
            M()->rollback();
            return $ret;
        }

        // 按微信新建本地分组
        foreach ($result['groups'] as $group) {
            // 未分组/黑名单/星标组无需新建，跳过
            if ($group['id'] == 0 || $group['id'] == 1 || $group['id'] == 2) {
                continue;
            }
            $wxUserGroup['name']        = $group['name'];
            $wxUserGroup['wx_group_id'] = $group['id'];
            $wxUserGroup['count']       = $group['count'];
            $wxUserGroup['node_wx_id']  = $this->weixinInfo['node_wx_id'];
            $wxUserGroup['node_id']     = $this->weixinInfo['node_id'];
            $rs                         = M()->table('twx_user_group')->add($wxUserGroup);
            if ($rs === false) {
                log_write("新建本地分组失败！" . M()->_sql());
                M()->rollback();
                return false;
            }
        }

        return true;
    }

    /*
     * 手工同步或者每天同步的时候，按照本地分组和微信分组进行比对同步 以本地分组为准
     */
    public function wangcaiGroupSyncToWeixin()
    {
        // 获取微信分组
        $result = $this->apiQueryGroup();
        if ($result === false) {
            log_write("get fans group error:[" . $this->error . "]");
            return false;
        }
        $weixinGroupMap = array();
        foreach ($result['groups'] as $group) {
            $weixinGroupMap[$group['name']] = $group['id'];
        }
        // 获取本地分组名称
        $where  = "node_id = '" . $this->weixinInfo['node_id'] . "' AND node_wx_id = '" . $this->weixinInfo['node_wx_id'] . "'";
        $result = M('twx_user_group')->field('id, name')->where($where)->select();
        // 按名称进行对比，没有的对微信进行新增
        foreach ($result as $localGroup) {
            if (isset($weixinGroupMap[$localGroup['name']])) { // 微信侧已存在
                unset($weixinGroupMap[$localGroup['name']]);
            } else { // 微信侧不存在 新建
                $weixinGroupId = $this->apiCreateGroup($localGroup['name']);
                if ($weixinGroupId === false) {
                    log_write("create weixin fansGroup error " . $this->error);
                    return false;
                } else { // 保存入数据库
                    $localGroupSave['wx_group_id'] = $weixinGroupId;

                    $ret = M('twx_user_group')->where(['id' => $localGroup['id']])->save($localGroupSave);
                    if ($ret === false) {
                        log_write("save weixin fansGroup error " . $this->error);
                        return false;
                    }
                }
            }
        }
        // 去微信侧删除旺财本地没有的分组
        foreach ($weixinGroupMap as $k => $v) {
            if ($v == 0 || $v == 1 || $v == 2) { // 未分组和黑名单、星标组跳过
                continue;
            }
            if (!$this->apiDeleteGroup($v)) {
                log_write("save weixin fansGroup error " . $this->error);
                return false;
            }
        }
        return true;
    }

    /*
     * 手工同步或者每天同步的时候，按照本地分组对微信侧的粉丝进行分组批量移动
     */
    public function wangcaiFansGroupsBatchSyncToWeixin()
    {
        // 遍历本机构所有微信粉丝，对已分组粉丝进行分组批量移动，凑足50个发起通讯
        $count       = 0; // 计数器
        $openIdArray = array();
        $groupId     = 0;
        $where       = "u.node_id = '" . $this->weixinInfo['node_id'] . "' AND u.node_wx_id = '" . $this->weixinInfo['node_wx_id'] . "' AND u.subscribe = '1' ";
        $result      = M()->table('twx_user u')->join('LEFT JOIN twx_user_group g ON u.group_id = g.id')->field(
                'u.openid, g.wx_group_id as group_id'
        )->where($where)->order("u.group_id")->select();
        if ($result === false) {
            log_write(
                    "syncWangcaiFansGroupToWeixin get fans list error:[" . M()->_sql() . "]"
            );
            return false;
        } else {
            foreach ($result as $fans) {
                if (($fans['group_id'] != $groupId) || $count >= 50) { // 分组编号变化或者满足50个，开始通讯更改
                    if (!$this->apiBatchMoveGroupByOpenIdArray(
                            $groupId,
                            $openIdArray
                    )
                    ) {
                        log_write(
                                "syncWangcaiFansGroupToWeixin apiBatchMoveGroupByOpenIdArray error:[" . $this->error . "]"
                        );
                        return false;
                    } else {
                        // 移动成功 帮当前这个数据加入数组
                        $groupId = $fans['group_id'];
                        $count   = 1;
                        unset($openIdArray);
                        $openIdArray[] = $fans['openid'];
                    }
                } else {
                    $groupId = $fans['group_id'];
                    $count++;
                    $openIdArray[] = $fans['openid'];
                }
            }
            // 把残余数据发出去
            if (count($openIdArray) > 0 && !$this->apiBatchMoveGroupByOpenIdArray($groupId, $openIdArray)) {
                log_write(
                        "syncWangcaiFansGroupToWeixin apiBatchMoveGroupByOpenIdArray error:[" . $this->error . "]"
                );
                return false;
            }
        }
        return true;
    }

    /*
     * 获取本地分组和微信分组之间的对应关系
     */
    public function getGroupMapArray()
    {
        $where       = ['node_id' => $this->weixinInfo['node_id'], 'node_wx_id' => $this->weixinInfo['node_wx_id']];
        $result      = M('twx_user_group')->field('id, wx_group_id')->where($where)->select();
        $mapArray    = array();
        $mapArray[0] = 0; // 未分组
        $mapArray[1] = 1; // 黑名单
        $mapArray[2] = 2; // 星标组
        foreach ($result as $map) {
            $mapArray[$map['wx_group_id']] = $map['id'];
        }
        log_write("getGroupMapArray :[" . print_r($mapArray, true) . "]");
        return $mapArray;
    }

    /*
     * 获取微信分组
     */
    public function queryWeixinGroup()
    {
        $result = $this->apiQueryGroup();
        return $result;
    }

    /*
     * 校验粉丝分组统计数是否一致
     */
    public function checkFansGroupCount()
    {
        $result = $this->apiQueryGroup();
        if ($result === false) {
            log_write("get fans group error:[" . $this->error . "]");
            return false;
        }
        $localMap = $this->getGroupMapArray();
        // 取出本地分组统计数
        $where         = [
                'node_id'    => $this->weixinInfo['node_id'],
                'node_wx_id' => $this->weixinInfo['node_wx_id'],
                'subscribe'  => 1
        ];
        $countList     = M('TwxUser')->field("group_id, count(1) as groupCount")->group("group_id")->where(
                $where
        )->select();
        $countMapArray = array();
        foreach ($countList as $map) {
            $countMapArray[$map['group_id']] = $countMapArray[$map['groupCount']];
        }
        foreach ($result['groups'] as $group) {
            if ($group['count'] != $countMapArray[$localMap[$group['id']]]) {
                return false;
            }
        }
        return true;
    }
}