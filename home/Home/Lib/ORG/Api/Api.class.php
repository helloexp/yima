<?php

/**
 * Class Api
 */
class Api
{
    const API_SECRET = 'fjzlT9HcyumCwV4VGxKtGkwdyQC9P07p';
    //const DOMAIN     = 'http://10.10.1.155/new/';  //测试
    const DOMAIN     = 'http://test.wangcaio2o.com/new/';  //测试
    //const DOMAIN     = 'http://192.168.0.254/new/';  //生产
    //const DOMAIN     = 'http://192.168.171.135/new/';  //本地
    const DEF_TIME   = 10;

    /**
     * post方式提交
     * @param $url
     * @param $data
     * @param int $second
     * @return mixed
     */
    public static function post( $url, $data, $second=self::DEF_TIME )
    {
        $data = self::formatData( $data );
        $data['sign'] = self::CreatSign( $data );
        return self::curl( self::DOMAIN . $url, $second, 'post', $data );
    }

    /**
     * get方式提交
     * @param $url
     * @param $data
     * @param int $second
     * @return mixed
     */
    public static function get( $url, $data, $second=self::DEF_TIME )
    {
        $data = self::formatData( $data );
        $data['sign'] = self::CreatSign( $data );
        return self::curl( self::DOMAIN . $url . '?' . http_build_query( $data ), $second );
    }

    /**
     * 生成密钥
     * @param $data
     * @return string
     */
    private static function CreatSign( $data )
    {
        $args = [];

        foreach ( $data as $k=> $v )
        {
            if( $k != 'sslcert' && $k != 'sslkey' )
            {
                $args[$k] = $v;
            }
        }

        ksort( $args, SORT_STRING );
        $string = '';
        foreach ( $args as $k => $v)
        {
            if( is_object( $v )) continue;
            if( is_array( $v )) $string .= $k.serialize($v);
            else $string .= "{$k}{$v}";
        }
        return md5( $string.self::API_SECRET );
    }

    /**
     * 整理数据
     * @param $array
     * @return array
     */
    private static function formatData( $array )
    {
        $return = [];
        foreach ( $array as $k=> $v )
        {
            if( isset( $v ))
            {
                if( $v !== '' )
                {
                    if( is_array( $v )) $return[$k] = serialize($v);
                    else $return[$k] = $v;
                }
            }
        }
        return $return;
    }
    /**
     * curl获取
     * @param $url
     * @param int $second
     * @param string $mode
     * @param null $postData
     * @return mixed
     * @throws WxPayException
     */
    private static function curl( $url, $second = 30, $mode='get', $postData=null )
    {
        $ch = curl_init();
        //设置超时
        curl_setopt( $ch, CURLOPT_TIMEOUT, $second );
        curl_setopt( $ch, CURLOPT_URL, $url );
        //设置header
        curl_setopt( $ch, CURLOPT_HEADER, FALSE );
        //要求结果为字符串且输出到屏幕上
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );

        /*       //如果有配置代理这里就设置代理
                if( $wxPayConfig-> curl_proxy_host != "0.0.0.0" && $wxPayConfig-> curl_proxy_port != 0 ){
                    curl_setopt( $ch,CURLOPT_PROXY, $wxPayConfig-> curl_proxy_host );
                    curl_setopt( $ch,CURLOPT_PROXYPORT, $wxPayConfig-> curl_proxy_port );
                }

                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER,TRUE );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST,2 );//严格校验

                if( $useCert == true ){
                    //设置证书
                    //使用证书：cert 与 key 分别属于两个.pem文件
                    curl_setopt( $ch, CURLOPT_SSLCERTTYPE,'PEM' );
                    curl_setopt( $ch, CURLOPT_SSLCERT, $wxPayConfig-> sslcert_path );
                    curl_setopt( $ch, CURLOPT_SSLKEYTYPE,'PEM' );
                    curl_setopt( $ch, CURLOPT_SSLKEY, $wxPayConfig-> sslkey_path );
                }*/

        if( $mode == 'post' )
        {
            //post提交方式
            curl_setopt( $ch, CURLOPT_POST, TRUE );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $postData );
        }

        //运行curl
        $data = curl_exec( $ch );
        curl_close( $ch );
        return $data;
    }
    public static function apiReturn( $data )
    {

        $result = json_decode( $data, true );
        if( (int)$result['code'] === 0 )
        {
            self::jsonReturn( $result['data'] );
        }
        else
        {
            self::jsonReturn( null, $result['code'], $result['msg'] );
        }
    }
    /**
     * json输出
     * @param unknown $data
     * @param number $errcode
     * @param string $err
     */
    public static function jsonReturn( $data, $errcode=0, $err='' )
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods:POST,GET,OPTIONS,PUT,DELETE');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');

        //$header_list = headers_list();
        //log_write('$header_list:'.var_export($header_list,1), 'INFO', 'JSONRET');

        $header_list = headers_list();
        error_log('$header_list:'.var_export($header_list,1), 3, '/tmp/JSONRET');

        exit( json_encode( array( 'code'=>(int)$errcode, 'data'=>$data, 'msg'=>$err ), JSON_UNESCAPED_UNICODE ));
    }
    public static function csv_h( $filename )
    {
        header("Content-type:text/csv");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=".$filename.".csv");
        header('Expires:0');
        header('Pragma:public');
    }
    public static function downloadCsvData( $csv_data=[],$arrayhead=[] )
    {
        $csv_string = null;
        $csv_row    = array();
        if(!empty($arrayhead)){
            $current = [];
            foreach( $arrayhead AS $item )
            {
                $current[]= mb_convert_encoding($item, 'GBK', 'UTF-8') ;
            }
            $csv_row[]    = trim(implode( "," , $current ),',');
        }
        foreach( $csv_data as $key => $csv_item )
        {
            $current = [];
            foreach( $csv_item AS $item )
            {
                if( preg_match( '/^(\d){15,}$/', $item ))
                {
                    $item = "'".$item."'";
                }
                $current[]= mb_convert_encoding($item, 'GBK', 'UTF-8') ;
            }
            $csv_row[]    = trim(implode( "," , $current ),',');
        }
        $csv_string = implode( "\r\n", $csv_row );
        echo $csv_string;
    }
}
?>