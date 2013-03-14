<?php

	ini_set("soap.wsdl_cache_enabled", "0");
	//~ error_reporting(0);

	require_once ('soapclient/SforcePartnerClient.php');
	require_once ('soapclient/SforceHeaderOptions.php');
	require_once ('includes/func.php');
	require_once ('includes/config.php');

	echo "Reading CSV File...... <br>";
	
	if ($records === false)
	{
		echo "Error in PHP Script: Email Sent to Admin<br>";
		mail("Dirgesh@gmail.com", "PHP Lead Export Script  Error", "There has been an error in the Lead replication script\nPlease Check Script $file");
	}else{
		echo "Picking up File from FTP............<br>";
		$pickupfile=GetFileVia_FTP();
		$CSVdata = Read_from_CSV($pickupfile);
		
		echo "Updating SFDC Records.......<BR>";
		Update_CallResults($client, 'Closed');
		
		echo "Creating Arvhice File....<BR>";
		Create_Archive_File($pickupfile, $DeliveredOrdersCount);
		
		echo "Moving file to Read Archive Folder.....<BR>";
		SendFileVia_FTP($ftp_readarchive_folder, $ReadArchive_file, $ReadArchive_file);
		
		echo "Moving $pickupfile to COPD Archive .... <BR>";
		Create_COPD_Archive($pickupfile, $COPD_Archive);
		
		echo "Removing CSV file from Local Directory.......<br>";
		remove_csvdump($pickupfile);
		
		echo "Removing Archive file from Local Directory.......<br>";
		remove_csvdump($ReadArchive_file);
		
		echo "Script Executed Succesfully!";
	}


?>