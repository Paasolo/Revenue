<?php

$param = array(
    "car_number" => 'GM5887-12',
    "client_name" =>'donewell',
    "api_call" => true
);


$url_path = "http://192.168.100.28:8000/api/renewal/car-details/";

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

echo '<pre>';
print_r($arr);
