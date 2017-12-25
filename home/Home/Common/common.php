<?php
if (file_exists(__DIR__ . '/commonNew.php')) {
    include (__DIR__ . '/commonNew.php');
    
    include (__DIR__ . '/commonNeedDelete.php');
    
    include (__DIR__ . '/commonNeedImprove.php');
    
    include (__DIR__ . '/commonNeedMerge.php');
    
    include (__DIR__ . '/commonNeedMove.php');
} else {
    include (APP_PATH . '/Common/commonNew.php');
    
    include (APP_PATH . '/Common/commonNeedDelete.php');
    
    include (APP_PATH . '/Common/commonNeedImprove.php');
    
    include (APP_PATH . '/Common/commonNeedMerge.php');
    
    include (APP_PATH . '/Common/commonNeedMove.php');
}

/**
 * 公用打印函数
 * @param  array  $arr [description]
 * @return [arr]      [description]
 */
function p($arr=array()){
    if(is_array($arr)){
        echo '<pre>';
        print_r($arr);
        echo '</pre>';
    }else{
        echo 'not a array';
    }
}

/**
 * 打印分隔符
 * @param  integer $len [description]
 * @return [type]       [description]
 */
function p_line($len=0){
    for($i=0;$i<$len;$i++){
        echo "=";
    }
    echo '<br>';
}

function wl($content=''){
    $path="";
    echo APP_PATH.'Runtime/Logs/test.txt';
    $rs=file_put_contents($content,FILE_APPEND);
}
