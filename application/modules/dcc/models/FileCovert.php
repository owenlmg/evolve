<?php

/**
 * 2013-9-1
 * @author mg.luo
 * @abstract
 */
class Dcc_Model_FileCovert {

    /**
     * exec output
     *
     * @var unknown_type
     */
    private $_output = array();

    /**
     * the os support of the convert
     *
     * @var string
     */
    private $_supportOS = 'WIN';

    /**
     * the path of the files
     *
     * @var string
     */
    private $_filePath = '';

    function __construct() {
        define('UPLOAD_PATH', HOME_PATH . '/upload/');
        define('CONVERT_PATH', UPLOAD_PATH . 'convert/');
        define('BASE_PATH', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '\\');  // for windows only
//        define('CONVERT_OPENOFFICE_JAVA', 'java.exe -jar ' . BASE_PATH . 'plugin\\DocConvertor_fat.jar localhost 8100 %s %s 2>&1');
        define('CONVERT_OPENOFFICE_JAVA', 'C:\\Java\\jdk1.7.0_40\\bin\\java.exe -jar ' . BASE_PATH . 'plugin\\jodconverter\\lib\\jodconverter-cli-2.2.2.jar %s %s 2>&1');

        define('CONVERT_PDF2SWF', '"' . BASE_PATH . 'plugin\\SWFTools\\pdf2swf.exe" ');
        define('CONVERT_JPEG2SWF', '"' . BASE_PATH . 'plugin\\SWFTools\\jpeg2swf.exe" ');
        define('CONVERT_GBK2UTF8', '"' . BASE_PATH . 'plugin\\iconv.exe" -f %s -t utf-8 "%s" > "%s"');  // to convert the encoding of file from gbk to utf-8
        define('IS_TEST', 0);

        define('CONVERT_OPENOFFICE_JAR', 'java -jar ' . dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '\\plugin\\convert.jar %s');

        $this->_output = array();

        require_once '../plugin/AK_String.php';
    }

    function createPreview($dirSrc, $fileBaseName, $page = "") {
        set_time_limit(0);
        $tmp = pathinfo($fileBaseName);
        $filename = $tmp['filename'];
        $extension = strtolower($tmp['extension']);

        $dirDst = $dirSrc . "convert/";
        // 原始文件
        $fileSrc = $dirSrc . $fileBaseName;
        // 中间PDF文件
        $filePdf = $dirDst . $filename . ".pdf";
        // 最终SWF文件
        $fileDst = $dirDst . $filename . ".swf";

        if (!is_dir($dirDst)) {
            mkdir($dirDst);
        }

        $isAccepted = false;
        $accepted = explode(",", "txt, pdf, doc, docx, xls, xlsx, ppt, pptx,csv");  //

        $officeFileExt = array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'jnt', 'odg', 'odp', 'ods', 'odt');   //'txt',



        $convertFrom = $this->_filePath . $fileBaseName;

        for ($i = 0; $i < count($accepted); $i++) {
            if (strtolower($extension) == strtolower(trim($accepted[$i]))) {
                $isAccepted = true;
                break;
            }
        }

        if (!$isAccepted) {
            return false;
        }

        if (in_array($extension, $officeFileExt)) {
            // convert to pdf first, and then convert pdf to swf;

            if (IS_TEST) {  // convert on local, use PHP openoffice code:COM;
                $doc_src = 'file:///' . $fileSrc;
                $doc_dst = 'file:///' . $filePdf;

                if (!$this->office2pdf($doc_src, $doc_dst)) {
                    return false;
                }
            } else {   // convert on server, use JAVA code;
                $doc_src = $fileSrc;
                $doc_dst = $filePdf;

                if (!$this->openOfficeDocumentConvert($doc_src, $doc_dst)) {
                    return false;
                }
            }

            $this->pdf2swf($filePdf, $fileDst);
        } elseif ($extension == 'txt') {
            // 先转换成PDF文件
            if (IS_TEST) {
                $doc_src = 'file:///' . $fileSrc;
                $doc_dst = 'file:///' . $filePdf;

                if (!$this->office2pdf($doc_src, $doc_dst)) {
                    return false;
                }
            } else {
                $fileodt = $dirDst . $filename . ".odt";
                copy($fileSrc, $fileodt);
                if (!$this->openOfficeDocumentConvert($fileodt, $filePdf)) {
                    @unlink($fileodt);
                    return false;
                }
               @unlink($fileodt);
            }
            // 再转换为SWF文件
            $this->pdf2swf($filePdf, $fileDst);
            // 删除中间过程产生的PDF文件
            //@unlink($filePdf);
        } elseif ($extension == 'eml') {
            $tmpFile = $dirSrc . $filename . '.txt';
            $srcFile = $fileSrc;

            $content_tmp = file_get_contents($srcFile);
            $content_tmp = quoted_printable_decode($content_tmp);

            $content_tmp = iconv('ISO-8859-1', 'utf-8', $content_tmp);
            $content_tmp = Ak_String::German_decode($content_tmp);

            file_put_contents($tmpFile, $content_tmp);

            $tmp = Ak_String::getMicroString();
            $doc_src = $tmpFile; //$fileSrc.$fileBaseName;
            $doc_dst_txt = $fileDst . $filename . $tmp . '.txt';

            $tmp_name = $doc_src;

            $encode = Ak_String::getFileEncode($doc_src);

            if ($encode == 'GB2312' || $encode == 'GBK' || $encode == 'ASCII') {
                $cmd = sprintf(CONVERT_GBK2UTF8, $encode, $doc_src, $doc_dst_txt);
                exec($cmd, $this->_output);

                if (empty($this->_output)) {  // convert to utf-8 success;
                    $tmp_name = $doc_dst_txt;
                }
            }

            if (IS_TEST) {
                $doc_src = 'file:///' . $tmp_name;
                $doc_dst = 'file:///' . $fileDst . $filename . $tmp . '.pdf';

                if (!$this->office2pdf($doc_src, $doc_dst)) {
                    return false;
                }
            } else {
                $doc_src = $tmp_name;
                $doc_dst = $fileDst . $filename . $tmp . '.pdf';

                if (!$this->openOfficeDocumentConvert($doc_src, $doc_dst)) {
                    return false;
                }
            }

            $doc_src = $fileDst . $filename . $tmp . '.pdf';
            $doc_dst = $fileDst . $filename . '.swf';

            $this->pdf2swf($doc_src, $doc_dst);

            @unlink($doc_dst_txt);
            @unlink($doc_src);
            @unlink($tmpFile);
        } elseif ($extension == 'html' || $extension == 'htm') {
            $srcFile = $fileSrc . $fileBaseName;
            $tmp = Ak_String::getMicroString();
            $doc_src = $fileDst . $filename . $tmp . '.pdf';
            $doc_dst = $fileDst . $filename . '.swf';

            $this->html2pdf($srcFile, $doc_src);

            $this->pdf2swf($doc_src, $doc_dst);

            @unlink($doc_src);
        } elseif ($extension == 'pdf') {
            $this->pdf2swf($fileSrc, $fileDst);
        } elseif ($extension == '__tif') {
            $tmp = Ak_String::getMicroString();
            $doc_src = $fileSrc . $fileBaseName;
            $doc_dst_p = $fileDst . $filename . '.jpg';

            $cmd = CONVERT_IMAGICK . CONVERT_IMAGICK_PAR . $doc_src . " " . $doc_dst_p;

            exec($cmd . ' 2>&1', $this->_output);

            if (!$this->isSuccess()) {
                return false;
            }
        } elseif ($extension == 'tif') {
            $tmp = Ak_String::getMicroString();
            $doc_src = $fileSrc . $fileBaseName;
            $doc_dst_p = $fileDst . $filename . $tmp . '.pdf';

            $cmd = CONVERT_IMAGICK . CONVERT_IMAGICK_PAR . $doc_src . " " . $doc_dst_p;

            exec($cmd . ' 2>&1', $this->_output);

            if (!$this->isSuccess()) {
                return false;
            }

            $doc_src = $doc_dst_p;
            $doc_dst = $fileDst . $filename . '.swf';

            $this->pdf2swf($doc_src, $doc_dst);

            @unlink($doc_dst_p);
        } else {
            $tmp = Ak_String::getMicroString();
            $convertTo = CONVERT_PATH . $filename . $tmp . ".jpg";

            $cmd = CONVERT_IMAGICK . CONVERT_IMAGICK_PAR . $convertFrom . $page . " " . $convertTo;
            exec($cmd . ' 2>&1', $this->_output);

            if (!$this->isSuccess()) {
                return false;
            }

            // TODO: convert to swf;
            $doc_src = $fileDst . $filename . $tmp . '.jpg';
            $doc_dst = $fileDst . $filename . '.swf';

            $this->jpeg2swf($doc_src, $doc_dst);

            @unlink($convertTo);
        }
        if (is_file($fileDst)) {
            return $fileDst;
        } else {
            return "";
        }
    }

    function setFilePath($path) {
        if (!is_dir($path)) {
            return false;
        }

        $this->_filePath = $path;

        return true;
    }

    function isSupport($fileType) {
        $accepted = array('txt', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx','csv');
        if (in_array($fileType, $accepted)) {
            return true;
        }

        return false;
    }

    function jarOffice2pdf($doc_src, $doc_dst) {
        $cmd = sprintf(CONVERT_OPENOFFICE_JAR, $doc_src);
        exec($cmd, $this->_output);

        return $this->isSuccess();
    }

    /**
     * use openoffice to convert file to pdf
     * Notice: must install openoffice in the os first.
     *
     * @param string $name
     * @param string $value
     * @param object $osm
     * @return array
     */
    function MakePropertyValue($name, $value, $osm) {
        $oStruct = $osm->Bridge_GetStruct("com.sun.star.beans.PropertyValue");
        $oStruct->Name = $name;
        $oStruct->Value = $value;

        return $oStruct;
    }

    /**
     * use openoffice to convert file to pdf
     *
     * @param string $doc_url
     * @param string $output_url
     */
    function office2pdf($doc_url, $output_url) {
        try {
            /* $cmd = CONVERT_OPENOFFICE_SERVICE;
              exec($cmd.' 2>&1', $this->_output);

              if (!$this->isSuccess()) {
              return false;
              } */

            $osm = new COM("com.sun.star.ServiceManager");
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }

        $args = array($this->MakePropertyValue("Hidden", true, $osm));
        $oDesktop = $osm->createInstance("com.sun.star.frame.Desktop");
        $oWriterDoc = $oDesktop->loadComponentFromURL($doc_url, "_blank", 0, $args);
        $export_args = array($this->MakePropertyValue("FilterName", "writer_pdf_Export", $osm));
        $oWriterDoc->storeToURL($output_url, $export_args);

        $oWriterDoc->close(true);

        //        $osm->dispose();

        return true;
    }

    function pyOffice2pdf($doc_url, $output_url) {
        $cmd = sprintf('C:\\Python33\\python.exe "C:\\Program Files (x86)\\OpenOffice.org 3\\program\\DocumentConverter.py" %s %s 2>&1', $doc_url, $output_url);

        exec($cmd, $this->_output);
        Ak_String::printm($this->_output);
        if (!$this->isSuccess()) {
            return false;
        }

        return true;
    }

    function openOfficeDocumentConvert($input, $output) {
        $cmd = sprintf(CONVERT_OPENOFFICE_JAVA, $input, $output);
        exec($cmd, $this->_output);

        return $this->isSuccess();
    }

    /**
     * convert html to pdf
     *
     * @param string $doc_src    path to source file, include filename
     * @param string $doc_dst    path to output file, include filename
     */
    function html2pdf($doc_src, $doc_dst) {
        require_once(LIB_PATH . 'html2fpdf/html2fpdf.php');

        $strContent = @file_get_contents($doc_src);

        $strContent = iconv('utf-8', 'gbk', $strContent);
        $pdf = new HTML2FPDF();
        $pdf->AddPage();
        $pdf->writeHTML($strContent);
        $pdf->Output($doc_dst);
    }

    function pdf2swf($doc_src, $doc_dst, $swf_v = 9) {


        if (!is_file($doc_src)) {
            return false;
        }

//        $cmd = CONVERT_PDF2SWF . ' -T ' . $swf_v . ' ' . $doc_src . ' -o ' . $doc_dst . ' -s poly2bitmap';
        $cmd = CONVERT_PDF2SWF . ' -T ' . $swf_v . ' ' . $doc_src . ' -o ' . $doc_dst;
        exec($cmd . ' 2>&1');
        // 删除pdf文件
        $tmp = pathinfo($doc_src);
        $dir = $tmp['dirname'];

        if(preg_match('/convert$/', $dir)) {
            @unlink($doc_src);
        }

        return $this->isSuccess();
    }

    function jpeg2swf($doc_src, $doc_dst, $swf_v = 9) {
        if (!is_file($doc_src)) {
            return false;
        }
        $cmd = CONVERT_JPEG2SWF . ' -T ' . $swf_v . ' ' . $doc_src . ' -o ' . $doc_dst;
        exec($cmd . ' 2>&1', $this->_output);

        return $this->isSuccess();
    }

    function imagickRotate($doc_src, $doc_dst, $degress = 90) {
        if (!is_file($doc_src)) {
            return false;
        }

        $cmd = CONVERT_IMAGICK . ' -rotate ' . $degress . ' ' . $doc_src . ' ' . $doc_dst;

        exec($cmd . ' 2>&1', $this->_output);

        if ($this->isSuccess()) {
            @unlink($doc_src);
            Ak_FileSystem_File::rename($doc_dst, $doc_src);
        }

        return $this->isSuccess();
    }

    /**
     * check the exec output
     * if error happen, return false, else return true.
     *
     * @return bool
     */
    function isSuccess() {
        if (!empty($this->_output)) {
            foreach ($this->_output as $val) {
                if (stripos($val, 'error') !== false) {
                    $hasError = true;
                    break;
                }
            }

            if (isset($hasError) && $hasError) {
                return false;
            }
        }

        return true;
    }

}