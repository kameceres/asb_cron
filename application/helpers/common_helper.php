<?php

if (!function_exists('asset')) {
    /**
     * Return assets folder with input path
     * @param $asset_path
     * @return string
     */
    function asset($asset_path)
    {
        return '/assets/' . $asset_path;
    }
    
    // File versioning makes sure that the correct files are downlaod and old files are not used in cash. .htaccess strips the versioning before delivering the file.
    function auto_version($file)
    {
        $ofile = $file;
        $u = curPageURL($dir = true);
        
        while (strpos($file, '../') === 0) {
            // remove from file var
            $file = substr($file, 3);
            $slash = strripos($u, "/");
            $u = substr($u, 0, $slash);
        }
        $u = $u . '/' . $file;
        
        if (strpos($file, '/') !== 0 || ! file_exists($_SERVER['DOCUMENT_ROOT'] . $u)) {
            $mtime = filemtime($_SERVER['DOCUMENT_ROOT'] . $u);
        }
        return preg_replace('{\\.([^./]+)$}', ".$mtime.\$1", $ofile);
    }
    
    function curPageURL($dir = false)
    {
        if ($dir) {
            $url = $_SERVER["REQUEST_URI"];
            $slash = strripos($url, "/");
            $pageURL = substr($url, 0, $slash);
        } else {
            $pageURL = $url;
        }
        return $pageURL;
    }
    
    function date_format_by_timezone($time, $format, $timezone=null)
    {
        $CI =& get_instance();
        $time_format = $CI->session->userdata('timeformat');
        
        if ($time_format == 1) {
            $format = str_replace('H', 'h', $format);
            $format = str_replace('G', 'g', $format);
            if (strpos($format, 'h:') !== false) {
                if (strpos(strtolower($format), ' a') === false && strpos(strtolower($format), 'a') === false) {
                    $format .= ' A';
                }
            } else if (strpos($format, 'g:') !== false) {
                if (strpos(strtolower($format), ' a') === false && strpos(strtolower($format), 'a') === false) {
                    $format .= ' A';
                }
            }
        } else {
            if (strpos($format, 'h') !== false) {
                $format = str_replace('h', 'H', $format);
            } else if (strpos($format, 'g') !== false) {
                $format = str_replace('g', 'G', $format);
            }
            $format = str_replace(' A', '', $format);
            $format = str_replace(' a', '', $format);
            $format = str_replace('A', '', $format);
            $format = str_replace('a', '', $format);
        }
        
        return date_by_timezone($time, $format, $timezone);
    }
    
    function date_by_timezone($time, $format, $timezone=null)
    {
        if (!$time) {
            return "";
        }
        if (!$timezone) {
            $CI =& get_instance();
            $timezone = $CI->session->userdata('timezone');
        }
        if (!$timezone) {
            $timezone = 'America/Denver';
        }
        
        date_default_timezone_set($timezone);
        return date($format, $time);
    }
    
    function getPresetData($xml, $data)
    {
        $data;
        $data = explode('->', $data);
        $D = $xml;
        foreach ($data as $e) {
            if ($D->$e) {
                $D = $D->$e;
            } else {
                $D = false;
                break;
            }
        }
        if ($D) {
            $D = (string) $D;
        }
        return $D;
    }

    function get_content($url)
    {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);     //We want the result to be saved into variable, not printed out
        $response = curl_exec($handle);                         
        curl_close($handle);
        if (!$response) {
            $response = file_get_contents($url);
        }
        return json_decode($response, true);
    }
    
    function addTranslationCounts($c_id, $d_id, $transCount)
    {
        $CI =& get_instance();
        
        $sql = "INSERT INTO company_translation_count (`date_of_trans`, `trans_count`, `c_id`, `d_id`) VALUES (CURRENT_DATE(), $transCount, $c_id, $d_id);";
        $CI->db->query($sql);
    }
}

?>