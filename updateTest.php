<?php
// SOAP_CLIENT_BASEDIR - folder that contains the PHP Toolkit and your WSDL
// $USERNAME - variable that contains your Salesforce.com username (must be in the form of an email)
// $PASSWORD - variable that contains your Salesforce.com password
	ini_set("soap.wsdl_cache_enabled", "0");
	error_reporting(0);


define("SOAP_CLIENT_BASEDIR", "soapclient");
require_once (SOAP_CLIENT_BASEDIR.'/SforcePartnerClient.php');
//~ require_once ('../userAuth.php');

try {
$USERNAME = 'lwalsh@vccsystems.com';
$PASSWORD = 'PinotNoir97FM3izV8V0agkFZf3Jmi8vain';
  $mySforceConnection = new SforcePartnerClient();
  $mySoapClient = $mySforceConnection->createConnection('partner.wsdl.xml');
  $mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);

/*--------------------------------------------------------\
| Please manage the values for OBJECT ID from file 
| userAuth.php
\--------------------------------------------------------*/
		$sObjects = array();

		$fieldsToUpdate = array (
			'MailingStreet' => '11111',
			'MailingCity' => 'TEST',
			'MailingState' => 'WA',
			'MailingPostalCode' => '33333',
			'MailingCountry' => 'USA' );
  $sObject1 = new SObject();
  $sObject1->fields = $fieldsToUpdate;
  $sObject1->type = 'Contact';
  $sObject1->Id = '003G000000uT5EGIA0';
array_push($sObjects, $sObject1);

  $response = $mySforceConnection->update($sObjects);

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

			$ERR = "Contact Address Update Failed\n";
			$ERR .= "Message: ".$Message."\n";
			$ERR .= "Status Code: ".$StatusCode."\n";
			//~ exit();
			echo $ERR;
			email_file($ERR);

		}
  //~ print_r($response);

} catch (Exception $e) {
  print_r($mySforceConnection->getLastRequest());
  echo $e->faultstring;
}

	function email_file($msg){
	$ADMIN_EMAIL = 'dirgesh.patel@idealistconsulting.com';
	$ADMIN_FROM = 'From: '.$ADMIN_EMAIL;
		
		$subject = "COPD Foundation CSV ERROR Occured on - ".date('m-d-Y');
		$message = "Please view the attached file for errors that occured: ";
		$message .= $msg;

		mail($ADMIN_EMAIL, $subject, $message, $ADMIN_FROM);
		//mail_attachment("dirgesh@gmail.com", $subject, $message, $file);
	}

?>
