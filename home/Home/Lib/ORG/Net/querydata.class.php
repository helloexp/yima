<?php

/**
 * 数据查询 $downcolumns_arr = array('a','b'); $jointable_arr = array(
 * 'a'=>array('table'=>'tpos_trace'),
 * 'b'=>array('table'=>'tshop_register','on'=>'b.shop_id=a.shop_id'),
 * 'c'=>array('table'=>'tpos_register','join'=>'b','on'=>'c.pos_id=b.pos_id'),
 * ); $columns_info = array( 'c1'=> //数组下标 array('join'=>'a', //表名
 * 'column'=>'SHOP_NODE_ID', //字段英文名 'name'=>'终端机构码', //字段显示中文名 'format'=>'<a
 * href="[_VALUE_]">[_VALUE_ORG_XXX]</a>', //显示格式
 * 'callback'=>array('fun_name',array('_VALUE_','其它参数')), //回调函数 'count'=>true,
 * //是否统计 'sort'=>$sort_link, //排序链接(页面显示用) 'nofilter'=>true, //不过滤标签
 * 'relation_column'=>'c1', //获取关联字段 ),
 * 'c2'=>array('join'=>'b','column'=>'SHOP_NAME','name'=>'商户名称'),
 * 'c3'=>array('join'=>'c','column'=>'POS_NAME','name'=>'终端名称'), ); $sql =
 * "select [COLUMNS] from TPOS_TRACE a [LEFT_JOIN] where $wh";
 * print_r(QueryData::getCustomInfo($sql,$downcolumns_arr,$columns_info,$jointable_arr))
 */
class QueryData {

    function getCustomJoin($join, $jointable_arr, &$joined_table = array()) {
        if (! array_key_exists($join, $jointable_arr))
            return '';
        $joinarr = $jointable_arr[$join];
        if (! in_array($join, $joined_table)) {
            $joined_table[] = $join;
            if (array_key_exists('join', $joinarr)) {
                $left_join = self::getCustomJoin($joinarr['join'], 
                    $jointable_arr, $joined_table);
            }
            if ($joinarr['on'])
                $left_join .= ' left join ' . $joinarr['table'] . ' ' . $join .
                     ' on ' . $joinarr['on'];
        }
        return $left_join;
    }

    function getCustomInfo($sql, $downcolumns_arr, $columns_info, $jointable_arr, 
        $group_flag = false) {
        if (! $downcolumns_arr)
            $downcolumns_arr = array();
        $joined_table = array();
        $columns = array();
        $left_join = '';
        $cols_info = array();
        $group_by = array();
        /*
         * foreach($downcolumns_arr as $column) {
         * if(!array_key_exists($column,$columns_info)) continue; $colarr =
         * $columns_info[$column];
         * if(!array_key_exists($colarr['join'],$jointable_arr)) continue;
         * $joinarr = $jointable_arr[$colarr['join']]; $new_column =
         * $colarr['column'] ? $colarr['column'].' "'.$column.'"' : $column;
         * $columns[$new_column] = $colarr['join'].'.'.$new_column; $left_join
         * .= self::getCustomJoin($colarr['join'],$jointable_arr,$joined_table);
         * $cols_info[$column] = $colarr; }
         */
        
        foreach ($columns_info as $column => $colarr) {
            // 计算是否为选中字段
            if (array_search((string) $column, $downcolumns_arr, true) === false)
                continue;
                
                // 获取当前字段
                // $colarr = $columns_info[$column];
            if (! array_key_exists($colarr['join'], $jointable_arr))
                continue;
            $joinarr = $jointable_arr[$colarr['join']];
            $new_column = $colarr['column'] ? $colarr['column'] . ' "' . $column .
                 '"' : $column;
            $columns[$column] = $colarr['join'] . '.' . $new_column;
            $left_join .= self::getCustomJoin($colarr['join'], $jointable_arr, 
                $joined_table);
            $cols_info[$column] = $colarr;
            
            // 获取关联字段
            $relation_column = $colarr['relation_column'];
            if ($relation_column) {
                $relation_column = explode(',', $relation_column);
                foreach ($relation_column as $column_2) {
                    if (! array_key_exists($column_2, $columns_info))
                        continue;
                    $colarr = $columns_info[$column_2];
                    if (! array_key_exists($colarr['join'], $jointable_arr))
                        continue;
                    $joinarr = $jointable_arr[$colarr['join']];
                    $new_column = $colarr['column'] ? $colarr['column'] . ' "' .
                         $column_2 . '"' : $column_2;
                    $columns[$column_2] = $colarr['join'] . '.' . $new_column;
                    $left_join .= self::getCustomJoin($colarr['join'], 
                        $jointable_arr, $joined_table);
                }
            }
            
            // 获取group字段
            $group = $colarr['group'];
            $new_column = $colarr['join'] . '.' .
                 ($colarr['column'] ? $colarr['column'] : $column);
            if ($group_flag === TRUE) {
                if ($group != '') {
                    $columns[$column] = $group . '(' . $new_column . ') as ' .
                         $column;
                } else {
                    $group_by[$colarr['column']] = $new_column;
                }
            }
        }
        // 如果传入参数不是以select开头，则凑成SQL语句
        if (strtolower(substr($sql, 0, 6)) != 'select') {
            list ($key, $first_table) = each($jointable_arr);
            $sql = 'select [COLUMNS] from (' . $first_table['table'] . ') ' .
                 $key . ' [LEFT_JOIN] ' . $sql;
        }
        $columns = implode(',', $columns);
        $sql = str_replace(
            array(
                '[COLUMNS]', 
                '[LEFT_JOIN]'), 
            array(
                $columns, 
                $left_join), $sql);
        // 如果有group语句
        if ($group_by) {
            $sql .= ' group by ' . implode(',', $group_by);
        }
        return array(
            'sql' => $sql, 
            'columns' => $cols_info);
    }
    // 处理过滤数组的值
    public static function array_value_filter($array, $column_arr) {
        $_array = $array;
        //初始化变量
        $callback = array();
        if (! $column_arr) {
            $column_arr = array();
            foreach ($array as $column => $value) {
                $column_arr[$column] = $column;
            }
        }
        foreach ($column_arr as $column => $column_info) {
            if (! is_array($column_info))
                $column_info = array(
                    'name' => $column_info,
                    'callback' => '',
                    'nofilter' => '',
                    'format' => ''
                    );
            $value = & $array[$column];
            if (is_array($column_info)) {
                if ($column_info['callback'] != '') {
                    $callback = $column_info['callback'];
                    if (is_string($callback))
                        $callback = array(
                            $callback, 
                            null);
                    $function = $callback[0];
                    if (function_exists($function)) {
                        $params = $callback[1];
                        if (is_string($params))
                            $params = array(
                                $params);
                        foreach ($params as & $tmpval) {
                            $value_flag = ! is_string($tmpval) ? FALSE : strpos(
                                $tmpval, '_VALUE_ORG_');
                            if ($value_flag === 0) {
                                $new_column = substr($tmpval, 11, 
                                    strlen($tmpval));
                                $tmpval = $new_column ? $_array[$new_column] : $value;
                            } else {
                                $value_flag = ! is_string($tmpval) ? FALSE : strpos(
                                    $tmpval, '_VALUE_');
                                if ($value_flag === 0) {
                                    $new_column = substr($tmpval, 7, 
                                        strlen($tmpval));
                                    $tmpval = $new_column ? $array[$new_column] : $value;
                                }
                            }
                        }
                        $value = call_user_func_array($function, $params);
                    }
                }
            }
            
            // 是否不需要过滤
            if ($column_info['nofilter'] == '') {
                $value = (strpos($value, '<') === false &&
                     strpos($value, '>') === false) ? $value : str_replace(
                        array(
                            '<', 
                            '>'), 
                        array(
                            '＜', 
                            '＞'), $value);
            }
            
            // 格式化处理
            $format = $column_info['format'];
            if ($format) {
                preg_match_all('/\[([A-Z_].*)\]/isU', $format, $match);
                foreach ($match[1] as $val) {
                    $org_arr = 'array';
                    if (strpos($val, '_VALUE_ORG_') === 0) {
                        $new_val = substr($val, 11);
                        $new_val = $new_val ? $new_val : $column;
                        $org_arr = '_array';
                    } elseif (strpos($val, '_VALUE_') === 0) {
                        $new_val = substr($val, 7);
                        $new_val = $new_val ? $new_val : $column;
                    }
                    $format = str_replace('[' . $val . ']', 
                        ${$org_arr}[$new_val], $format);
                }
                $value = $format;
            }
        }
        return $array;
    }

    /*
     * $column_arr = array( 'b'=>'字段2', 'a'=>'字段1', 'c'=>array( 'name'=>'字段3',
     * //'info'=>array('1'=>'aa','2'=>'bb','3'=>'cc',null=>'no'),
     * 'callback'=>array('abc',array('_VALUE_')), ), 'd'=>'字段5' ); $data_arr =
     * array( array('a'=>1,'b'=>2,'c'=>'1'), array('a'=>11,'b'=>22,'c'=>'2'),
     * array('a'=>111,'b'=>222,'c'=>'3'), );
     */
    public static function downloadData($data, $column_arr = null, &$db = null, $dowhere = "", 
        $xlsFilename = null, $isReturn) {
        // $filename = iconv('utf-8', 'gb2312', $filename);
        //set_magic_quotes_runtime(0);
        define('TEMPS_PATH', C('DOWN_TEMP'));
        $nowtime = $nowtime = time();
        $xlsNowtime = is_null($xlsFilename) ? time() : $xlsFilename;
        $tmpfilename = "" . $xlsNowtime . ".xls";
        $tmpzipfilename = $nowtime . ".zip";
        $tmpfilename = TEMPS_PATH . $tmpfilename;
        $tmpzipfilename = TEMPS_PATH . $tmpzipfilename;
        $fp = fopen($tmpfilename, 'wb');
        if (! $fp) {
            return false;
        }
        // 附加的行 字段规则 '_ADDROW_COUNT' => array('name'=>'总计','value'=>'xxxx');
        $add_rows = array();
        $add_top = array();
        if ($column_arr) {
            foreach ($column_arr as $column => $column_info) {
                // 添加底行
                if (strpos($column, '_ADDROW_') === 0) {
                    // 记录要添加的行数据
                    $add_rows[] = '<Row><Cell ss:MergeAcross="{MergeAcross}"><Data ss:Type="String">' .
                         $column_info['name'] . $column_info['value'] .
                         '</Data></Cell></Row>';
                    // 删掉此变量
                    unset($column_arr[$column]);
                }
                
                // 添加顶部
                if (strpos($column, '_ADDTOP_') === 0) {
                    $top_str = '<Row><Cell ss:MergeAcross="{MergeAcross}"';
                    if (empty($add_top))
                        $top_str .= ' ss:StyleID="top"';
                    $top_str .= '><Data ss:Type="String">' .
                         $column_info['value'] . '</Data></Cell></Row>';
                    
                    $add_top[] = $top_str;
                    // 删掉此变量
                    unset($column_arr[$column]);
                }
            }
        }
        $add_rows = str_replace('{MergeAcross}', count($column_arr) - 1, 
            implode("\n", $add_rows));
        $add_top = str_replace('{MergeAcross}', count($column_arr) - 1, 
            implode("\n", $add_top));
        // 结束附加的行
        
        $excel_date = date('YmdHis');
        $contents = '
<?xml version="1.0" encoding="utf8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Author>IMAGECO</Author>
  <LastAuthor>IMAGECO DEVELOP</LastAuthor>
  <Created>' . $excel_date . '</Created>
  <Company>IMAGECO</Company>
  <Version>11.9999</Version>
 </DocumentProperties>
 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
  <WindowHeight>9450</WindowHeight>
  <WindowWidth>16020</WindowWidth>
  <WindowTopX>0</WindowTopX>
  <WindowTopY>60</WindowTopY>
  <ProtectStructure>False</ProtectStructure>
  <ProtectWindows>False</ProtectWindows>
 </ExcelWorkbook>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Center"/>
   <Borders/>
   <Font ss:FontName="宋体" x:CharSet="134" ss:Size="12"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="top">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Interior ss:Color="#C0C0C0" ss:Pattern="Solid"/>
  </Style>
 </Styles>
';
        
        if (is_string($data)) {
            $data = $db->query($data);
            if (! $data)
                unlink($tmpfilename);
        }
        
        $cut_sheet_count = 60000;
        $cut_sheet_index = 0;
        $dataLen = count($data);
        while (1) {
            if($cut_sheet_index >= $dataLen)
                break;

            $contents .= '<Worksheet ss:Name="Sheet' .
                 (($cut_sheet_index / $cut_sheet_count) + 1) . '">' . "\n" .
                 '<Table ss:ExpandedColumnCount="1000" ss:ExpandedRowCount="70000" x:FullColumns="1" x:FullRows="1" ss:DefaultColumnWidth="54" ss:DefaultRowHeight="14.25">' .
                 "\n";
            
            // 加上头部
            if ($add_top != "") {
                $contents .= $add_top . "\n";
            }
            
            $temp_arr = array();
            if ($column_arr) {
                foreach ($column_arr as $column => $column_info) {
                    if (! is_array($column_info)) {
                        $column_name = $column_info;
                    } else {
                        $column_name = $column_info['name'];
                    }
                    $temp_arr[] = $column_name;
                }
                $contents .= '<Row><Cell><Data ss:Type="String">' .
                     implode("</Data></Cell>\n<Cell><Data ss:Type=\"String\">", 
                        $temp_arr) . '</Data></Cell></Row>';
                $contents .= "\n";
                fwrite($fp, $contents);
                $contents = '';
            }
            $i = 0;
            $cut_sheet_break = true;
            //foreach ($data as $v) {
            do {
                $v = $data[$cut_sheet_index];
                $temp_arr  = self::array_value_filter($v, $column_arr);
                $_temp_arr = [];
                foreach ($column_arr as $key => $val) {
                    $_temp_arr[] = $temp_arr[$key];
                }
                // print_r($_temp_arr);
                // exit;
                $contents .= '<Row><Cell><Data ss:Type="String">' . implode("</Data></Cell>\n<Cell><Data ss:Type=\"String\">",
                        $_temp_arr) . '</Data></Cell></Row>';
                $contents .= "\n";
                if ($i > 0 && $i % 100 == 0) {
                    fwrite($fp, $contents);
                    $contents = '';
                }
                $i++;
                $cut_sheet_index++;
                if ($cut_sheet_index % $cut_sheet_count === 0) {
                    $cut_sheet_break = false;
                    break;
                }
            }while($cut_sheet_index < $dataLen);
            //}
            
            // 加上附加的行
            if ($add_rows != "") {
                $contents .= $add_rows . "\n";
            }
            $contents .= <<<EOT
	</Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
  </WorksheetOptions>
 </Worksheet>
EOT;
            if ($cut_sheet_break)
                break;
            else
                continue;
        }
        
        $contents .= <<<EOT
</Workbook>
EOT;
        fwrite($fp, $contents);
        $contents = '';
        fclose($fp);
        // 开始生成zip文件//
        import('@.ORG.Net.Zip', '', '.php') or die('导入包失败');
        if (false === $isReturn) {
            $class_zip = new FINE_Zip();
            $zip = $class_zip->zip_file($tmpzipfilename); // 新建立一个zip的类
            $zip->set_options(
                array(
                    'storepaths' => 0, 
                    'inmemory' => 1));
            $zip->add_files($tmpfilename);
            $zip->create_archive();
            $zip->download_file();
            unlink($tmpfilename);
            @unlink($tmpzipfilename);
            exit();
        }
        return true;
    }
}
?>
