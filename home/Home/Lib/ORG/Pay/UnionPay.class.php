<?php

/**
 * Class UnionPay
 * 银联相关类
 */

class UnionPay
{
    /**
     * 签名
     * @param $params
     */
    public static function sign( &$params )
    {
        $config = (object)C( 'UNIONPAY' );
        $params ['certId'] = self::getSignCertId ( $config-> SIGN_CERT_PATH, $config-> SIGN_CERT_PWD ); //证书ID
        self::createSign( $params, $config-> SIGN_CERT_PATH, $config-> SIGN_CERT_PWD );
    }
    public static function createAutoFormHtml( $params, $reqUrl ) {
        // <body onload="javascript:document.pay_form.submit();">
        $encodeType = isset ( $params ['encoding'] ) ? $params ['encoding'] : 'UTF-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$reqUrl}" method="post">
	
eot;
        foreach ( $params as $key => $value )
        {
            $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }
        $html .= <<<eot
   <!-- <input type="submit" type="hidden">-->
    </form>
</body>
</html>
eot;
        return $html;
    }

    /**
     * 验签
     * @param $params 应答数组
     * @return 是否成功
     */
    public static function validate( $params )
    {
        $public_key     = self::getPulbicKeyByCertId ( $params ['certId'] );
        if( empty( $public_key )) return false;

        // 签名串
        $signature_str  = $params ['signature'];
        unset ( $params ['signature'] );

        $params_str     = self::createLinkString ( $params, true, false );
        $signature      = base64_decode ( $signature_str );
        $params_sha1x16 = sha1 ( $params_str, FALSE );
        $isSuccess      = openssl_verify ( $params_sha1x16, $signature,$public_key, OPENSSL_ALGO_SHA1 );

        return $isSuccess;
    }

    /**
     * 根据证书ID 加载 证书
     *
     * @param unknown_type $certId
     * @return string NULL
     */
    private static function getPulbicKeyByCertId( $certId )
    {
        $config = (object)C( 'UNIONPAY' );
        // 证书目录
        $cert_dir = $config-> VERIFY_CERT_DIR;
        $handle   = opendir ( $cert_dir );
        if ( $handle )
        {
            while ( $file = readdir ( $handle ) )
            {
                clearstatcache ();
                $filePath = $cert_dir . '/' . $file;
                if ( is_file ( $filePath ))
                {
                    if ( pathinfo( $file, PATHINFO_EXTENSION ) == 'cer')
                    {
                        if ( self::getCertIdByCerPath ( $filePath ) == $certId)
                        {
                            closedir ( $handle );
                            return self::getPublicKey ( $filePath );
                        }
                    }
                }
            }
        }
        closedir ( $handle );
        return null;
    }

    /**
     * 取证书公钥 -验签
     *
     * @return string
     */
    private static function getPublicKey( $cert_path )
    {
        return file_get_contents ( $cert_path );
    }

    /**
     * 取证书ID(.cer)
     *
     * @param unknown_type $cert_path
     */
    private static function getCertIdByCerPath( $cert_path )
    {
        $x509data = file_get_contents ( $cert_path );
        openssl_x509_read ( $x509data );
        $certdata = openssl_x509_parse ( $x509data );
        $cert_id = $certdata ['serialNumber'];
        return $cert_id;
    }

    /**
     * 取证书ID(.pfx)
     *
     * @return unknown
     */
    private static function getSignCertId( $cert_path, $cert_pwd )
    {
        $pkcs12certdata = file_get_contents ( $cert_path );
        openssl_pkcs12_read ( $pkcs12certdata, $certs, $cert_pwd );
        $x509data = $certs ['cert'];
        openssl_x509_read ( $x509data );
        $certdata = openssl_x509_parse ( $x509data );
        $cert_id = $certdata ['serialNumber'];
        return $cert_id;
    }
    /**
     * 生成签名
     *
     * @param String $params_str
     */
    private static function createSign( &$params, $cert_path, $cert_pwd )
    {
        if( isset( $params['signature'] ))
        {
            unset( $params['signature'] );
        }
        // 转换成key=val&串
        $params_str     = self::createLinkString ( $params, true, false );
        $params_sha1x16 = sha1( $params_str, FALSE );
        $private_key    = self::getPrivateKey ( $cert_path, $cert_pwd );
        // 签名
        $sign_falg = openssl_sign ( $params_sha1x16, $signature, $private_key, OPENSSL_ALGO_SHA1 );
        if ( $sign_falg )
        {
            $signature_base64 = base64_encode ( $signature );
            $params ['signature'] = $signature_base64;
        }
    }

    /**
     * 讲数组转换为string
     *
     * @param $para 数组
     * @param $sort 是否需要排序
     * @param $encode 是否需要URL编码
     * @return string
     */
    private static function createLinkString( $para, $sort, $encode )
    {
        if( $para == NULL || !is_array( $para ))
            return "";

        $linkString = "";
        if ( $sort )
        {
            $para = self::argSort ( $para );
        }
        while ( list( $key, $value ) = each ( $para ))
        {
            if ( $encode )
            {
                $value = urlencode ( $value );
            }
            $linkString .= $key . "=" . $value . "&";
        }
        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );

        return $linkString;
    }
    /**
     * 返回(签名)证书私钥 -
     *
     * @return unknown
     */
    private static function getPrivateKey( $cert_path, $cert_pwd ) {
        $pkcs12 = file_get_contents ( $cert_path );
        openssl_pkcs12_read ( $pkcs12, $certs, $cert_pwd );
        return $certs ['pkey'];
    }

    /**
     * 对数组排序
     * @param $para
     * @return mixed
     */
    private static function argSort( $para )
    {
        ksort ( $para );
        reset ( $para );
        return $para;
    }
}