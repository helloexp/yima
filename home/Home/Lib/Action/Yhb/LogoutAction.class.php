<?php

class LogoutAction extends Action {

    public function index() {
        $memcache = new Memcache();
        $memcache_ip = C('SSO_MEMCACHE_IP');
        $memcache_port = 11211;
        $memcache->addserver($memcache_ip, $memcache_port) or
             $this->error("退出失败,无法连接memcache");
        $token = $memcache->get("WC_" . session_id());
        if (! $token) {
            if (isset($_SESSION)) {
                session_unset();
                session_write_close();
            }
            header("location:index.php");
            exit();
        } else {
            $RemoteRequest = D('UserSess', 'Service');
            $t1 = microtime(true);
            $res = $RemoteRequest->ssoLogout($token);
            $t2 = microtime(true);
            $logtime = (($t2 - $t1) * 1000) . 'ms';
            Log_write("退出时间计算==" . $logtime);
            
            if (! $res || $res['resp_id'] != '0000') {
                if (isset($_SESSION)) {
                    session_unset();
                    session_write_close();
                }
                // session(array('id'=>$session_id));
                
                header("location:index.php?g=Yhb&m=Login&a=showLogin");
                exit();
            } else {
                node_log("退出系统");
                header("location:index.php?g=Yhb&m=Login&a=showLogin");
            }
        }
    }
}
