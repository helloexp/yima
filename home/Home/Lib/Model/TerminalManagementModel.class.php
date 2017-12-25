<?php

/**
 * 终端管理
 *
 * @author bao
 */
class TerminalManagementModel extends Model {
    protected $tableName = '__NONE__';
    /**
     * 获取指定状态下商户所有终端信息
     *
     * @param $nodeId 商户号
     * @param $status 终端状态 0-新增，未绑定1-已绑定，未激活 2-正常 3 停机 4 过期 5 注销 null所有
     */
    public function getTerminalList($nodeId, $status = null) {
        is_null($status) ? $where = array(
            'node_id' => $nodeId) : $where = array(
            'node_id' => $nodeId, 
            'status' => $status);
        $terminalList = M('tpos_info')->where($where)->select();
        return $terminalList;
    }
}