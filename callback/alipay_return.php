<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html> 
<head> 
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
	<title>支付宝支付接口返回页面</title>
	<link href="https://cdn.bootcss.com/bootstrap/3.3.6/css/bootstrap-theme.css" rel="stylesheet"> 
</head> 
<body style="margin:0;padding:0;"> 
	<div style="text-align:center;padding:20% 0 0 0;margin:0;"> 
			<table width="350" border="0" align="center" cellpadding="0" cellspacing="0" style="background:#ccccff;border:1px solid #999999;margin:5px auto;"> 
			<tr height="47" valign="middle"> 
				<td align="left"> 
					
				</td> 
			</tr> 
			<tr> 
				<td align="center" style="padding:8px 4px;"> 
					<table border="0" cellpadding="4" cellspacing="0"> 
						<tr> 
							<td colspan="2" align="center">
<?php 
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
require_once("./alipay.class.php");
$gatewaymodule = "alipay"; # Enter your gateway module name here replacing template
$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback
$need_confirm=$GATEWAY['need_confirm'];
$auto_send=$GATEWAY['auto_send'];
$debug=$GATEWAY['debug'];

$alipay_config['input_charset']= "utf-8";
$alipay_config['sign_type']    = "MD5";
$alipay_config['transport']    = $GATEWAY['ssl'] ? "https" :"http";
$alipay_config['partner']      = $GATEWAY['partnerID'];
$alipay_config['key']          = $GATEWAY['security_code'];
$alipay_config['seller_email'] = $GATEWAY['seller_email'];
$alipay_config['cacert']       = getcwd().'/cacert.pem';

$alipayNotify = new AlipayNotify($alipay_config);
$alipayNotify->debug=$debug;
if ($debug) logResult(serialize($_GET));
logTransaction($GATEWAY["name"],$_GET,"INFO");
$verify_result = $alipayNotify->verifyReturn();
$n1=$n2=false;
if($verify_result) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代码
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	//获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

	# Get Returned Variables
	$status    = $_GET['trade_status'];    //获取支付宝传递过来的交易状态
	$invoiceid = $_GET['out_trade_no']; //获取支付宝传递过来的订单号
	$transid   = $_GET['trade_no'];       //获取支付宝传递过来的交易号
	$amount    = $_GET['total_fee'];       //获取支付宝传递过来的总价格
	$fee       =0;

	if($status == 'WAIT_SELLER_SEND_GOODS') {  //准备卖家发货
		if ($debug) logResult("订单 $invoiceid  已收款.准备发货");
		if (!$need_confirm) $n2=true;
		if ($auto_send) {  //是否自动发货
			echo "订单支付成功,但需要你到支付宝后台确认收货,支付才算成功.麻烦你尽快登陆支付宝确认收货";
			$n1=true;
		}else{
			echo "支付已经完成.但需要我们手工确认发货.请留意支付宝后台.如果有发货了,麻烦你尽快确认收货";			
		}
	}
	else if($status == 'TRADE_FINISHED' or $status == 'TRADE_SUCCESS') { //支付完成
		if ($debug) logResult("订单 $invoiceid  支付成功.");
		$n2=true;
		echo "订单支付成功!如果没有入帐成功.请联系管理员";
	}
	else if($status == 'WAIT_BUYER_CONFIRM_GOODS'){
		if ($debug) logResult("订单 $invoiceid  等待收货.");
		if ($need_confirm){
			echo "订单支付成功,但需要你到支付宝后台确认收货,支付才算成功.麻烦你尽快登陆支付宝确认收货";
		}else{
			echo "订单支付成功!如果没有入帐成功.请联系管理员";
			$n2=true;
		}
	}
	else {
		logResult(" $invoiceid trade_status=".$status);
		echo "验证成功: $status";
	}
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else {
	//验证失败
	//如要调试，请看alipay_notify.php页面的verifyReturn函数
	echo "验证失败";
	print_r($_GET);
}
?>		
							
							</td> 
						</tr> 
						<tr height="47" ><td colspan="4" align="center">按<a href="../../../" target="">这里</a>返回客户系统</td></tr>
					</table> 
				</td> 
			</tr> 
		</table> 	
	
	</div> 
</body> 
</html>
<?php
if (!$GATEWAY['return']) exit;
if ($n1){
	$parameter = array(
		"service"			=> "send_goods_confirm_by_platform",
		"partner"			=> $GATEWAY['partnerID'],
		"_input_charset"	=> trim(strtolower($alipay_config['input_charset'])),
		"trade_no"			=> $transid,
		"logistics_name"	=> "AUTO_WHMCS",
		"invoice_no"		=> $invoiceid,
		"transport_type"	=> "EXPRESS"
	);
	//自动发货
	$alipaySubmit = new AlipaySubmit($alipay_config);
	$html_text = $alipaySubmit->buildRequestHttp($parameter);
	if ($debug) logResult("订单 $invoiceid 发货信息:".$html_text);
}
if ($n2){
	$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing
	checkCbTransID($transid);
	addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule);
	logTransaction($GATEWAY["name"],$_GET,"Successful-C");
	if ($debug) logResult("订单 $invoiceid  直接入帐.");
}