<?php

include 'dotenv.php';
(new DotEnv(__DIR__ . '/.env'))->load();
define("url", getenv('BASE_URL'));
define("prem_url", getenv('BASE_PREM_URL'));


function authorized($number)
{
    $authorizedNumbers = array(
        //BSA numbers
        //Authorized numbers

    );
    if (in_array($number, $authorizedNumbers)) {
        return "1";
    } else {
        return "0";
    }
}


function checkPhone($option)
{
}

function checkreq($param)
{
    $handle = curl_init('https://enyhx7j9dq8rg.x.pipedream.net/');

    $encodedData = json_encode($param);

    curl_setopt($handle, CURLOPT_POST, 1);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $encodedData);
    curl_setopt($handle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $result = curl_exec($handle);
    return $result;
}

function format_date($param)
{
    $timestamp = strtotime($param);
    $new_date = date("d/m/Y", $timestamp);
    return $new_date;
}

function ValidateContact($num)
{
    $Contact = '';
    $str = str_replace(' ', '', $num);
    $Contact = str_replace('-', '', $str);

    if ($Contact == null) {
        $Contact2 = 0;
    } else {
        $Contact2 = preg_match('/^[0]{1}[0-9]{1,9}+$/', $Contact);
    }
    return $Contact2;
}

function check_revType($option)
{
    $revType = '';
    if ($option == '1') {
        $revType = 'Market Fees';
    } elseif ($option == '2') {
        $revType = 'Property Tax';
    } elseif ($option == '3') {
        $revType = 'Business Operation Fees';
    } else {
        $revType = '';
    }
    return $revType;
}

function saveUssd($param)
{

    // checkreq($param);
    // $url_path = url . "saveUssddata";
    // echo "<pre>";
    // var_dump($param);
    // exit;
    $url_path = url . "process/buy-motor-policy/2";

    $data = json_encode($param);
    $curl = curl_init($url_path);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Content-type: 	application/json"
    ));

    curl_setopt($curl, CURLOPT_POST, true);

    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    $resp = curl_exec($curl);
    $arr = json_decode($resp);
    curl_close($curl);

    // return $arr;
}

function getEndDate($option)
{
    $todays_date = date("Y-m-d");

    $date = getPeriod($option);

    $period_in_months = $date[0];

    $end_date = date('Y-m-d', strtotime($period_in_months, strtotime($todays_date)));

    return  $end_date;
}

function isCorrectDate($string)
{
    if (isDate($string)) {

        $date = str_replace('/', '-', $string);

        $newDate = date("Y-m-d 23:59:59", strtotime($date));

        $date_now = new DateTime();
        $date2 = new DateTime($newDate);

        if ($date_now > $date2) {
            return false;
        } else {
            return true;
        }
    } else {
        return false;
    }
}

function isDate($string)
{
    $matches = array();
    $pattern = '/^([0-9]{1,2})\\/([0-9]{1,2})\\/([0-9]{4})$/';
    if (!preg_match($pattern, $string, $matches)) return false;
    if (!checkdate($matches[2], $matches[1], $matches[3])) return false;
    return true;
}

function formatPhoneNumber($num)
{
    if (empty($num)) {
        $Contact = '';
    } else {
        $Contact = "+233" . ltrim($num, "0");
    }

    return $Contact;
}

function formate_Date($param)
{
    $timestamp = strtotime($param);

    // Creating new date format from that timestamp
    $new_date = date("d/m/Y", $timestamp);
    return $new_date;
}

function continuePayment(array $param)
{
    // checkreq($param);
    $url_path = url . "continue-payment";

    $data = json_encode($param);
    $curl = curl_init($url_path);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Content-type: 	application/json"
    ));

    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 2);
    //curl_setopt($curl, CURLOPT_COOKIE, 'AspxAutoDetectCookieSupport=1');

    $resp = curl_exec($curl);
    $arr = json_decode($resp);
    curl_close($curl);

    // return $arr;
}

function check_agent($param){
    // checkreq($param);
    // $url_path = url . "continue-payment";

    // $data = json_encode($param);
    // $curl = curl_init($url_path);

    // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    //     "Content-type: 	application/json"
    // ));

    // curl_setopt($curl, CURLOPT_POST, true);
    // curl_setopt($curl, CURLOPT_HEADER, 0);
    // curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 2);
    // //curl_setopt($curl, CURLOPT_COOKIE, 'AspxAutoDetectCookieSupport=1');

    // $resp = curl_exec($curl);
    // $arr = json_decode($resp);
    // curl_close($curl);

    // return $arr;
    return true;
}
