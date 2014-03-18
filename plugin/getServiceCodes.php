<?php

include_once('eServices/eServices.php');
include_once('eServices/CustomerConnect.php');

// Get the parameters required for a connection to eServices
$oConnect = new ConnectDetails();
$connection = $oConnect->getConnectDetails();

//Create the request, as per Request Schema described in eServices - Usage Guide.xls

$parameters = array(
    'header' => array(
        'source' => 'TEAM',
        'accountNo' => '12345',
        'userAccessKey' => $connection['userAccessKey']
     )
);
$request = array('parameters' => $parameters);

// NOTE: Applications *must* validate all parameters passed (data type, mandatory fields non-null), as described in Readme.pdf,
//       or alerts are generated at StarTrack. Validation is omitted in this sample for reasons of clarity.

// Invoke StarTrack eServices

try
{
	$oC = new STEeService();
//	$oC = new startrackexpress\eservices\STEeService();	// *** If PHP V5.3 or later, uncomment this line and remove the line above ***

$response = $oC->invokeWebService($connection,'getConsignmentDetails', $request);		// $response is as per Response Schema
																						// described in eServices - Usage Guide.xls.
																						// Returned value is a stdClass object.
																						// Faults to be handled as appropriate.
}
catch (SoapFault $e)
{
	echo "<p>" . $e->detail->fault->fs_severity . "<br />";	
	echo $e->faultstring . "<br />";
	echo $e->detail->fault->fs_message . "<br />";
	echo $e->detail->fault->fs_category . "<br />";
	echo $e->faultcode . "<br />" . "</p>";
	exit($e->detail->fault->fs_timestamp);
	//	Or if there is a higher caller:    throw new SoapFault($e->faultcode, $e->faultstring, $e->faultactor, $e->detail, $e->_name, $e->headerfault);	
}

echo serialize($response);