<?php

	ini_set("soap.wsdl_cache_enabled", "0");
	//~ error_reporting(0);

	require_once ('soapclient/SforcePartnerClient.php');
	require_once ('soapclient/SforceHeaderOptions.php');
	require_once ('includes/func.php');
	require_once ('includes/config.php');

	echo "Reading CSV File...... <br>";

	$conn_id="ftp01.originalimpressions.com";//Write in the format "ftp.servername.com"
	$conn_id = ftp_connect ( $ftp_server );
	$ftp_user_name="copdfulfillment";
	$ftp_user_pass="H65jja4tuLVT";
	$login_result = ftp_login ( $conn_id , $ftp_user_name , $ftp_user_pass );
	
	echo "Turning Passive mode Off";
	ftp_pasv($conn_id, false);
	
	print_r($conn_id);
	
	if ((! $conn_id ) || (! $login_result )) {
		echo "FTP connection has failed!" ;
		echo "Attempted to connect to $ftp_server for user $ftp_user_name" ;
		exit;
	    } else {
		echo "Connected to $ftp_server, for user $ftp_user_name" ;
	    } 
	exit();
	
	if ($records === false)
	{
		echo "Error in PHP Script: Email Sent to Admin<br>";
		mail("Dirgesh@gmail.com", "PHP Lead Export Script  Error", "There has been an error in the Lead replication script\nPlease Check Script $file");
	}else{
		echo "Unix timestamp: ";
		echo time();
		
		echo "Picking up File from FTP............<br>";
		$filename = GetFileVia_FTP();
		
		//~ $file='COPD_datadump-051011-0317pm.csv';
		//~ $CSVdata = Read_From_CSV($file);
		
		echo "Updating SFDC Records.......<BR>";
		Update_CallResults($client, 'Closed');
		
		echo "Removing CSV file from Local Directory.......<br>";
		//~ remove_csvdump($file);
		
		echo "Script Executed Succesfully!";
	}


?>