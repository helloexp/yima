<?php

/**
 * Created by PhpStorm. User: john_zeng Date: 2015/11/06 Time: 09:38
 */
class DailyCashStatisticsModel extends BaseModel {

    protected $tableName = 'tnode_daily_cash_statistics';

    public function getDailyInfo() {
        $dailyInfo = $this->getData($this->tableName);
        return $dailyInfo;
    }
    
    // 取得第一条数据的时间
    public function getFirstTime() {
        $getTime = $this->getField('trans_date');
        return $getTime;
    }
    
    // 取得需要插入的数据
    public function saveInfo($time) {
        $dateAgo = date('Ymd', $time - 3600 * 24); // 取前一天数据
        $date = date('Ymd', $time);
        $totalDaily = M('tnode_total_daily_statistics')->order('date_time desc')
            ->limit(1)
            ->find();
        // 初始化数据
        if (! $totalDaily) {
            $totalDaily = array(
                'total_accounts' => 0,  // 应收总帐
                'pay_accounts' => 0,  // 已提现总额
                'remainder_accounts' => 0,  // 预计账户余额
                'payoff' => 0,  // 盈利
                'need_pay_accounts' => 0,  // 待提现总额 需要减去 已体现金额
                'date_time' => $date); // 账单生成时间
        
        }
        // 取得已支付金额
        $actualMoney = M($this->tableName)->where(
            array(
                'trans_date' => $date))->getField('SUM(actual_money)');
        if ($actualMoney) {
            $totalDaily['total_accounts'] = $totalDaily['total_accounts'] +
                 $actualMoney;
        }
        // 取得可提现金额
        $sql = "SELECT SUBSTR(add_time, 1, 8) as add_time,
                IFNULL(SUM(CASE trans_status WHEN '3' THEN cash_money END) ,0.00) AS pay_accounts,
                IFNULL(SUM(CASE trans_type WHEN '1' THEN cash_money END) ,0.00) AS have_accounts
                FROM `tnode_cash_trace` 
                WHERE add_time LIKE '{$date}%'
                GROUP BY SUBSTR(add_time, 1, 8)";
        $getCash = M('tnode_cash_trace')->query($sql);
        $payAccounts = 0;
        $haveAccounts = 0;
        if (count($getCash) > 0) {
            $payAccounts = $getCash[0]['pay_accounts'];
            $haveAccounts = $getCash[0]['have_accounts'] -
                 $getCash[0]['pay_accounts'];
        }
        $payAccounts = $totalDaily['pay_accounts'] + $payAccounts;
        $haveAccounts = $totalDaily['need_pay_accounts'] + $haveAccounts;
        $remainderAccounts = $totalDaily['total_accounts'] - $payAccounts;
        $totalDaily['pay_accounts'] = $payAccounts > 0 ? $payAccounts : '0.00';
        $totalDaily['need_pay_accounts'] = $haveAccounts > 0 ? $haveAccounts : '0.00';
        $totalDaily['remainder_accounts'] = $remainderAccounts > 0 ? $remainderAccounts : '0.00';
        $payOff = $totalDaily['remainder_accounts'] -
             $totalDaily['need_pay_accounts'];
        $totalDaily['payoff'] = $payOff > 0 ? $payOff : '0.00';
        $totalDaily['date_time'] = $date;
        unset($totalDaily['id']); // 去除获取的ID
        M('tnode_total_daily_statistics')->add($totalDaily);
    }
}