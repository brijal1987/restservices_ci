<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * format out put with print_r
 *
 * @access    public
 * @param    number
 * @return    formated number
 */
if (!function_exists('pre')) {

    function pre($data, $exit = 1) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        if ($exit) {
            exit;
        }
    }

}
/**
 * print last query
 *
 * @access    public
 * @param    number
 * @return    last query
 */
if (!function_exists('lq')) {

    function lq($exit = 1) {
        $obj = &get_instance();
        echo $obj->db->last_query();
        if ($exit) {
            exit;
        }
    }

}

/**
 * write error details in error log
 *
 * @access  public
 * @param   array
 * @return  NA
 */
if (!function_exists('write_err_log')) {

    function write_err_log($db_error) {
        $obj = &get_instance();
        log_message('error', sprintf('%s : %s : DB transaction failed. Error msg:%s, Last query: %s', $obj->router->fetch_method(), $obj->router->fetch_class(), $db_error['message'], print_r($obj->db->last_query(), TRUE)));
    }

}

function genSlug($str) {
    return str_replace(' ', '-', strtolower($str));
}

function DDtoYYYY($refdate) { // convert from dd-mm-yyyy to yyyy-mm-dd
    if (!empty($refdate)) {
        $TempArray = explode(" ", $refdate);
        $arrayref = explode("-", $TempArray[0]);
        return $arrayref[2] . "-" . $arrayref[1] . "-" . $arrayref[0];
    }
}

function DDtoYYYY1($refdate, $format = "/") { // convert from dd/mm/yyyy to yyyy-mm-dd
    if (!empty($refdate)) {
        $TempArray = explode(" ", $refdate);
        $arrayref = explode($format, $TempArray[0]);
        return $arrayref[2] . "-" . $arrayref[1] . "-" . $arrayref[0];
    }
}

function YYYYtoDD($refdate) { // convert from yyyy-mm-dd to dd-mm-yyyy
    if (!empty($refdate)) {
        $TempArray = explode(" ", $refdate);
        $arrayref = explode("-", $TempArray[0]);
        return $arrayref[2] . "-" . $arrayref[1] . "-" . $arrayref[0];
    }
}

function format_date($date, $format = PHP_DATE_FORMAT) {
    if ($date != "") {
        return date($format, strtotime($date));
    }
}

function format_date_time($date, $format = PHP_DATE_TIME_FORMAT) {
    if ($date != "") {
        return date($format, strtotime($date));
    }
}

function getValidSlug($str) {
    $str = strtolower(str_replace(" ", "_", $str));
    return preg_replace('/[^a-zA-Z0-9_]/s', '', $str);
}

function is_admin() {
    $obj = &get_instance();
    return $obj->session->usertype_id == SUPER_ADMIN_USERTYPE_ID || $obj->session->usertype_id == ADMIN_USERTYPE_ID;
}

function is_exporter() {
    $obj = &get_instance();
    return $obj->session->usertype_id == EXPORTER_USERTYPE_ID;
}

function is_field_officer() {
    $obj = &get_instance();
    return $obj->session->usertype_id == FO_USERTYPE_ID;
}

function is_deo() {
    $obj = &get_instance();
    return $obj->session->usertype_id == DEO_USERTYPE_ID;
}

function vessel_is_valid($vessel_reg_no) {
    ini_set("soap.wsdl_cache_enabled", "0");
    $url = REAL_CRAFT_WSDL;
    $client = new SoapClient($url);

    $credentials = array(
        "vessel_regno" => $vessel_reg_no, //"IND-KL-07-MM-4798",
        "username" => REAL_CRAFT_USERNAME,
        "password" => REAL_CRAFT_PASSWORD
    );

    $response = $client->__soapCall("getVesselDetails", $credentials);

    if (isset($response[0]->vessel_regno)) {
        $obj = &get_instance();
        require_once APPPATH . "core/MY_Controller.php";

        $vessel_id = MY_Controller::get_vessel_detail($response);
        $response = array();
        $response[] = (object)array("vessel_id" => $vessel_id);
    }
    return $response;
}

//Call CURL to get Details from NIC server
function callAPIFromNiceServer($url, $data, $returnArray = true) {

    $params = '';
    foreach ($data as $key => $value)
        $params .= $key . '=' . $value . '&';

    $params = trim($params, '&');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . $params); //Url together with parameters
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7); //Timeout after 7 seconds
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
    curl_setopt($ch, CURLOPT_HEADER, 0);

    $result = curl_exec($ch);
    if ($returnArray) {
        return json_decode($result, true);
    } else {
        return json_decode($result);
    }
    curl_close($ch);
}

function replace_null_with_empty_string($array) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $array[$key] = replace_null_with_empty_string($value);
        } else {
            if (is_null($value)) {
                $array[$key] = "";
            }
        }
    }
    return $array;
}

/**
 * Get list of Styling added to Excel
 *
 * @access  Public
 * @param   NA
 * @return  Object
 */
function getExcelHeaders($objPHPExcel, $objDrawing, $excelStyle, $characterRange = ['A', 'Z']) {

    $intCharFirst = isset($characterRange[0]) ? $characterRange[0] : 'A';
    $intCharLast = isset($characterRange[1]) ? $characterRange[1] : 'Z';
    $count = 1;
    $characterMergeArray = [];
    for ($i = $intCharFirst; $i <= $intCharLast; $i++) {
        $count++;
        $characterMergeArray[$count] = $i;
    }
    $logoTextColumn = $count - 3;

    $sheet = $objPHPExcel->getActiveSheet();

    for ($i = 1; $i <= 8; $i++) {

        $sheet->mergeCells($intCharFirst . $i . ':' . $characterMergeArray[$logoTextColumn] . $i);
    }
    $sheet->mergeCells($characterMergeArray[$logoTextColumn + 1] . '1:' . $intCharLast . '8');

    $objPHPExcel->getActiveSheet()->SetCellValue($intCharFirst . '2', "THE MARINE PRODUCTS EXPORT DEVELOPMENT AUTHORITY")->getStyle($intCharFirst . '2:' . $characterMergeArray[$logoTextColumn] . '2')->applyFromArray($excelStyle['no_border'])->getAlignment();

    $objPHPExcel->getActiveSheet()->SetCellValue($intCharFirst . '3', "(Ministry of Commerce & Industry, Government of India)")->getStyle($intCharFirst . '3:' . $characterMergeArray[$logoTextColumn] . '3')->applyFromArray($excelStyle['no_border'])->getAlignment();

    $objPHPExcel->getActiveSheet()->SetCellValue($intCharFirst . '4', "")->getStyle($intCharFirst . '4:' . $characterMergeArray[$logoTextColumn] . '4')->applyFromArray($excelStyle['no_border'])->getAlignment();

    $objPHPExcel->getActiveSheet()->SetCellValue($intCharFirst . '5', "Head Office, MPEDA House, Building No: 27/1162, PB No: 4272")->getStyle($intCharFirst . '5:' . $characterMergeArray[$logoTextColumn] . '5')->applyFromArray($excelStyle['no_border'])->getAlignment();

    $objPHPExcel->getActiveSheet()->SetCellValue($intCharFirst . '6', "Panampilly Avenue, Panampilly Nagar PO,")->getStyle($intCharFirst . '6:' . $characterMergeArray[$logoTextColumn] . '6')->applyFromArray($excelStyle['no_border'])->getAlignment();

    $objPHPExcel->getActiveSheet()->SetCellValue($intCharFirst . '7', "KOCHI-682036, KERALA")->getStyle($intCharFirst . '7:' . $characterMergeArray[$logoTextColumn] . '7')->applyFromArray($excelStyle['no_border'])->getAlignment();

    // $objPHPExcel->getActiveSheet()->SetCellValue($intCharFirst . '1', "THE MARINE PRODUCTS EXPORT DEVELOPMENT AUTHORITY\n".
    // "(Ministry of Commerce & Industry, Government of India)\n\n".
    // "Head Office, MPEDA House, Building No: 27/1162, PB No:4272\n".
    // "Panampilly Avenue, Panampilly Nagar PO,\n".
    // "KOCHI-682036, KERALA")->getStyle($intCharFirst . '1:' . $characterMergeArray[$logoTextColumn] . '7')->applyFromArray($excelStyle['header_style'])->getAlignment();

    $objDrawing->setName('Logo');
    $objDrawing->setDescription('Logo');
    $logo = IMAGES . '/apple-touch-icon.png'; // Provide path to your logo file
    $objDrawing->setPath($logo);  //setOffsetY has no effect
    $objDrawing->setCoordinates($characterMergeArray[$logoTextColumn + 1] . '1');
    $objDrawing->setHeight(130); // logo height
    $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
    $objPHPExcel->getActiveSheet()->getStyle($characterMergeArray[$logoTextColumn + 1] . '1:' . $intCharLast . '8')->applyFromArray($excelStyle['header_style']);
}

/**
 * Get list of Styling added to Excel
 *
 * @access  Public
 * @param   NA
 * @return  Object
 */
function getExcelFooters($cell_id, $objPHPExcel, $objDrawing, $excelStyle, $characterRange = ['A', 'Z']) {
    $cell_id++;
    $intCharFirst = isset($characterRange[0]) ? $characterRange[0] : 'A';
    $intCharLast = isset($characterRange[1]) ? $characterRange[1] : 'Z';
    $count = 1;
    $characterMergeArray = [];
    for ($i = $intCharFirst; $i <= $intCharLast; $i++) {
        $count++;
        $characterMergeArray[$count] = $i;
    }
    $logoTextColumn = $count - 2;

    $sheet = $objPHPExcel->getActiveSheet();
    $sheet->mergeCells($intCharFirst . $cell_id . ':' . $characterMergeArray[$logoTextColumn] . $cell_id);
    $sheet->mergeCells($characterMergeArray[$logoTextColumn + 1] . $cell_id . ':' . $intCharLast . $cell_id);
    
    $objPHPExcel->getActiveSheet()->SetCellValue($intCharFirst . $cell_id, html_entity_decode(FOOTER_COPYRIGHT))->getStyle($intCharFirst . $cell_id . ':' . $characterMergeArray[$logoTextColumn] . $cell_id)->applyFromArray($excelStyle['footer_style']);

    $objPHPExcel->getActiveSheet()->SetCellValue($characterMergeArray[$logoTextColumn + 1] . $cell_id, date('d-m-Y'))->getStyle($characterMergeArray[$logoTextColumn + 1] . $cell_id . ':' . $intCharLast . $cell_id)->applyFromArray($excelStyle['footer_style']);
}

/**
 * Get list of Styling added to Excel
 *
 * @access  Public
 * @param   NA
 * @return  Object
 */
function excelCSSStylingForReport() {


    $style['header_style'] = array(
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wraptext' => true,
            'align' => 'center'
        ),
        'font' => array(
            'bold' => true,
            'size' => 11,
            'color' => array('rgb' => '428bca'),
            'name' => 'Calibri'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array('rgb' => '000000'),
            )
        )
    );

    $style['footer_style'] = array(
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wraptext' => true,
            'align' => 'left'
        ),
        'font' => array(
            'bold' => true,
            'size' => 11,
            'color' => array('rgb' => '428bca'),
            'name' => 'Calibri'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array('rgb' => '000000'),
            )
        )
    );
    $style['no_border'] = array(
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wraptext' => true,
            'align' => 'center'
        ),
        'font' => array(
            'bold' => true,
            'size' => 11,
            'color' => array('rgb' => '428bca'),
            'name' => 'Calibri'
        )
    );
    /* Cell Styling for first sheet */
    $style['style'] = array(
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wraptext' => true
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        ),
    );

    $style['filter_style'] = array(
        'alignment' => array(
            //'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wraptext' => true,
        ),
        'font' => array(
            'bold' => true,
            'size' => 11,
            'name' => 'Calibri'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_DOUBLE,
                'color' => array('rgb' => '000000'),
            )
        )
    );

    $style['filter_style_bold'] = array(
        'alignment' => array(
            //'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wraptext' => true,
        ),
        'font' => array(
            'bold' => true,
            'size' => 11,
            'color' => array('rgb' => '428bca'),
            'name' => 'Calibri'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_DOUBLE,
                'color' => array('rgb' => '000000'),
            )
        )
    );

    $style['row_style_bold'] = array(
        'font' => array(
            'bold' => true,
            'size' => 11,
            //'color' => array('rgb' => '428bca'),
            'name' => 'Calibri'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'd6d9db')
        )
    );

    $style['border_style'] = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        ),
    );

    $style['thstyle'] = array(
        'alignment' => array(
            //'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wraptext' => true,
        ),
        'font' => array(
            //'bold' => true,
            'color' => array('rgb' => 'f9f9f9'),
            'size' => 11,
            'name' => 'Calibri'
        ),
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array('rgb' => '000000'),
            )
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => '2e3292')
        )
    );
    return $style;
}

function get_purchase_log_label() {
    $obj = &get_instance();

    if ($obj->session->usertype_id == EXPORTER_USERTYPE_ID) {
        $label = "Exporter";
    } else if ($obj->session->usertype_id == DEO_USERTYPE_ID) {
        $label = "HDC";
    } else {
        $label = "Purchase";
    }
    return $label;
}

function round_to_3dp($number)
{
    return number_format((float)$number, 3, '.', '');
}

 /**
    *
    * Convert an object to an array
    *
    * @param    object  $object The object to convert
    * @reeturn      array
    *
    */
    function objectToArray( $object )
    {
        if( !is_object( $object ) && !is_array( $object ) )
        {
            return $object;
        }
        if( is_object( $object ) )
        {
            $object = get_object_vars( $object );
        }
        return array_map( 'objectToArray', $object );
    }
