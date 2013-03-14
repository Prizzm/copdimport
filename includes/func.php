<?php
	ob_start();
	
	function remove_csvdump($file){
		if (file_exists($file)) {
			unlink($file);
		} 
	}
	
	function email_file($file, $msg){
		include 'mail_attachment.php';
		global $ADMIN_EMAIL, $ADMIN_FROM;
		
		$subject = "COPD Foundation CSV ERROR Occured on - ".date('m-d-Y');
		$message = "Please view the attached file for errors that occured: ";
		$message .= $msg;

		mail($ADMIN_EMAIL, $subject, $message, $ADMIN_FROM);
		//mail_attachment("dirgesh@gmail.com", $subject, $message, $file);
	}

	function return_count($beg_date){
		global $client, $debug, $soql;
		
		if($debug)echo "<BR>$soql<br><br>";

		$queryOptions = new QueryOptions(2000);
		$response = $client->query(($soql), $queryOptions);	
		
		
		return $response->size;
	}
	
	function Create_COPD_Archive($pickupfile, $COPD_Archive){
		global $myFile;
		$COPD_Archive .= $pickupfile;

		if (!copy($pickupfile, $COPD_Archive)) {
			$ERR = "failed to copy $pickupfile...to $COPD_Archive\n";
			echo $ERR;
			email_file($myFile, $ERR);
		}
	}
	
	function FIX_DATE($date){
		if($date != ''){
			$date = explode("/",$date);
			$date = $date[2].'-'.$date[0].'-'.$date[1];
			
			return $date;
		}
		return false;
	}
	
	function Delivery_Status($Delivery_Status){
		if($Delivery_Status != ''){
			if($Delivery_Status == 0){
				return 'Un-Delivered';
			}elseif($Delivery_Status == 1){
				return 'Delivered';
			}
		}
		return false;
	}
	
	function UpdateContactAddress($connection, $Contact_Address){
		global $ERROR_MESSAGE, $myFile;
		
		$Contact_ID = $Contact_Address[1];
		$CallResults_OI = $Contact_Address[2];
		$MailingStreet = $Contact_Address[3];
		$MailingCity = $Contact_Address[4];
		$MailingState = $Contact_Address[5];
		$MailingPostalCode = $Contact_Address[6];
		$MailingCountry = $Contact_Address[7];
		$Contact_Name = $Contact_Address[8];
				
		$sObjects = array();

		$fieldsToUpdate = array (
			'MailingStreet' => $MailingStreet,
			'MailingCity' => $MailingCity,
			'MailingState' => $MailingState,
			'MailingPostalCode' => $MailingPostalCode,
			'MailingCountry' => $MailingCountry );
			
		$sObject1 = new SObject();
		$sObject1->fields = $fieldsToUpdate;
		$sObject1->type = 'Contact';
		$sObject1->Id = $Contact_ID;
		array_push($sObjects, $sObject1);
		$response = $connection->update($sObjects);
		
		
		if($response[0]->success == 1){
			echo "Contact [$Contact_Name] Update";
			echo '<br>';
			return true;
		}else{
			$records = $response[0];

			foreach ($records as $record) {
				$Message =  $record[0]->message;
				$StatusCode = $record[0]->statusCode;
				if($Message != '')break;

			}

			$ERR = "Contact Address Update Failed<br>";
			$ERR .= "Message: ".$Message."\n";
			$ERR .= "Status Code: ".$StatusCode."\n";
			//~ print_r($response);
			//~ exit();
			echo $ERR;
			echo $ERROR_MESSAGE;
			email_file($myFile, $ERR);
			return false;
			//~ exit();
		}
		return false;
	}
	
	function Update_CallResults($connection, $status){
		global $Cases_2_Update, $CSVdata, $ERROR_MESSAGE, $myFile;
		
		$count = count($Cases_2_Update);
		$sObjects = array();
		$function = 'SFDC Update';
		$tmp_array = array();
		$tmp_array2 = array();
		$tmp_var="";
		
		try
		{
			if($status=='Closed'){
				$i=0;
				$count = 0;

				//~ echo 'SIZE : '.sizeof($CSVdata)."<BR>";
				foreach($CSVdata as $first){
					if($i>0){
						$SFDC_ID = $first[0];
						
						//Check to make sure this isn't a duplicate;
						if (in_array($SFDC_ID, $tmp_array)) {
							$i++;
							continue;
						}
						$Contact_ID = $first[1];
						$CallResults_OI = $first[2];
						$MailingStreet = $first[3];
						$MailingCity = $first[4];
						$MailingState = $first[5];
						$MailingPostalCode = $first[6];
						$MAilingCountry = $first[7];
						$Name = $first[8];
						$Date_Delivered = $first[9];
						$Delivery_Status = $first[10];
						
						
						//Convert from Unix Timestamp
						$Date_Delivered = FIX_DATE($Date_Delivered);
						$Delivery_Status = Delivery_Status($Delivery_Status);
						//~ echo $SFDC_ID. "<BR>";
						//~ echo $Date_Delivered. "<BR>";
						
						//If contact addresses are different update the address
						if($MailingStreet != '' || $MailingCity != '' || $MailingState != ''){
							$ret = UpdateContactAddress($connection, $first);
						}
						
						$fieldsToUpdate = array (
							'OI_Date_Delivered__c' => $Date_Delivered,
							'OI_Delivery_Status__c' => $Delivery_Status,
							'Status' => $status );
							
						$sObject1 = new SObject();
						$sObject1->fields = $fieldsToUpdate;
						$sObject1->type = 'Case';
						$sObject1->Id = $SFDC_ID;
						array_push($sObjects, $sObject1);
						array_push($tmp_array, $SFDC_ID);
						$count++;
					}
					$i++;
					if($i > 190){
						$i=1;
						$response = $connection->update($sObjects);
						  if(sizeof($response) > 1){
							foreach ($response as $result)
							{
								$data = $result->id. ", ";
								$data .= $status. ", ";
								$data .= $function. ", ";
								$data .= date("F j, Y, g:i a"); 

								if($result->success){
									$s++;
									log_it($data, 'success');
								  }else{
									$errMessage = $result->errors->message;
									$data .= $errMessage; 
									log_it($data, 'error');
								  }
							}
						  }elseif(sizeof($response) == 1){
								$data = $response->id. ", ";
								$data .= $status. ", ";
								$data .= $function. ", ";
								$data .= date("F j, Y, g:i a"); 
							
							if($response->success){
								$s++;
								log_it($data, 'success');
							}else{
								$errMessage = $response->errors->message;
								$data .= $errMessage; 
								log_it($data, 'error');
							}	
						  }
						$sObjects = array();
					}
				}
			}else{
				
				for($i=0; $i<$count;$i++){
						$fieldsToUpdate = array (
						  'Status' => $status );
						$sObject1 = new SObject();
						$sObject1->fields = $fieldsToUpdate;
						$sObject1->type = 'Case';
						$sObject1->Id = $Cases_2_Update[$i];
						$tmp_var = $Cases_2_Update[$i];
							
						if (!in_array($tmp_var, $tmp_array2)) {
							array_push($tmp_array2, $tmp_var);
							array_push($sObjects, $sObject1);
						}else{
							echo "<BR> Found dup, Skipping <BR>";
						}
				}
			}
			
			//~ echo '<pre>' . print_r($sObjects, true) . '</pre>';
			//~ exit();
			
			  $response = $connection->update($sObjects);
			  //~ print_r($response);
			  //~ echo "Found: ".sizeof($response)." Records";
			  
			  if(sizeof($response) > 1){
				foreach ($response as $result)
				{
					$data = $result->id. ", ";
					$data .= $status. ", ";
					$data .= $function. ", ";
					$data .= date("F j, Y, g:i a"); 

					if($result->success){
						$s++;
						log_it($data, 'success');
					  }else{
						$errMessage = $result->errors->message;
						$data .= $errMessage; 
						log_it($data, 'error');
					  }
				}
			  }elseif(sizeof($response) == 1){
					$data = $response->id. ", ";
					$data .= $status. ", ";
					$data .= $function. ", ";
					$data .= date("F j, Y, g:i a"); 
				
				if($response->success){
					$s++;
					log_it($data, 'success');
				}else{
					$errMessage = $response->errors->message;
					$data .= $errMessage; 
					log_it($data, 'error');
				}	
			  }
			
			if($count != $s){
				//mismatch in update count
				$ERR = "Update Failed, Please View Error Log<br>";
				$ERR .= "Found: $count<br>";
				$ERR .= "Updated: $s<br>";
				echo $ERR;
				echo $ERROR_MESSAGE;
				
				email_file($myFile, $ERR);
				//~ exit();
				return false;
			}
			return true;
		}
		catch(exception $e)
		{
			echo $e;
			return false;
			//~ exit;
		}
		
	}
	
	function get_records($connection, $query)
	{
	    //Set this to the number of records to process per batch
	    //200 is the minimum
	    $queryOptions = new QueryOptions(2000);
	    $response = $connection->query(($query), $queryOptions);
	    $count_records = $response->size;
	    
	    if ($response->size > 0)
	    {
		$records = $response->records;
		    set_time_limit(100);
		    ini_set("memory_limit", "512M");
		    $current_count = csv_dump($records, true);
		}
		return $count_records;

	}

	function log_it($data, $type){
		$today = date("m_d_y");
		global $myFile;
		$myFile = "logs/".$type."_log_".$today.".txt";
		if(!file_exists($myFile)){
			$tmp = "ID, ";
			$tmp .= "Status, ";
			$tmp .= "Function, ";
			$tmp .= "Timestamp\n"; 
			$data = $tmp.$data;
		}
		$fh = fopen($myFile, 'a') or die("can't open file");
		$data .= "\n";
		fwrite($fh, $data);
		fclose($fh);
	}

	function csv_dump($data, $header)
	{
		
	    global $DropOff_file, $Cases_2_Update, $OI_Item_Lookup, $ERROR_MESSAGE;

	    $fp = fopen($DropOff_file, 'a');
	
		//echo $data[0][0];
	    foreach ($data as $r)
	    {
	    
		//Get the CALL ORDER LINE ITEMS
		$CallOrder_LineItems = $r->queryResult[0]->size;
		array_push($Cases_2_Update, $r->Id);
		for($i=0; $i < $CallOrder_LineItems; $i++){
			$CO_Qty = $r->queryResult[0]->records[$i]->fields->Qty__c;
			$CO_CallResult = $r->queryResult[0]->records[$i]->fields->Call_Result__c;
			$pass_this['Id'] = $r->Id;
			$pass_this['Contact ID'] = $r->fields->ContactId;
			$pass_this['Call Results'] = $OI_Item_Lookup[$CO_CallResult];

			foreach ($r->fields as $key )
			{	
				//~ print_r($key);
				foreach ($key->fields as $sub_key => $sub_value ){
					$pass_this[$sub_key] = $sub_value;
					if($sub_key == 'MailingStreet')$pass_this['Qty']=$CO_Qty;
				}
			}

			if ($header)
			{
			//~ print_r($pass_this);
			    $keys = array_keys($pass_this);
			    fputcsv($fp, $keys);
			    $header = false;
			}
			fputcsv($fp, $pass_this);
		}
		
		
		//~ foreach ($r->queryResult as $key ){
			//~ echo 'SIZE of Call Order: '.$key->size;
			//~ echo '<BR>';
			//~ foreach ($key->records as $test ){
				//~ print_r($test->fields->Call_Result__c);
				//~ echo "<br>";
				//~ print_r($test->fields->Qty__c);
				//~ echo "<br>";		
			//~ }
		//~ }

		//~ $pass_this['Id'] = $r->Id;
		//~ $pass_this['Contact ID'] = $r->fields->ContactId;
		//~ $TotalQty = intval($r->fields->Order_Qty__c);
		
		//~ $CallResults_OI = $r->fields->Call_Results__c;
		//~ $pass_this['Call Results'] = $OI_Item_Lookup[$CallResults_OI];


	    }

		//~ echo '<pre>' . print_r($data, true) . '</pre>';
		//~ echo '<pre>' . print_r($pass_this, true) . '</pre>';
		fclose($fp);
		//~ exit();
	}
	
	function SendFileVia_FTP($ftp_dropoff_folder, $source_file, $destination_file){
		// variables
		global $ftp_server, $ftp_user_name, $ftp_user_pass, $ERROR_MESSAGE, $myFile;
		$destination_file = $ftp_dropoff_folder.$destination_file;

		// set up basic connection
		$conn_id = ftp_connect($ftp_server); 

		// login with username and password
		$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass); 
		echo "Uploading $source_file to $destination_file <br>";
		       
		// check connection
		if ((!$conn_id) || (!$login_result)) { 
		       echo "FTP connection has failed! <br>";
		       echo "Attempted to connect to '$ftp_server' for user '$ftp_user_name'<br>"; 
		       $ftp_error = "Attempted to connect to $ftp_server for user $ftp_user_name";
		       log_it($ftp_error);
			email_file($myFile, $ftp_error);
		       exit; 
		   } 

		// upload the file
		$upload = ftp_put($conn_id, $destination_file, $source_file, FTP_BINARY);  // line 30

		// check upload status
		if (!$upload) { 
		       echo "FTP upload has failed!<br>";
			echo "Uploading $source_file to $destination_file <br>";
		       $ftp_error="FTP upload has failed!";
		       log_it($ftp_error);
			email_file($myFile, $ftp_error);
		       exit; 
		   } 

		// close the FTP stream 
		ftp_close($conn_id);
		return $DropOff_file;
	}
	
	function GetFileName_viaFTP(){
		global $ftp_server, $ftp_user_name, $ftp_user_pass, $ftp_pickup_folder, $ftp_dropoff_folder,$ERROR_MESSAGE, $myFile;
		
		// set up basic connection
		$conn_id = ftp_connect($ftp_server); 
		// login with username and password
		$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass); 
		$contents = ftp_nlist($conn_id,$ftp_pickup_folder);
		ftp_close($conn_id);
		
		if(sizeof($contents) == 0){
			echo "No files found in $ftp_pickup_folder<br>";
			$msg = "No File found in $ftp_pickup_folder";
			email_file($myFile, $msg);
			return false;
		}
		
		$dir = $contents[0];
		$contents = explode("/", $dir);
		//~ print_r($contents);
		
		//~ return $contents[2];
		return $contents[2];
	}
	
	function GetFileVia_FTP(){
		// variables
		global $ftp_server, $ftp_user_name, $ftp_user_pass, $ftp_pickup_folder, $ftp_dropoff_folder, $pickupfile, $ERROR_MESSAGE, $myFile;
		
		$filename = GetFileName_viaFTP();
		if (!$filename){
			echo "No filename [$filename] found to pickup, Exiting Script";
			$msg = "No filename [$filename] found to pickup, Exiting Script";
			email_file($myFile, $msg);
			//~ exit();
			return false;
		}
		
		$source_file = $ftp_pickup_folder.$filename;
		$destination_file = $filename;

		// set up basic connection
		$conn_id = ftp_connect($ftp_server); 

		// login with username and password
		$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass); 

		// check connection
		if ((!$conn_id) || (!$login_result)) { 
		       echo "FTP connection has failed! <br>";
		       echo "Attempted to connect to $ftp_server for user $ftp_user_name<br>"; 
		       $ftp_error = "Attempted to connect to $ftp_server for user $ftp_user_name";
		       log_it($ftp_error);
       			email_file($myFile, $ftp_error);
			return false;
		       //~ exit; 
		   } 

		// pickup the file
		$upload = ftp_get($conn_id, $destination_file, $source_file, FTP_BINARY);  // line 30

		// check download status
		if (!$upload) { 
		       echo "FTP FETCH has failed! <br>";
		       echo "Trying to get file [$filename] with Source: $source_file from $ftp_pickup_folder with Destination: [$destination_file]<br>";
		       $ftp_error="FTP upload has failed!";
		       log_it($ftp_error);
       			email_file($myFile, $ftp_error);
		   } 

		// close the FTP stream 
		ftp_close($conn_id);
		return $filename;
	}
	
	function Create_Archive_File($Filename, $DeliveredOrdersCount){
		global $ReadArchive_file;
		$Filename = $Filename;
		
		$fp = fopen($ReadArchive_file, 'w');
		fputs($fp, "$Filename\r\n");
		fputs($fp, $DeliveredOrdersCount);
		fclose($fp);
	}

	function Read_From_CSV($file){
		global $DeliveredOrdersCount;
		$csv=array();
		$file = fopen($file, 'r');
		$DeliveredOrdersCount=0;
		
		while (($result = fgetcsv($file)) !== false)
		{
			if($result[0] !=''){
			    $csv[] = $result;
			    $DeliveredOrdersCount++;
			}
		}
		$DeliveredOrdersCount--;
		
		fclose($file);
		if(sizeof($csv) > 1 ) return $csv;
		
		return false;
	}
	
	function Preview_CSV($connection, $query){
	    //Set this to the number of records to process per batch
	    //200 is the minimum
	    $queryOptions = new QueryOptions(2000);
	    $response = $connection->query(($query), $queryOptions);
	    $header=true;
	    global $OI_Item_Lookup;

	    if ($response->size > 0)
	    {
		$records = $response->records;
		$count_records = 0;
		    set_time_limit(100);
		    ini_set("memory_limit", "512M");
		    //Make a Temp CSV file**************************************************
		    $output = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');
		    foreach ($records as $r)
		    {
			//Get the CALL ORDER LINE ITEMS
			$CallOrder_LineItems = $r->queryResult[0]->size;
			for($i=0; $i < $CallOrder_LineItems; $i++){
				$CO_Qty = $r->queryResult[0]->records[$i]->fields->Qty__c;
				$CO_CallResult = $r->queryResult[0]->records[$i]->fields->Call_Result__c;
				$pass_this['Id'] = $r->Id;
				$pass_this['Contact ID'] = $r->fields->ContactId;
				$pass_this['Call Results'] = $OI_Item_Lookup[$CO_CallResult];

				foreach ($r->fields as $key )
				{	
					//~ print_r($key);
					foreach ($key->fields as $sub_key => $sub_value ){
						$pass_this[$sub_key] = $sub_value;
						if($sub_key == 'MailingStreet')$pass_this['Qty']=$CO_Qty;
					}
				}

			if ($header)
			{
			    $keys = array_keys($pass_this);
			    fputcsv($output, $keys);
			    $header = false;
			}
			fputcsv($output, $pass_this);
			}
		    }
			rewind($output);
			$export = stream_get_contents($output);
			fclose($output);
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="export.csv"');	
			echo $export;
			exit();
		    //**********************************************************************
		//~ return $count_records;
		}else{
			echo "<br>No Records Found<br>";
		}
	}

?>