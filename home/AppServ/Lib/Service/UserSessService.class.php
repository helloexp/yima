<?php

/* 用户登录服务 */

class UserSessService {

    /**
     * 旺财用户登陆,若登陆成功则创建session
     *
     * @return boolean
     */
    public $sess_id;
    // sesion_id
    public $session;

    public function __construct() {
        // 导入session
        import('@.Model.Session.PosSession') or die('[@.Model.Session.PosSession]导入失败');
        $this->session = new PosSession();
    }

    public function LoginWangCai($posId, $userId, $userName, $nodeId, $posSerial) {
        // 判断机构判断态
        $nodeInfo = M('TnodeInfo')->where("node_id='" . $nodeId . "'")->field("status")->find();
        if (!$nodeInfo || $nodeInfo['status'] != '0') {
            $this->errMsg = "机构不存在或已停用";

            return false;
        }
        $posService = D("Pos", 'Service');
        $rs         = $posService->posIsOpen($posId);
        if (!$rs) {
            $this->errMsg = "终端关闭状态";

            return false;
        }
        // 判断是否激活
        if (!$posService->checkIsActivated($posId)) {
            // 激活,如果激活失败
            if (!$posService->activatePos($posId)) {
                $this->errMsg = $posService->getErrMsg();

                return false;
            }
        }
        // 再次判断激活标识
        if (!$posService->checkIsActivated($posId)) {
            $this->errMsg = '终端未激活';

            return false;
        }
        $posInfo = $posService->getPos($posId);
        // 终端签到
        $rs = $posService->checkin($posId, $userId, $posService->getNewPosSeq($posId), $posSerial,
                $posInfo['master_key'], $posInfo['work_key']);
        if (!$rs) {
            !empty($this->errMsg) || $this->errMsg = "签到失败";

            return false;
        }
        // 更新工作密钥
        $posService->updateWorkKey($posId, $posInfo['work_key']);

        // 校验用户名密码
        $user      = M('TuserInfo');
        $where     = "NODE_ID ='" . $nodeId . "' and USER_NAME ='" . $userName . "'";
        $user_info = $user_info2 = $user->where($where)->find();

        // 用户存在 role_id 1：管理员 2：老板 3：小妹
        if ($user_info) {
            // 老板用户,在数据库中 值为 2
            if ($user_info['role_id'] == '2') {
                $role_id = 1;
                // SSO用户号是空,保存SSO用户ID
                if ($user_info['user_id'] == '') {
                    $user->where($where)->save(array(
                            "user_id" => $userId,
                    ));
                }
            } else {
                $role_id = 0;
            }
            $uid = $user_info['id'];
            // 用户不存在，那就是小妹账户 小妹 3
        } else {
            // 添加小妹用户信息
            $uid     = $user->add(array(
                    "user_id"   => $userId,
                    "node_id"   => $nodeId,
                    "user_name" => $userName,
                    'role_id'   => '3',
                    "add_time"  => date('YmdHis'),
            ));
            $role_id = 0;
        }
        // 更新用户登录信息
        $user->where("id='$uid'")->save(array(
                'login_time' => date('YmdHis'),
                'login_ip'   => $_SERVER['REMOTE_ADDR'],
        ));
        // 导入session
        $session = $this->session;

        $userInfo['user_id']    = $userId;
        $userInfo['user_name']  = $userName;
        $userInfo['node_id']    = $nodeId;
        $userInfo['pos_name']   = $posInfo['pos_name'];
        $userInfo['pos_id']     = $posInfo['pos_id'];
        $userInfo['master_key'] = $posInfo['master_key'];
        $userInfo['work_key']   = $posInfo['work_key'];
        $userInfo['user_role']  = $role_id;
        $session->login($userInfo);

        return true;
    }

    /*
     * 保存SESSION
     */
    public function getSessionId() {
        return session_id();
    }

    public function getErrMsg() {
        return $this->errMsg;
    }

    public function getSession() {
        return $this->session;
    }
}