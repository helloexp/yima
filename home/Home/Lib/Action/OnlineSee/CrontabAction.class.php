<?php

class CrontabAction extends Action
{
    // public $_authAccessMap = '*';
    public function _initialize()
    {
        /*
     * if($this->getIP()!=='127.0.0.1') { die("非法访问！"); }
     */
    }

    private function getIP()
    {
        static $realip;
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }
        }

        return $realip;
    }

    public function index()
    {
        $domain = is_production() ? 'www.wangcaio2o.com' : 'test.wangcaio2o.com';
        set_time_limit(0);
        $map['a.status'] = 0;
        $list = M()->table('tfb_onlinesee_link_crontab a')
            ->where($map)
            ->field('a.*')
            ->limit('0,1')
            ->select();
        M()->startTrans();
        foreach ($list as $row) {
            if ($row['status'] != 0) {
                continue;
            }

            $update_data = array('status' => 1);
            $Where['id'] = $row['id'];
            $res = M('tfb_onlinesee_link_crontab')->where($Where)->save($update_data);
            if ($res > 0) {
                M()->commit();
                if ($row['crontab_type'] == 0) {
                    for ($i = 0; $i < $row['need_count']; ++$i) {
                        // 拼接短链
                        if ($row['link_type'] == 1) {
                            $url = U('OnlineSee/InsuranceWap/index',
                                array(
                                    'id' => $row['crew_id'],
                                    'type' => 1, ), '', '', $domain);
                        }
                        if ($row['link_type'] == 0) {
                            $url = U('OnlineSee/InsuranceWap/index',
                                array(
                                    'id' => $row['crew_id'],
                                    'type' => 0, ), '', '', $domain);
                        }

                        // $link = $this->shorturl($link);

                        $key = md5(microtime().$row['id'].$i);
                        $new_url = $url.'&key='.$key;
                        $shorturl = $this->shorturl($new_url);
                        $add_data = array(
                            'crontab_id' => $row['id'],
                            'url_key' => $key,
                            'status' => 0,
                            'new_url' => $new_url,
                            'short_url' => $shorturl, );

                        $flag = M('tfb_onlinesee_link')->add($add_data);
                        if ($flag === false) {
                            M()->rollback();
                            log_write('添加记录保存失败！'.M()->_sql(), '',
                                'OnlineSee');
                        }
                    }
                    $update_data = array('status' => 2);
                    $Where['id'] = $row['id'];
                    $res = M('tfb_onlinesee_link_crontab')->where($Where)->save($update_data);
                    M()->commit();
                } else {
                    $gruop_ids = $row['group_id'];
                    $gruop_ids = explode(',', $gruop_ids);
                    if (empty($gruop_ids)) {
                        M()->rollback();
                        log_write('缺少分组id', 'OnlineSee');
                        continue;
                    }
                    foreach ($gruop_ids as $gruop_id) {
                        if ($gruop_id == 0) {
                            $crew_ids = M()->table('tfb_onlinesee_member a')
                                ->where(array('status' => 1))
                                ->field('a.id')
                                ->select();
                        } else {
                            $crew_ids = M()->table('tfb_onlinesee_crew a')
                                ->where(array('group_id' => $gruop_id, 'crew_sta' => 1))
                                ->field('a.id')
                                ->select();
                        }
                        if ($row['link_type'] == 2) {
                            if ($gruop_id > 0) {
                                $row['link_type'] = 0;
                            } else {
                                $row['link_type'] = 1;
                            }
                        }
                        foreach ($crew_ids as $crew) {
                            for ($i = 0; $i < $row['need_count']; ++$i) {
                                // 拼接短链
                                if ($row['link_type'] == 1) {
                                    $url = U('OnlineSee/InsuranceWap/index',
                                        array(
                                            'id' => $crew['id'],
                                            'type' => 1, ), '', '', $domain);
                                }
                                if ($row['link_type'] == 0) {
                                    $url = U('OnlineSee/InsuranceWap/index',
                                        array(
                                            'id' => $crew['id'],
                                            'type' => 0, ), '', '', $domain);
                                }
                                // $link = $this->shorturl($link);

                                $key = md5(microtime().$row['id'].$i);
                                $new_url = $url.'&key='.$key;
                                $shorturl = $this->shorturl($new_url);
                                $add_data = array(
                                    'crontab_id' => $row['id'],
                                    'url_key' => $key,
                                    'status' => 0,
                                    'new_url' => $new_url,
                                    'short_url' => $shorturl, );

                                $flag = M('tfb_onlinesee_link')->add($add_data);
                                if ($flag === false) {
                                    M()->rollback();
                                    log_write('添加记录保存失败！'.M()->_sql(), '',
                                        'OnlineSee');
                                }
                            }
                            M()->commit();
                        }
                    }
                    $update_data = array('status' => 2);
                    $Where['id'] = $row['id'];
                    $res = M('tfb_onlinesee_link_crontab')->where($Where)->save($update_data);
                    M()->commit();
                }
            } else {
                M()->rollback();
                log_write('任务'.$row['id'].'状态更新失败', '', 'OnlineSee');
            }
        }
        M()->commit();
    }

    public function shorturl($long_url)
    {
        $apiUrl = C('ISS_SERV_FOR_IMAGECO');
        $req_arr = array(
            'CreateShortUrlReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'),
                'TransactionID' => time().rand(10000, 99999),
                'OriginUrl' => "<![CDATA[$long_url]]>", ), );

        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($req_arr, 'gbk');
        $error = '';
        $result_str = httpPost($apiUrl, $str, $error);
        if ($error) {
            echo $error;

            return '';
        }

        $arr = $xml->parse($result_str);
        $arr = $xml->getArrayNoRoot();

        return $arr['Status']['StatusCode'] == '0000' ? $arr['ShortUrl'] : '';
    }
}
