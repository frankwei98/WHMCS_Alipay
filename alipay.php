<?php
require_once("callback/alipay.class.php");
function alipay_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"Alipay 支付宝全能接口 6.X专用 v3.0"),
     "T" => array("FriendlyName" => "接口模式", "Type" => "dropdown", "Options" => "1,2,3","Description" =>"支付宝接口模式:  即时到帐[1] 担保交易[2] 双功能 [3] ", ),
     "seller_email" => array("FriendlyName" => "卖家支付宝帐户", "Type" => "text", "Size" => "32","Description" => "需要申请支付宝商家集成", ),
     "partnerID" => array("FriendlyName" => "合作伙伴ID", "Type" => "text", "Size" => "25","Description" => "到你的支付宝商家后台查找", ),
     "security_code" => array("FriendlyName" => "安全检验码", "Type" => "text", "Size" => "50", "Description" => "同上",),
     "auto_send" => array("FriendlyName" => "自动发货", "Type" => "yesno",  "Description" => "是否自动发货[需支持SSL]", ),
     "ssl" => array("FriendlyName" => "支持SSL", "Type" => "yesno",  "Description" => "你的服务器(PHP)是否支持SSL,如果工作不正常.就选择NO", ),
     "need_confirm" => array("FriendlyName" => "必须确认收货", "Type" => "yesno",  "Description" => "客户使用担保交易的话,是否确认收货才入帐 ", ),
     "return" => array("FriendlyName" => "前台返回页面是否处理数据", "Type" => "yesno",  "Description" => "一般建议使用异步传输处理,但有可能支付宝服务器与你的服务器无法传输,那就需要这个处理了. ", ),
     "debug" => array("FriendlyName" => "调试模式", "Type" => "yesno", "Description" => "调试模式,详细LOG请见[WHMCS]/download/alipay_log.php", ),
    );
	return $configarray;
}
//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
function alipay_link($params) {
	#支付宝接口配置
	$type=$params['T'];

	$alipay_config['input_charset']  = 'utf-8';
	$alipay_config['sign_type']      = "MD5";
	$alipay_config['transport']      = $params['ssl'] ? "https" :"http";  
	$alipay_config['partner']        = $params['partnerID'];
	$alipay_config['key']            = $params['security_code'];
	$alipay_config['seller_email']   = $params['seller_email'];
	$debug                           = $params["debug"];

	#系统变量
	$invoiceid = $params['invoiceid'];
	$description = $params["description"];
	$amount = $params['amount']; # Format: ##.##
	$currency = $params['currency']; # Currency Code
	$companyname = $params['companyname'];
	$systemurl = $params['systemurl'];
	$currency = $params['currency'];

	$alipay_config['return_url'] = $systemurl."/modules/gateways/callback/alipay_return.php";
	$alipay_config['notify_url'] = $systemurl."/modules/gateways/callback/alipay_callback.php";

	switch ($type) {
		case "1":
			$service_name="create_direct_pay_by_user";
			break;
		case "2":
			$service_name="create_partner_trade_by_buyer";
			break;
		case "3":
			$service_name="trade_create_by_buyer";
			break;
		default:
	
	}

	//基本参数
	$parameter = array(
	"service"				=> $service_name,
	"partner"			  => trim($alipay_config['partner']),
	"_input_charset"=> trim(strtolower($alipay_config['input_charset'])),
	"return_url"		=> trim($alipay_config['return_url']),
	"sign_type"     => trim($alipay_config['sign_type']),
	"notify_url"		=> trim($alipay_config['notify_url']),
	);
	//业务参数
	$parameter["subject"]       = "$companyname 订单[ $invoiceid ]";
	$parameter["body"]          = $description;
	$parameter["out_trade_no"]  = $invoiceid;
	$parameter["price"]			    = $amount;
	$parameter["quantity"]		  = "1";
	$parameter["payment_type"]	= "1";
	$parameter["seller_email"]	= trim($alipay_config['seller_email']);
	if ($type <> "1"){
		$parameter["logistics_fee"]     ="0";
		$parameter["logistics_type"]    ="EXPRESS";
		$parameter["logistics_payment"] ="SELLER_PAY";
	}

	$img=$systemurl."/modules/gateways/callback/alipay.png";
	$alipaySubmit = new AlipaySubmit($alipay_config);
	$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
	if ($debug) {
		$msg="订单: $invoiceid 生成支付表单 $html_text";
		logResult($msg);
	}
	$code=$html_text."</form><a href='#' onclick=\"document.forms['alipaysubmit'].submit();\"><img src='$img' alt='点击使用支付宝支付'> </a>";
	return $code;
}
?>