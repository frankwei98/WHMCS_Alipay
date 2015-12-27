<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>WHMCS - 支付宝接口测试</title>
</head>
<body>

<?php
require_once("./alipay.class.php");
$cafile="./cacert.pem";
$url="http://notify.alipay.com/trade/notify_query.do?service=notify_verify&partner=2088002560&notify_id=RqP";
$return=getHttpResponseGET($url,$cafile);
$ok=strstr($return,'false')? "成功" : "失败";
echo "测试CURL连接HTTP  :$ok";
$url="https://mapi.alipay.com/gateway.do?service=notify_verify&service=notify_verify&partner=2088002560&notify_id=RqP";
$return=getHttpResponseGET($url,$cafile);
$ok=strstr($return,'PARTNER')? "成功" : "失败";
echo "\r\n<br>测试CURL连接HTTPS  :$ok";
?>