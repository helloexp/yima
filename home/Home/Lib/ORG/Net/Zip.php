<?php

/*
 * -------------------------------------------------- | TAR/GZIP/BZIP2/ZIP
 * ARCHIVE CLASSES 2.1 | By Devin Doucette | Copyright (c) 2005 Devin Doucette |
 * Email: darksnoopy@shaw.ca +--------------------------------------------------
 * | Email bugs/suggestions to darksnoopy@shaw.ca
 * +-------------------------------------------------- | This script has been
 * created and released under | the GNU GPL and is free to use and redistribute
 * | only if this copyright statement is not removed
 * +--------------------------------------------------
 */
/*
 * //�÷�ʾ�� //����archive��� //����������ļ�
 * require_once("zipArchive/archive.php"); $test = new zip_file( $zipFileName );
 * // Create archive in disk $test->set_options( array( 'basedir' =>
 * dirname($modpath), 'inmemory' => 0,
 * //�����ڴ�ѹ��.����ֱ�Ӵ�ŵ�����.���Ҫѹ������,�����ѡ��Ϊ1 'recurse' => 1,
 * //�Ƿ�ѹ����Ŀ¼��resurse���ݹ����˼�� 'storepaths' => 1, //�Ƿ�洢Ŀ¼�ṹ����ѡ�ǡ�
 * 'overwrite' => 1, //�Ƿ񸲸� 'level' => 5 ,//ѹ���� 'name' => $zipFileName,
 * //ѹ��������ɵ��ļ����������ٴ����á�������Ϊ�˽�˵����ŷ������ġ� 'prepend' => "",
 * //δ֪ 'followlinks' => 0, //δ֪ 'method' => 1, //δ֪ 'sfx' => "", //��֪��ʲô��˼
 * 'type' => "zip", //��zip����tar...,�������ã�����Ϊ�˷����˵���������� 'comment'
 * => "" ) ); // Add files to archive,args can be array or a filename,and
 * support *.*,but all files must be under the basedir $files =
 * array($module_name.".php",$module_name.".xml","templates/".$module_name.".html");
 * //���Խ��ļ��������г����ӽ�ȥ�������ļ���������basedir�£��ļ���֧��*.*��ʾѹ��ȫ����
 * //$test->add_files($files); //��$files��������ļ���
 * $test->add_files("diruti"); //��Ŀ¼diruti�����diruti���ļ�����ô���Ǽ��ļ���
 * //$test->add_files(array("images/*.jp*g", "images/*.gif")); // Store all exe
 * files in bin without compression //��ѹ���洢 //$test->store_files("bin/*.exe");
 * // ��ʽд����� $test->create_archive(); // Send archive to user for download
 * //�����ѡ�������ڴ��д����������ṩ���ء� //$test->download_file();
 */
class FINE_Zip {

    function zip_file($zip) {
        return new zip_file($zip);
    }

    function unzip_file($zip = null) {
        return new unzip_file();
    }

    function tar_file($zip) {
        return new tar_file($zip);
    }

    function bzip_file($zip) {
        return new bzip_file($zip);
    }

    function gzip_file($zip) {
        return new bzip_file($zip);
    }
}

class archive {

    function archive($name) {
        $this->options = array(
            'basedir' => ".", 
            'name' => $name, 
            'prepend' => "", 
            'inmemory' => 0, 
            'overwrite' => 0, 
            'recurse' => 1, 
            'storepaths' => 1, 
            'followlinks' => 0, 
            'level' => 3, 
            'method' => 1, 
            'sfx' => "", 
            'type' => "", 
            'comment' => "");
        $this->files = array();
        $this->exclude = array();
        $this->storeonly = array();
        $this->error = array();
    }

    function set_options($options) {
        foreach ($options as $key => $value)
            $this->options[$key] = $value;
        if (! empty($this->options['basedir'])) {
            $this->options['basedir'] = str_replace("\\", "/", 
                $this->options['basedir']);
            $this->options['basedir'] = preg_replace("/\/+/", "/", 
                $this->options['basedir']);
            $this->options['basedir'] = preg_replace("/\/$/", "", 
                $this->options['basedir']);
        }
        if (! empty($this->options['name'])) {
            $this->options['name'] = str_replace("\\", "/", 
                $this->options['name']);
            $this->options['name'] = preg_replace("/\/+/", "/", 
                $this->options['name']);
        }
        if (! empty($this->options['prepend'])) {
            $this->options['prepend'] = str_replace("\\", "/", 
                $this->options['prepend']);
            $this->options['prepend'] = preg_replace("/^(\.*\/+)+/", "", 
                $this->options['prepend']);
            $this->options['prepend'] = preg_replace("/\/+/", "/", 
                $this->options['prepend']);
            $this->options['prepend'] = preg_replace("/\/$/", "", 
                $this->options['prepend']) . "/";
        }
    }

    function create_archive() {
        $this->make_list();
        
        if ($this->options['inmemory'] == 0) {
            $pwd = getcwd();
            chdir($this->options['basedir']);
            if ($this->options['overwrite'] == 0 && file_exists(
                $this->options['name'] . ($this->options['type'] == "gzip" ||
                     $this->options['type'] == "bzip" ? ".tmp" : ""))) {
                $this->error[] = "File {$this->options['name']} already exists.";
                chdir($pwd);
                return 0;
            } else if ($this->archive = @fopen(
                $this->options['name'] . ($this->options['type'] == "gzip" ||
                     $this->options['type'] == "bzip" ? ".tmp" : ""), "wb+"))
                chdir($pwd);
            else {
                $this->error[] = "Could not open {$this->options['name']} for writing.";
                chdir($pwd);
                return 0;
            }
        } else
            $this->archive = "";
        
        switch ($this->options['type']) {
            case "zip":
                if (! $this->create_zip()) {
                    $this->error[] = "Could not create zip file.";
                    return 0;
                }
                break;
            case "bzip":
                if (! $this->create_tar()) {
                    $this->error[] = "Could not create tar file.";
                    return 0;
                }
                if (! $this->create_bzip()) {
                    $this->error[] = "Could not create bzip2 file.";
                    return 0;
                }
                break;
            case "gzip":
                if (! $this->create_tar()) {
                    $this->error[] = "Could not create tar file.";
                    return 0;
                }
                if (! $this->create_gzip()) {
                    $this->error[] = "Could not create gzip file.";
                    return 0;
                }
                break;
            case "tar":
                if (! $this->create_tar()) {
                    $this->error[] = "Could not create tar file.";
                    return 0;
                }
        }
        
        if ($this->options['inmemory'] == 0) {
            fclose($this->archive);
            if ($this->options['type'] == "gzip" ||
                 $this->options['type'] == "bzip")
                unlink(
                    $this->options['basedir'] . "/" . $this->options['name'] .
                     ".tmp");
        }
    }

    function add_data($data) {
        if ($this->options['inmemory'] == 0)
            fwrite($this->archive, $data);
        else
            $this->archive .= $data;
    }

    function make_list() {
        if (! empty($this->exclude))
            foreach ($this->files as $key => $value)
                foreach ($this->exclude as $current)
                    if ($value['name'] == $current['name'])
                        unset($this->files[$key]);
        if (! empty($this->storeonly))
            foreach ($this->files as $key => $value)
                foreach ($this->storeonly as $current)
                    if ($value['name'] == $current['name'])
                        $this->files[$key]['method'] = 0;
        unset($this->exclude, $this->storeonly);
    }

    function add_files($list) {
        $temp = $this->list_files($list);
        foreach ($temp as $current)
            $this->files[] = $current;
    }

    function exclude_files($list) {
        $temp = $this->list_files($list);
        foreach ($temp as $current)
            $this->exclude[] = $current;
    }

    function store_files($list) {
        $temp = $this->list_files($list);
        foreach ($temp as $current)
            $this->storeonly[] = $current;
    }

    function list_files($list) {
        if (! is_array($list)) {
            $temp = $list;
            $list = array(
                $temp);
            unset($temp);
        }
        
        $files = array();
        
        $pwd = getcwd();
        chdir($this->options['basedir']);
        foreach ($list as $key => $current) {
            $current = str_replace("\\", "/", $current);
            $current = preg_replace("/\/+/", "/", $current);
            $current = preg_replace("/\/$/", "", $current);
            if (strstr($current, "*")) {
                $regex = preg_replace("/([\\\^\$\.\[\]\|\(\)\?\+\{\}\/])/", 
                    "\\\\\\1", $current);
                $regex = str_replace("*", ".*", $regex);
                $dir = strstr($current, "/") ? substr($current, 0, 
                    strrpos($current, "/")) : ".";
                $temp = $this->parse_dir($dir);
                foreach ($temp as $current2)
                    if (preg_match("/^{$regex}$/i", $current2['name']))
                        $files[] = $current2;
                unset($regex, $dir, $temp, $current);
            } else if (@is_dir($current)) {
                $temp = $this->parse_dir($current);
                foreach ($temp as $file)
                    $files[] = $file;
                unset($temp, $file);
            } else if (@file_exists($current)) {
                if ($this->options['storepaths'] == 0 && strstr($current, '/')) {
                    $name2 = substr($current, strrpos($current, '/') + 1);
                } else {
                    $name2 = $current;
                }
                $name2 = $this->options['prepend'] . $name2;
                $files[] = array(
                    'name' => $current, 
                    'name2' => is_string($key) ? $key : $name2, 
                    'type' => @is_link($current) &&
                         $this->options['followlinks'] == 0 ? 2 : 0, 
                        'ext' => substr($current, strrpos($current, ".")), 
                        'stat' => stat($current));
            } else {
                echo "other error ";
            }
        }
        chdir($pwd);
        unset($current, $pwd);
        usort($files, 
            array(
                "archive", 
                "sort_files"));
        // prt($files); //die;
        return $files;
    }

    function parse_dir($dirname) {
        if ($this->options['storepaths'] == 1 &&
             ! preg_match("/^(\.+\/*)+$/", $dirname)) {
            $files = array(
                array(
                    'name' => $dirname, 
                    'name2' => $this->options['prepend'] . preg_replace(
                        "/(\.+\/+)+/", "", 
                        ($this->options['storepaths'] == 0 &&
                         strstr($dirname, "/")) ? substr($dirname, 
                            strrpos($dirname, "/") + 1) : $dirname), 
                    'type' => 5, 
                    'stat' => stat($dirname)));
        } else
            $files = array();
        $dir = @opendir($dirname);
        
        while ($file = @readdir($dir)) {
            $fullname = $dirname . "/" . $file;
            if ($file == "." || $file == "..")
                continue;
            else if (@is_dir($fullname)) {
                if (empty($this->options['recurse']))
                    continue;
                $temp = $this->parse_dir($fullname);
                foreach ($temp as $file2)
                    $files[] = $file2;
            } else if (@file_exists($fullname))
                $files[] = array(
                    'name' => $fullname, 
                    'name2' => $this->options['prepend'] . preg_replace(
                        "/(\.+\/+)+/", "", 
                        ($this->options['storepaths'] == 0 &&
                             strstr($fullname, "/")) ? substr($fullname, 
                                strrpos($fullname, "/") + 1) : $fullname), 
                    'type' => @is_link($fullname) &&
                     $this->options['followlinks'] == 0 ? 2 : 0, 
                    'ext' => substr($file, strrpos($file, ".")), 
                    'stat' => stat($fullname));
        }
        
        @closedir($dir);
        
        return $files;
    }

    function sort_files($a, $b) {
        if ($a['type'] != $b['type'])
            if ($a['type'] == 5 || $b['type'] == 2)
                return - 1;
            else if ($a['type'] == 2 || $b['type'] == 5)
                return 1;
            else if ($a['type'] == 5)
                return strcmp(strtolower($a['name']), strtolower($b['name']));
            else if ($a['ext'] != $b['ext'])
                return strcmp($a['ext'], $b['ext']);
            else if ($a['stat'][7] != $b['stat'][7])
                return $a['stat'][7] > $b['stat'][7] ? - 1 : 1;
            else
                return strcmp(strtolower($a['name']), strtolower($b['name']));
        return 0;
    }

    function download_file($filename = null) {
        if ($this->options['inmemory'] == 0) {
            $this->error[] = "Can only use download_file() if archive is in memory. Redirect to file otherwise, it is faster.";
            return;
        }
        /*
         * switch ($this->options['type']) { case "zip": header("Content-Type:
         * application/zip"); break; case "bzip": header("Content-Type:
         * application/x-bzip2"); break; case "gzip": header("Content-Type:
         * application/x-gzip"); break; case "tar": header("Content-Type:
         * application/x-tar"); } $header = "Content-Disposition: attachment;
         * filename=\""; $header .= strstr($this->options['name'], "/") ?
         * substr($this->options['name'], strrpos($this->options['name'], "/") +
         * 1) : $this->options['name']; $header .= "\""; header($header);
         * header("Content-Length: " . strlen($this->archive));
         * header("Content-Transfer-Encoding: binary"); header("Cache-Control:
         * no-cache, must-revalidate, max-age=60"); header("Expires: Sat, 01 Jan
         * 2000 12:00:00 GMT");
         */
        $disposition = strpos($HTTP_SERVER_VARS['HTTP_USER_AGENT'], 'MSIE') ? 'attachment' : 'inline';
        header('Content-type: application/octet-stream');
        $filename = $filename ? $filename : (strstr($this->options['name'], '/') ? substr(
            $this->options['name'], strrpos($this->options['name'], '/') + 1) : $this->options['name']);
        header(
            'Content-Disposition: ' . $disposition . '; filename=' . $filename);
        header('Pragma: public');
        header('Cache-Control: max-age=0');
        print($this->archive);
    }

    function get_archive() {
        return $this->archive;
    }
}

class tar_file extends archive {

    function tar_file($name) {
        $this->archive($name);
        $this->options['type'] = "tar";
    }

    function create_tar() {
        $pwd = getcwd();
        chdir($this->options['basedir']);
        
        foreach ($this->files as $current) {
            if ($current['name'] == $this->options['name'])
                continue;
            if (strlen($current['name2']) > 99) {
                $path = substr($current['name2'], 0, 
                    strpos($current['name2'], "/", 
                        strlen($current['name2']) - 100) + 1);
                $current['name2'] = substr($current['name2'], strlen($path));
                if (strlen($path) > 154 || strlen($current['name2']) > 99) {
                    $this->error[] = "Could not add {$path}{$current['name2']} to archive because the filename is too long.";
                    continue;
                }
            }
            $block = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12", 
                $current['name2'], sprintf("%07o", $current['stat'][2]), 
                sprintf("%07o", $current['stat'][4]), 
                sprintf("%07o", $current['stat'][5]), 
                sprintf("%011o", 
                    $current['type'] == 2 ? 0 : $current['stat'][7]), 
                sprintf("%011o", $current['stat'][9]), "        ", 
                $current['type'], 
                $current['type'] == 2 ? @readlink($current['name']) : "", 
                "ustar ", " ", "Unknown", "Unknown", "", "", 
                ! empty($path) ? $path : "", "");
            
            $checksum = 0;
            for ($i = 0; $i < 512; $i ++)
                $checksum += ord(substr($block, $i, 1));
            $checksum = pack("a8", sprintf("%07o", $checksum));
            $block = substr_replace($block, $checksum, 148, 8);
            
            if ($current['type'] == 2 || $current['stat'][7] == 0)
                $this->add_data($block);
            else if ($fp = @fopen($current['name'], "rb")) {
                $this->add_data($block);
                while ($temp = fread($fp, 1048576))
                    $this->add_data($temp);
                if ($current['stat'][7] % 512 > 0) {
                    $temp = "";
                    for ($i = 0; $i < 512 - $current['stat'][7] % 512; $i ++)
                        $temp .= "\0";
                    $this->add_data($temp);
                }
                fclose($fp);
            } else
                $this->error[] = "Could not open file {$current['name']} for reading. It was not added.";
        }
        
        $this->add_data(pack("a1024", ""));
        
        chdir($pwd);
        
        return 1;
    }

    function extract_files() {
        $pwd = getcwd();
        chdir($this->options['basedir']);
        
        if ($fp = $this->open_archive()) {
            if ($this->options['inmemory'] == 1)
                $this->files = array();
            
            while ($block = fread($fp, 512)) {
                $temp = unpack(
                    "a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100symlink/a6magic/a2temp/a32temp/a32temp/a8temp/a8temp/a155prefix/a12temp", 
                    $block);
                $file = array(
                    'name' => $temp['prefix'] . $temp['name'], 
                    'stat' => array(
                        2 => $temp['mode'], 
                        4 => octdec($temp['uid']), 
                        5 => octdec($temp['gid']), 
                        7 => octdec($temp['size']), 
                        9 => octdec($temp['mtime'])), 
                    'checksum' => octdec($temp['checksum']), 
                    'type' => $temp['type'], 
                    'magic' => $temp['magic']);
                if ($file['checksum'] == 0x00000000)
                    break;
                else if (substr($file['magic'], 0, 5) != "ustar") {
                    $this->error[] = "This script does not support extracting this type of tar file.";
                    break;
                }
                $block = substr_replace($block, "        ", 148, 8);
                $checksum = 0;
                for ($i = 0; $i < 512; $i ++)
                    $checksum += ord(substr($block, $i, 1));
                if ($file['checksum'] != $checksum)
                    $this->error[] = "Could not extract from {$this->options['name']}, it is corrupt.";
                
                if ($this->options['inmemory'] == 1) {
                    $file['data'] = fread($fp, $file['stat'][7]);
                    fread($fp, 
                        (512 - $file['stat'][7] % 512) == 512 ? 0 : (512 -
                             $file['stat'][7] % 512));
                    unset($file['checksum'], $file['magic']);
                    $this->files[] = $file;
                } else if ($file['type'] == 5) {
                    if (! is_dir($file['name']))
                        mkdir($file['name'], $file['stat'][2]);
                } else if ($this->options['overwrite'] == 0 &&
                     file_exists($file['name'])) {
                    $this->error[] = "{$file['name']} already exists.";
                    continue;
                } else if ($file['type'] == 2) {
                    symlink($temp['symlink'], $file['name']);
                    chmod($file['name'], $file['stat'][2]);
                } else if ($new = @fopen($file['name'], "wb")) {
                    fwrite($new, fread($fp, $file['stat'][7]));
                    fread($fp, 
                        (512 - $file['stat'][7] % 512) == 512 ? 0 : (512 -
                             $file['stat'][7] % 512));
                    fclose($new);
                    chmod($file['name'], $file['stat'][2]);
                } else {
                    $this->error[] = "Could not open {$file['name']} for writing.";
                    continue;
                }
                chown($file['name'], $file['stat'][4]);
                chgrp($file['name'], $file['stat'][5]);
                touch($file['name'], $file['stat'][9]);
                unset($file);
            }
        } else
            $this->error[] = "Could not open file {$this->options['name']}";
        
        chdir($pwd);
    }

    function open_archive() {
        return @fopen($this->options['name'], "rb");
    }
}

class gzip_file extends tar_file {

    function gzip_file($name) {
        $this->tar_file($name);
        $this->options['type'] = "gzip";
    }

    function create_gzip() {
        if ($this->options['inmemory'] == 0) {
            $pwd = getcwd();
            chdir($this->options['basedir']);
            if ($fp = gzopen($this->options['name'], 
                "wb{$this->options['level']}")) {
                fseek($this->archive, 0);
                while ($temp = fread($this->archive, 1048576))
                    gzwrite($fp, $temp);
                gzclose($fp);
                chdir($pwd);
            } else {
                $this->error[] = "Could not open {$this->options['name']} for writing.";
                chdir($pwd);
                return 0;
            }
        } else
            $this->archive = gzencode($this->archive, $this->options['level']);
        
        return 1;
    }

    function open_archive() {
        return @gzopen($this->options['name'], "rb");
    }
}

class bzip_file extends tar_file {

    function bzip_file($name) {
        $this->tar_file($name);
        $this->options['type'] = "bzip";
    }

    function create_bzip() {
        if ($this->options['inmemory'] == 0) {
            $pwd = getcwd();
            chdir($this->options['basedir']);
            if ($fp = bzopen($this->options['name'], "wb")) {
                fseek($this->archive, 0);
                while ($temp = fread($this->archive, 1048576))
                    bzwrite($fp, $temp);
                bzclose($fp);
                chdir($pwd);
            } else {
                $this->error[] = "Could not open {$this->options['name']} for writing.";
                chdir($pwd);
                return 0;
            }
        } else
            $this->archive = bzcompress($this->archive, $this->options['level']);
        
        return 1;
    }

    function open_archive() {
        return @bzopen($this->options['name'], "rb");
    }
}

class zip_file extends archive {

    function zip_file($name) {
        $this->archive($name);
        $this->options['type'] = 'zip';
    }

    function create_zip() {
        $files = 0;
        $offset = 0;
        $central = "";
        
        if (! empty($this->options['sfx']))
            if ($fp = @fopen($this->options['sfx'], "rb")) {
                $temp = fread($fp, filesize($this->options['sfx']));
                fclose($fp);
                $this->add_data($temp);
                $offset += strlen($temp);
                unset($temp);
            } else
                $this->error[] = "Could not open sfx module from {$this->options['sfx']}.";
        
        $pwd = getcwd();
        chdir($this->options['basedir']);
        
        foreach ($this->files as $current) {
            if ($current['name'] == $this->options['name'])
                continue;
            
            $timedate = explode(" ", date("Y n j G i s", $current['stat'][9]));
            $timedate = ($timedate[0] - 1980 << 25) | ($timedate[1] << 21) |
                 ($timedate[2] << 16) | ($timedate[3] << 11) |
                 ($timedate[4] << 5) | ($timedate[5]);
            
            $block = pack("VvvvV", 0x04034b50, 0x000A, 0x0000, 
                (isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, 
                $timedate);
            
            if ($current['stat'][7] == 0 && $current['type'] == 5) {
                $block .= pack("VVVvv", 0x00000000, 0x00000000, 0x00000000, 
                    strlen($current['name2']) + 1, 0x0000);
                $block .= $current['name2'] . "/";
                $this->add_data($block);
                $central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, 
                    $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000, 
                    (isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, 
                    $timedate, 0x00000000, 0x00000000, 0x00000000, 
                    strlen($current['name2']) + 1, 0x0000, 0x0000, 0x0000, 
                    0x0000, $current['type'] == 5 ? 0x00000010 : 0x00000000, 
                    $offset);
                $central .= $current['name2'] . "/";
                $files ++;
                $offset += (31 + strlen($current['name2']));
            } else if ($current['stat'][7] == 0) {
                $block .= pack("VVVvv", 0x00000000, 0x00000000, 0x00000000, 
                    strlen($current['name2']), 0x0000);
                $block .= $current['name2'];
                $this->add_data($block);
                $central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, 
                    $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000, 
                    (isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, 
                    $timedate, 0x00000000, 0x00000000, 0x00000000, 
                    strlen($current['name2']), 0x0000, 0x0000, 0x0000, 0x0000, 
                    $current['type'] == 5 ? 0x00000010 : 0x00000000, $offset);
                $central .= $current['name2'];
                $files ++;
                $offset += (30 + strlen($current['name2']));
            } else if ($fp = @fopen($current['name'], "rb")) {
                $temp = fread($fp, $current['stat'][7]);
                fclose($fp);
                $crc32 = crc32($temp);
                if (! isset($current['method']) && $this->options['method'] == 1) {
                    $temp = gzcompress($temp, $this->options['level']);
                    $size = strlen($temp) - 6;
                    $temp = substr($temp, 2, $size);
                } else
                    $size = strlen($temp);
                $block .= pack("VVVvv", $crc32, $size, $current['stat'][7], 
                    strlen($current['name2']), 0x0000);
                $block .= $current['name2'];
                $this->add_data($block);
                $this->add_data($temp);
                unset($temp);
                $central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, 
                    $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000, 
                    (isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, 
                    $timedate, $crc32, $size, $current['stat'][7], 
                    strlen($current['name2']), 0x0000, 0x0000, 0x0000, 0x0000, 
                    0x00000000, $offset);
                $central .= $current['name2'];
                $files ++;
                $offset += (30 + strlen($current['name2']) + $size);
            } else
                $this->error[] = "Could not open file {$current['name']} for reading. It was not added.";
        }
        
        $this->add_data($central);
        
        $this->add_data(
            pack("VvvvvVVv", 0x06054b50, 0x0000, 0x0000, $files, $files, 
                strlen($central), $offset, 
                ! empty($this->options['comment']) ? strlen(
                    $this->options['comment']) : 0x0000));
        
        if (! empty($this->options['comment']))
            $this->add_data($this->options['comment']);
        
        chdir($pwd);
        
        return 1;
    }
}
// ��ѹzip
class unzip_file {

    var $magic_quotes_flag;

    function __construct() {
        $this->magic_quotes_flag = get_magic_quotes_runtime();
        if ($this->magic_quotes_flag) {
            set_magic_quotes_runtime(0);
        }
    }

    function get_list($zip_name) {
        $zip = @fopen($zip_name, 'rb');
        if (! $zip)
            return (0);
        $centd = $this->ReadCentralDir($zip, $zip_name);
        @rewind($zip);
        @fseek($zip, $centd['offset']);
        for ($i = 0; $i < $centd['entries']; $i ++) {
            $header = $this->ReadCentralFileHeaders($zip);
            $header['index'] = $i;
            $info['filename'] = $header['filename'];
            $info['stored_filename'] = $header['stored_filename'];
            $info['size'] = $header['size'];
            $info['compressed_size'] = $header['compressed_size'];
            $info['crc'] = strtoupper(dechex($header['crc']));
            $info['mtime'] = $header['mtime'];
            $info['comment'] = $header['comment'];
            $info['folder'] = ($header['external'] == 0x41FF0010 ||
                 $header['external'] == 16) ? 1 : 0;
            $info['index'] = $header['index'];
            $info['status'] = $header['status'];
            $ret[] = $info;
            unset($header);
        }
        return $ret;
    }

    function unzip($zn, $to = './', $index = Array(-1), $dirflag = 1) {
        $ok = 0;
        $zip = @fopen($zn, 'rb');
        if (! $zip)
            return (- 1);
        
        $cdir = $this->ReadCentralDir($zip, $zn);
        $pos_entry = $cdir['offset'];
        if (! is_array($index)) {
            $index = array(
                $index);
        }
        foreach ($index as $idx) {
            if (intval($idx) != $idx || $idx > $cdir['entries'])
                return (- 1);
        }
        $dirflag = $cdir['entries'] > 1 ? 1 : $dirflag;
        for ($i = 0; $i < $cdir['entries']; $i ++) {
            fseek($zip, $pos_entry);
            $header = $this->ReadCentralFileHeaders($zip);
            $header['index'] = $i;
            $pos_entry = ftell($zip);
            rewind($zip);
            fseek($zip, $header['offset']);
            if (in_array('-1', $index) || in_array($i, $index)) {
                $stat[$header['filename']] = $this->ExtractFile($header, $to, 
                    $zip, $dirflag);
            }
        }
        fclose($zip);
        set_magic_quotes_runtime($this->magic_quotes_flag);
        return $stat;
    }

    function setPos(&$header, &$zip) {
        $pos_entry = ftell($zip) + $header['filename_len'] + $header['extra_len'] +
             30;
        fseek($zip, $pos_entry);
    }

    function ReadFileHeader($zip) {
        $binary_data = fread($zip, 30);
        $data = unpack(
            'vchk/vid/vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', 
            $binary_data);
        $header['filename'] = fread($zip, $data['filename_len']);
        if ($data['extra_len'] != 0) {
            $header['extra'] = fread($zip, $data['extra_len']);
        } else {
            $header['extra'] = '';
        }
        
        $header['compression'] = $data['compression'];
        $header['size'] = $data['size'];
        $header['compressed_size'] = $data['compressed_size'];
        $header['crc'] = $data['crc'];
        $header['flag'] = $data['flag'];
        $header['mdate'] = $data['mdate'];
        $header['mtime'] = $data['mtime'];
        if ($header['mdate'] && $header['mtime']) {
            $hour = ($header['mtime'] & 0xF800) >> 11;
            $minute = ($header['mtime'] & 0x07E0) >> 5;
            $seconde = ($header['mtime'] & 0x001F) * 2;
            $year = (($header['mdate'] & 0xFE00) >> 9) + 1980;
            $month = ($header['mdate'] & 0x01E0) >> 5;
            $day = $header['mdate'] & 0x001F;
            $header['mtime'] = mktime($hour, $minute, $seconde, $month, $day, 
                $year);
        } else {
            $header['mtime'] = time();
        }
        $header['stored_filename'] = $header['filename'];
        $header['status'] = 'ok';
        return $header;
    }

    function ReadCentralFileHeaders($zip) {
        $binary_data = fread($zip, 46);
        $header = unpack(
            'vchkid/vid/vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', 
            $binary_data);
        if ($header['filename_len'] != 0)
            $header['filename'] = fread($zip, $header['filename_len']);
        else
            $header['filename'] = '';
        if ($header['extra_len'] != 0)
            $header['extra'] = fread($zip, $header['extra_len']);
        else
            $header['extra'] = '';
        if ($header['comment_len'] != 0)
            $header['comment'] = fread($zip, $header['comment_len']);
        else
            $header['comment'] = '';
        if ($header['mdate'] && $header['mtime']) {
            $hour = ($header['mtime'] & 0xF800) >> 11;
            $minute = ($header['mtime'] & 0x07E0) >> 5;
            $seconde = ($header['mtime'] & 0x001F) * 2;
            $year = (($header['mdate'] & 0xFE00) >> 9) + 1980;
            $month = ($header['mdate'] & 0x01E0) >> 5;
            $day = $header['mdate'] & 0x001F;
            $header['mtime'] = mktime($hour, $minute, $seconde, $month, $day, 
                $year);
        } else {
            $header['mtime'] = time();
        }
        $header['stored_filename'] = $header['filename'];
        $header['status'] = 'ok';
        if (substr($header['filename'], - 1) == '/')
            $header['external'] = 0x41FF0010;
        return $header;
    }

    function ReadCentralDir($zip, $zip_name) {
        $size = filesize($zip_name);
        if ($size < 277)
            $maximum_size = $size;
        else
            $maximum_size = 277;
        
        @fseek($zip, $size - $maximum_size);
        $pos = ftell($zip);
        $bytes = 0x00000000;
        while ($pos < $size) {
            $byte = @fread($zip, 1);
            $bytes = ($bytes << 8) | Ord($byte);
            if ($bytes == 0x504b0506) {
                $pos ++;
                break;
            }
            $pos ++;
        }
        $data = unpack(
            'vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', 
            fread($zip, 18));
        if ($data['comment_size'] != 0)
            $centd['comment'] = fread($zip, $data['comment_size']);
        else
            $centd['comment'] = '';
        $centd['entries'] = $data['entries'];
        $centd['disk_entries'] = $data['disk_entries'];
        $centd['offset'] = $data['offset'];
        $centd['disk_start'] = $data['disk_start'];
        $centd['size'] = $data['size'];
        $centd['disk'] = $data['disk'];
        return $centd;
    }

    function ExtractFile($header, $to, $zip, $dirflag = 1) {
        $this->setPos($header, $zip);
        // ���Ҫ����Ŀ¼
        if ($dirflag) {
            if (substr($to, - 1) != '/')
                $to .= '/';
            if (! @is_dir($to))
                @mkdir($to, 0777);
            $pth = explode('/', dirname($header['filename']));
            $dir = '';
            foreach ($pth as $dirname) {
                $dir .= $dirname . '/';
                if (! is_dir($to . $dir)) {
                    mkdir($to . $dir, 0777);
                }
            }
            $zip_file_name = $to . $header['filename'];
        } else {
            $zip_file_name = $to;
        }
        if ($header['external'] != 0x41FF0010 && $header['external'] != 16) {
            if ($header['compression'] == 0) {
                $fp = @fopen($zip_file_name, 'wb');
                if (! $fp)
                    return (- 1);
                $size = $header['compressed_size'];
                while ($size != 0) {
                    $read_size = ($size < 2048 ? $size : 2048);
                    $buffer = fread($zip, $read_size);
                    $binary_data = pack('a' . $read_size, $buffer);
                    @fwrite($fp, $binary_data, $read_size);
                    $size -= $read_size;
                }
                fclose($fp);
                touch($zip_file_name, $header['mtime']);
            } else {
                $fp = fopen($zip_file_name . '.gz', 'wb');
                if (! $fp)
                    return (- 1);
                $binary_data = pack('va1a1Va1a1', 0x8b1f, 
                    Chr($header['compression']), Chr(0x00), time(), Chr(0x00), 
                    Chr(3));
                fwrite($fp, $binary_data, 10);
                $size = $header['compressed_size'];
                while ($size != 0) {
                    $read_size = ($size < 1024 ? $size : 1024);
                    $buffer = fread($zip, $read_size);
                    $binary_data = pack('a' . $read_size, $buffer);
                    @fwrite($fp, $binary_data, $read_size);
                    $size -= $read_size;
                }
                $binary_data = pack('VV', $header['crc'], $header['size']);
                fwrite($fp, $binary_data, 8);
                fclose($fp);
                $gzp = gzopen($zip_file_name . '.gz', 'rb') or
                     die('gzp compressed error');
                if (! $gzp)
                    return (- 2);
                $fp = fopen($zip_file_name, 'wb');
                if (! $fp)
                    return (- 1);
                $size = $header['size'];
                while ($size != 0) {
                    $read_size = ($size < 2048 ? $size : 2048);
                    $buffer = gzread($gzp, $read_size);
                    $binary_data = pack('a' . $read_size, $buffer);
                    @fwrite($fp, $binary_data, $read_size);
                    $size -= $read_size;
                }
                fclose($fp);
                gzclose($gzp);
                touch($zip_file_name, $header['mtime']);
                @unlink($zip_file_name . '.gz');
            }
        }
        return true;
    }
}
?>