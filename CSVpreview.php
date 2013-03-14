<?php
	ini_set("soap.wsdl_cache_enabled", "0");
	//~ error_reporting(0);
	//~ error_reporting(E_ALL);

	require_once ('soapclient/SforcePartnerClient.php');
	require_once ('soapclient/SforceHeaderOptions.php');
	require_once ('includes/func.php');
	require_once ('includes/config.php');
	$records = Preview_CSV($client, $soql);
	
	//~ if($debug){
		//~ echo $soql;
		//~ exit();
	//~ }

?>