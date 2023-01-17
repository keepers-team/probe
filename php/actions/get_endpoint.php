<?php

if (!isset($_POST['url'])) return false;

$url = $_POST['url'];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);

$json = curl_exec($ch);

if(!$json) {
    echo curl_error($ch);
}

curl_close($ch);

print_r($json);

?>