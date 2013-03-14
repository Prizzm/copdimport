<?php

	ini_set("soap.wsdl_cache_enabled", "0");
	error_reporting(0);

	require_once ('soapclient/SforcePartnerClient.php');
	require_once ('soapclient/SforceHeaderOptions.php');
	require_once ('includes/func.php');
	require_once ('includes/config.php');

	//Processes the query to get account information from Salesforce
	//~ log_it($soql);
	//~ echo $soql;
	echo "Capturing Records...... <br>";
	
	$records = get_records($client, $soql);
	
	if($records == 0){
		echo "No Records Found <BR>";
		exit();
	}else{
		echo "Sending File to FTP............<br>";
		SendFileVia_FTP($ftp_dropoff_folder, $DropOff_file, $DropOff_file);
		
		//~ echo "Emailing File to Admin..........<br>";
		//~ email_file($DropOff_file);
		
		echo "Removing CSV file from Local Directory.......<br>";
		remove_csvdump($DropOff_file);
		
		echo "Updating SFDC Records.......<BR>";
		Update_CallResults($client, 'Pending fulfillment');
		
		echo "Script Executed Succesfully!";
	}
?>