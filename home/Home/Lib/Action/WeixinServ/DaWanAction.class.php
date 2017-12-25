<?php
// �����̨��ʱ����Action
class DaWanAction extends BaseAction {

    public function _initialize() {
        C(require (CONF_PATH . 'Label/config.php'));
        C('LOG_PATH', C('LOG_PATH') . 'DAWAN_');
    }

    public function deal_dawan() {
        // ��ȡ��ǰʱ��
        $now_time = date('YmdHis');
        // ����δ�����¼
        // SELECT * FROM twx_dawan_sess WHERE end_time < '20150324000000' AND
        // STATUS = '0'
        $sess_list = M('twx_dawan_sess')->where(
            "end_time < '" . $now_time . "' and STATUS = '0' ")->select();
        if (! $sess_list) {
            $this->log("δ�ҵ��������¼:" . M()->_sql());
            echo "δ�ҵ��������¼:" . M()->_sql();
        } else {
            // ѭ������
            foreach ($sess_list as $sess_info) {
                // ���´���״̬ ȷ�����ظ�����
                $sess_info_update['status'] = '1';
                $rs = M('twx_dawan_sess')->where("id= " . $sess_info['id'])->save(
                    $sess_info_update);
                if ($rs === false) {
                    $this->log("���´���״̬ʧ�� ȷ�����ظ�����:" . M()->_sql());
                    echo "���´���״̬ʧ�� ȷ�����ظ�����:" . M()->_sql();
                    return;
                }
                // �������� ����
                M()->startTrans();
                // SELECT * FROM twx_dawan_score WHERE batch_number = '1111'
                // ORDER BY max_score , update_time
                $score_list = M('twx_dawan_score')->lock(true)
                    ->where(
                    "batch_number = '" . $sess_info['batch_number'] . "' ")
                    ->order('max_score desc, update_time')
                    ->select();
                if (! $score_list) {
                    M()->rollback();
                    $this->log("δ�ҵ�������¼ ����:" . M()->_sql());
                    echo "δ�ҵ�������¼ ����:" . M()->_sql();
                    continue;
                }
                // ѭ����������
                // UPDATE twx_dawan_score SET rank_number = 1 WHERE id = 1;
                $i = 1;
                foreach ($score_list as $score_info) {
                    $score_info_update['rank_number'] = $i;
                    $rs = M('twx_dawan_score')->where(
                        "id= " . $score_info['id'])->save($score_info_update);
                    $i ++;
                    if ($rs === false) {
                        M()->rollback();
                        $this->log("ѭ����������ʧ�� ����:" . M()->_sql());
                        echo "ѭ����������ʧ�� ����:" . M()->_sql();
                        return;
                    }
                }
                M()->commit(); // �ύ����
                               // ���Ҹû�Ľ�Ʒ��Ϣ
                               // SELECT * FROM tcj_cate WHERE batch_id = 1 AND
                               // STATUS = '1' ;
                $cate_list = M('tcj_cate')->where(
                    "STATUS = '1' and batch_id = '" . $sess_info['batch_id'] .
                         "' ")->select();
                if (! $cate_list) {
                    $this->log("���Ҹû�Ľ�Ʒ��Ϣ  :" . M()->_sql());
                    echo "���Ҹû�Ľ�Ʒ��Ϣ :" . M()->_sql();
                    return;
                }
                // ѭ������Ʒ��Ϣ��¼
                // UPDATE twx_dawan_score SET cate_id = 1 WHERE batch_number =
                // '1111' AND rank_number >= min_rank AND rank_number <=
                // max_rank;
                foreach ($cate_list as $cate_info) {
                    $cate_info_info_update['cate_id'] = $cate_info['id'];
                    $rs = M('twx_dawan_score')->where(
                        "batch_number = '" . $sess_info['batch_number'] .
                             "' AND  rank_number >= " . $cate_info['min_rank'] .
                             " AND  rank_number <= " . $cate_info['max_rank'])->save(
                        $cate_info_info_update);
                    if ($rs === false) {
                        $this->log("ѭ������Ʒ��Ϣ��¼ʧ�� ����:" . M()->_sql());
                        echo "ѭ������Ʒ��Ϣ��¼ʧ�� ����:" . M()->_sql();
                        return;
                    }
                }
                // ��Ʒѭ�������������ʼ���봦��ѭ��
            }
        }
        // ѭ������
        // SELECT * FROM twx_dawan_score WHERE cate_id IS NOT NULL AND STATUS =
        // '0'
        $score_list = M('twx_dawan_score')->where(
            "cate_id IS NOT NULL AND STATUS = '0' ")->select();
        if (! $score_list) {
            $this->log("δ�ҵ�ѭ��������¼ :" . M()->_sql());
            echo "δ�ҵ�ѭ��������¼ :" . M()->_sql();
            return;
        }
        foreach ($score_list as $score_info) {
            // �齱����
            import('@.Vendor.ChouJiang');
            $choujiang = new ChouJiang($score_info['batch_channel_id'], 
                $score_info['mobile'], $score_info['batch_channel_id'], '', 
                $other);
            // print_r($score_info);
            $resp = $choujiang->send_code();
            // ���´�����
            // UPDATE twx_dawan_score SET STATUS = '1' AND status_msg = '0000'
            // WHERE id = 1;
            if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
                $score_info_update['status'] = '1';
                $score_info_update['status_msg'] = $resp['resp_desc'];
            } else {
                $score_info_update['status'] = '2';
                $score_info_update['status_msg'] = '[' . $resp['resp_id'] . ']' .
                     $resp['resp_desc'];
            }
            $rs = M('twx_dawan_score')->where("id= " . $score_info['id'])->save(
                $score_info_update);
            if ($rs === false) {
                M()->rollback();
                $this->log("���³齱������ʧ�� ����:" . M()->_sql());
                echo "���³齱������ʧ�� ����:" . M()->_sql();
                return;
            }
        }
    }
    
    // ��¼��־
    protected function log($msg, $level = Log::INFO) {
        // trace('Log.'.$level.':'.$msg);
        Log::write($msg, '[' . getmypid() . ']' . $level);
    }
}
