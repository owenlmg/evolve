<?php
/**
 * 2013-6-26 下午3:41:44
 * @author      x.li
 * @abstract    公共函数库
 */

/**
 * 2013-6-27 上午11:17:41
 * @author  x.li
 * @param   number $parent_id
 * @return  array
 */
function getTestData($param = 0)
{
    $data = array();
    
    return $data;
}

function getMicrotimeStr()
{
    list($usec, $sec) = explode(" ", microtime());
    
    return $sec.'_'.substr($usec, 1);
}

function exportCsv($data)
{
    /* echo '<pre>';
    print_r($data);
    exit; */
    
    $filePath = '../temp/'.getMicrotimeStr().'.csv';
    
    $fp = fopen($filePath,'w');
    
    foreach ($data as $d){
        fputcsv($fp, $d);
    }
    
    fclose($fp);
    
    header('Content-Description: File Transfer');
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($filePath));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: '.filesize($filePath));
    readfile($filePath);
}