<?php
if (! defined('CHECKPOSTANDGET')) { // ֻ����һ��
    define('CHECKPOSTANDGET', true);
    // get���ع���
    $getfilter = "<[^>]*?=[^>]*?&#[^>]*?>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\()|<[^>]*?\\b(onerror|onmousemove|onload|onclick|onmouseover)\\b[^>]*?>|^\\+\\/v(8|9)|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
    // post���ع���
    $postfilter = "<[^>]*?=[^>]*?&#[^>]*?>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\()|<[^>]*?\\b(onerror|onmousemove|onload|onclick|onmouseover)\\b[^>]*?>|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
    // cookie���ع���
    $cookiefilter = "\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";

    function slog($logs)
    {
        $toppath = LOG_PATH . 'xssattact_' . APP_NAME . '' . date('Ymd') . '.htm';
        $Ts = fopen($toppath, "a+");
        fputs($Ts, $logs . "\r\n");
        fclose($Ts);
    }

    function StopAttack($StrFiltKey, $StrFiltValue, $ArrFiltReq)
    {
        if (is_array($StrFiltValue)) {
            $StrFiltValue = implode($StrFiltValue);
        }
        if (preg_match("/" . $ArrFiltReq . "/is", $StrFiltValue) == 1) {
            slog(
                "<br><br>����IP: " . $_SERVER["REMOTE_ADDR"] . "<br>����ʱ��: " .
                     strftime("%Y-%m-%d %H:%M:%S") . "<br>����ҳ��:" .
                     $_SERVER["PHP_SELF"] . $gma . "<br>�ύ��ʽ: " .
                     $_SERVER["REQUEST_METHOD"] . "<br>�ύ����: " . $StrFiltKey .
                     "<br>�ύ����: " . $StrFiltValue);
            print "input param Illegal!";
            exit();
        }
    }

    function StopAttackStart($g, $p, $c)
    {
        // $ArrPGC=array_merge($_GET,$_POST,$_COOKIE);
        foreach ($_GET as $key => $value) {
            StopAttack($key, $value, $g);
        }
        foreach ($_POST as $key => $value) {
            StopAttack($key, $value, $p);
        }
        foreach ($_COOKIE as $key => $value) {
            StopAttack($key, $value, $c);
        }
    }
    StopAttackStart($getfilter, $postfilter, $cookiefilter);
}