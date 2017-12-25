<?php

// 接口入口
class IndexAction extends BaseAction {

    public function run() {
        $reqServ = D('RemoteRequest', 'Service');
        $data    = array(
                'SubmitVerifyReq' => array(
                        'SystemID'      => '6609',
                        'ISSPID'        => '00000186',
                        'TransactionID' => '66091372055946',
                        'Recipients'    => array(
                                'Number' => '13482121286',
                        ),
                        'SendClass'     => 'MMS',
                        'Messages'      => array(
                                'Sms' => array(
                                        'Text' => 'hello',
                                ),
                        ),
                ),
        );
        $result  = $reqServ->requestIssServ($data);
        tag('view_end');
    }
}