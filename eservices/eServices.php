<?php
  // namespace startrackexpress\eservices;	// *** Uncomment this line if PHP V5.3 or later ***
  
  require_once("WSSecurity.php");

/*	 ** CUSTOMER SHOULD NOT MODIFY THIS FILE **

     <document_root>/MyWebSite/eServices.php
     PHP API for StarTrack eService
     StarTrack
     17 August 2012
	 Version 4.4
     --------------------------------------------------------
     
     PURPOSE
     This software is provided to customers of StarTrack to simplify the development of PHP-based customer software 
     interfacing to StarTrack systems via eServices. Customers may alternatively interface directly to eServices, using any 
     programming language.

     DISCLAIMER
     This software is provided by StarTrack as-is and without warranty. StarTrack will not be liable for any 
     defects or omissions herein.
     
     REQUIREMENTS
     * PHP Web Server V5 with the following extensions enabled in php.ini: soap, cURL and openssl.

     * The following items supplied by StarTrack:
         -- WSDL files for staging and for production
		 -- Username and password for specific customer account (as used for access to StarTrack web portal), with
            appropriate role(s) enabled (for example Track-and-Trace and/or Cost Estimation)
		 -- Unique User Access Key 

     USAGE
     For sample calling programs, see ConsignmentDetails.php, CostCalculation.php and CostETACalculation.php.
     
     TRANSPORT
     SOAP over HTTPS
*/

define("ERRORSTRING", "*Error*");

class STEeService		
{	
// ****************
// PUBLIC FUNCTIONS
// ****************
	
    public function invokeWebService(array $connection, $operation, array $request)
	// Invokes StarTrack web service using supplied request and returns the response
	// For details see sample applications and the Usage Guide
	{
			try
			{		
/*
				$localCertificateFilespec = $connection['localCertificateFilespec'];				// .pem client certificate with 
																									// private key prepended.																															
																																	
				$localCertificatePassphrase = $connection['localCertificatePassphrase'];			// Passphrase for client's
																									// private key
				$sslOptions = array(
								'ssl' => array(
												'cafile' => "c:/inetpub/wwwroot/eServices/Server_DSA_Public_Certificate.pem",
												'local_cert' => $localCertificateFilespec,
												'passphrase' => $localCertificatePassphrase,
												'allow_self_signed' => true,
												'verify_peer' => false
										   	   )
								   );		
				$sslContext = stream_context_create($sslOptions);
*/			
				$clientArguments = array(
//											'stream_context' => $sslContext,
//											'trace' => true,
											'exceptions' => true,			
											'encoding' => 'UTF-8',
											'soap_version' => SOAP_1_1,
											'features' => SOAP_SINGLE_ELEMENT_ARRAYS	// Added in Release 4.0 to regularise
																						// the response - single elements are 
																						// arrays of length 1, not objects
									 	);

				$oClient = new WSSoapClient($connection['wsdlFilespec'], $clientArguments);	
				
				$oClient->__setUsernameToken($connection['username'], $connection['password']);	

				return $oClient->__soapCall($operation, $request);																				
			}
			catch (SoapFault $e)
			{
				throw new SoapFault($e->faultcode, $e->faultstring, NULL, $e->detail);
				// It is left to the caller to handle this exception as desired
			}
	}
	
	// Customers should invoke the following translation methods via the eponymous methods in the Translation class
	// (CustomerConnect.php) and not directly

	public function statusDescription($statusCode, $level, $verbosity)
	/*
	Converts a consignment status code or freight item status code to a descriptive string.
	Input parameters (case-insensitive):
		$statusCode = a consignment status code or freight item status code, as returned by invokeWebService
		$level = 'consignment' for consignment status, or
		        'freightItem' for freight item status 

		$verbosity = 'brief' for short description - same terminology as StarTrack website Track-and-Trace, or
		            'full' for long description, more understandable by people unfamiliar with the transportation industry

	Returned:
		Description string, or "* Error *" if input parameter is illegal.
	
	Remark: If 'brief' must be used, consider displaying 'full' when user hovers over brief description.
	
	*/
	{	
		if (is_null($statusCode))
		{
			return "";
		}
		switch (strtolower($level))
		{
    		case 'consignment':
    				switch (strtolower($verbosity))
					{
						case 'brief':
							$descriptionMap = $this->consignmentBriefDescriptionMap();
						break;
						case 'full':
							$descriptionMap = $this->consignmentFullDescriptionMap();
						break;
						default:
							return ERRORSTRING;	// Illegal verbosity
					}
        	break;
    		case 'freightitem':
					switch (strtolower($verbosity))
					{
						case 'brief':
							$descriptionMap = $this->freightItemBriefDescriptionMap();
						break;
						case 'full':
							$descriptionMap = $this->freightItemFullDescriptionMap();
						break;
						default:
							return ERRORSTRING;	// Illegal verbosity
					}
        	break;
    		default:
        		return ERRORSTRING			;	// Illegal level
		}
		
		$returnVal = $descriptionMap[strtoupper($statusCode)];
		if (is_null($returnVal))
		{
			$returnVal = "";
		}
		return $returnVal;

	}
	
	public function serviceDescription($serviceCode)
	/*
	Converts a service code (e.g. 'EXP') to a descriptive string.
	Input parameters (case-insensitive):
		$serviceCode = a service code, as returned by invokeWebService

	Returned:
		Description string (e.g. 'ROAD EXPRESS'), or "" if not found
	*/
	{
		if (is_null($serviceCode))
		{
			return "";
		}
		$descriptionMap = $this->serviceDescriptionMap();
		$returnVal = $descriptionMap[strtoupper($serviceCode)];
		if (is_null($returnVal))
		{
			$returnVal = "";
		}
		return $returnVal;
	}
	
	public function locationDescription($locationCode)
	/*
	Converts a location code (e.g. 'SYD') to a descriptive string.
	Input parameters (case-insensitive):
		$locationCode = a location code, as returned by invokeWebService

	Returned:
		Description string (e.g. 'SYDNEY'), or "" if not found
	*/
	{
		if (is_null($locationCode))
		{
			return "";
		}

		$descriptionMap = $this->locationDescriptionMap();
		$returnVal = $descriptionMap[strtoupper($locationCode)];
		if (is_null($returnVal))
		{
			$returnVal = "";
		}
		return $returnVal;
	}
	
	public function qualityControlDescription($qcCode)
	/*
	Converts a Quality Control code (e.g. 'U') to a descriptive string.
	Input parameters (case-insensitive):
		$qcCode = a Quality Control code, as returned by invokeWebService

	Returned:
		Description string (e.g. 'UNSERVICEABLE'), or "" if not found
	*/
	{
		if (is_null($qcCode))
		{
			return "";
		}
		$descriptionMap = $this->qualityControlDescriptionMap();
		$returnVal = $descriptionMap[strtoupper($qcCode)];
		if (is_null($returnVal))
		{
			$returnVal = "";
		}
		return $returnVal;
	}
	
	public function podSignatoryDescription($signatoryCode)
	/*
	Converts a POD Signatory code (e.g. '*LAI') to a descriptive string
	Input parameters (case-insensitive):
		$signatoryCode = a POD Signatory code, as returned by invokeWebService

	Returned:
		Description string (e.g. 'LEFT AS INSTRUCTED'), or "" if not found
	*/
	{
		if (is_null($signatoryCode))
		{
			return "";
		}
		$signatoryCode = trim($signatoryCode);
		$descriptionMap = $this->podSignatoryDescriptionMap();
		$returnVal = $descriptionMap[strtoupper($signatoryCode)];
		if (is_null($returnVal))
		{
			$returnVal = "";
		}
		return $returnVal;
	}
	
	public function substituteAnyPODSignatoryCode($podSignatoryName)
	/*
	Checks whether podSignatoryName contains a defined code such as *LAI, and if so substitutes a descriptive string

	Input parameters (case-insensitive):
		$podSignatoryName = name of POD signatory, or special code, as returned by invokeWebService

	Returned:
		The input parameter or, if it is a defined special code (e.g. *LAI), a descriptive string for the code (e.g. LEFT AS INSTRUCTED)
	*/
	{
		if(substr($podSignatoryName, 0, 1) == '*')					// If first character is '*'
		{
			$returnVal = $this->podSignatoryDescription($podSignatoryName);
			if ($returnVal == "")
			{
				return $podSignatoryName;
			}
			return $returnVal;										// Invalid code - return it as-is
		}
		else
		{
			return $podSignatoryName;
		}			
	}
	
	public function stateAbbreviation($stateCode)
	/*
	Converts a State code (e.g. '2') to the corresponding abbreviation
	Input parameters (case-insensitive):
		$stateCode = a State code, as returned by invokeWebService

	Returned:
		Description string  (e.g. 'NSW'), or "" if not found		
	*/
	{
		if (is_null($stateCode))
		{
			return "";
		}
		$abbreviationMap = $this->stateAbbreviationMap();
		$returnVal = $abbreviationMap[strtoupper($stateCode)];
		if (is_null($returnVal))
		{
			$returnVal = "";
		}
		return $returnVal;
	}
	
	public function consignmentTransitState($stateCode)
	/*
	Converts a consignment-level Transit State code (e.g. 'M') to the corresponding abbreviation
	Input parameters (case insensitive):
		$stateCode = a consignment-level Transit State code, as returned by invokeWebService

	Returned:
		Description string  (e.g. 'On Board for Delivery'), or "" if not found		
	*/
	{
		if (is_null($stateCode))
		{
			return "";
		}
		$abbreviationMap = $this->consignmentTransitStateMap();
		$returnVal = $abbreviationMap[strtoupper($stateCode)];
		if (is_null($returnVal))
		{
			$returnVal = "";
		}
		return $returnVal;
	}
	
	public function freightItemTransitState($stateCode)
	/*
	Converts a freight-item-level Transit State code (e.g. 'M') to the corresponding abbreviation
	Input parameters (case insensitive):
		$stateCode = a freight-item-level Transit State code, as returned by invokeWebService

	Returned:
		Description string  (e.g. 'On Board for Delivery'), or "" if not found		
	*/
	{
		if (is_null($stateCode))
		{
			return "";
		}
		$abbreviationMap = $this->freightItemTransitStateMap();
		$returnVal = $abbreviationMap[strtoupper($stateCode)];
		if (is_null($returnVal))
		{
			$returnVal = "";
		}
		return $returnVal;
	}
	
	public function suburbs()
	// DO NOT USE THIS FUNCTION -- SUBURB NAMES ARE NO LONGER UNIQUE
	/*	Returned:
		Associative array of all valid Suburb names (as per Reference Data), each linked to its Postcode and State.
		A typical array element is:
		       ‘THE GAP (BRISBANE)’ => array(‘4061’, ‘QLD’).
	*/
	{
		return $this->suburbsMap();
	}
	
	public function destinationSortationCode($postcode, $serviceCode)
	/*
	Generates a Destination Sortation Code as barcoded on freight labels
	Input parameters (case-insensitive):
		$postcode = postcode of freight receiver (e.g. '2000')
		$serviceCode = Service Code for the consignment (e.g. 'EXP')

	Returned:
		Destination Sortation Code (e.g. 'SYDX')
	*/
	{
		return $this->nearestDepot($postcode) . $this->fastServiceCode($serviceCode);
	}
	
	public function nearestDepot($postcode)
	/*
	Converts a postcode (e.g. '2000') to the Depot Code of the nearest carrier depot (e.g. 'SYD')
	Input parameters (case-insensitive):
		$postcode = postcode (usually of freight receiver)

	Returned: Depot Code of the nearest carrier depot		
	*/
	{
		if (is_null($postcode))
		{
			return "";
		}
		$abbreviationMap = $this->nearestDepotMap();
		$returnVal = $abbreviationMap[strtoupper($postcode)];
		if (is_null($returnVal))
		{
			$returnVal = "";
		}
		return $returnVal;
	}
	
	public function fastServiceCode($serviceCode)
	/*
	Converts a Service Code (e.g. 'EXP') to the corresponding Fast Service Code (e.g. 'X')
	Input parameters (case-insensitive):
		$serviceCode = Service Code

	Returned:
		Fast Service Code
	*/
	{
		if (is_null($serviceCode))
		{
			return "";
		}
		$abbreviationMap = $this->fastServiceCodeMap();
		$returnVal = $abbreviationMap[strtoupper($serviceCode)];
		if (is_null($returnVal))
		{
			$returnVal = "";
		}
		return $returnVal;
	}
	

// *********************************
// PRIVATE FUNCTIONS (CODE MAPPINGS)
// *********************************

// These mappings may vary from time to time and so should be peridically reviewed by StarTrack.
// Ultimately a web service should be made available to furnish these values.

	private function consignmentBriefDescriptionMap() 
    // Consignment Code => Brief Description	
	{ 
   		return array 
   		( 
			'AD' => 'At Delivery Depot',    
      		'BI' => 'Booked In',  
      		'CO' => 'Confirmed',     
      		'DE' => 'Deleted',   
      		'DF' => 'Delivered in Full', 
      		'DL' => 'Delivered',     
      		'FS' => 'Final Shortage',     
      		'IC' => 'Incomplete',   
      		'IT' => 'In Transit',     
      		'OD' => 'On Board for Delivery',   
      		'PD' => 'Partial Delivery',    
      		'PP' => 'Partial Pickup',  
      		'PU' => 'Picked Up',    
      		'RC' => 'Re-Consigned',   
      		'RD' => 'To be Re-Delivered',
      		'RP' => 'Ready for Pickup',  
      		'UC' => 'Unconfirmed',    
      		'UD' => 'Unsuccessful Delivery'
   		); 
	} 

	private function consignmentFullDescriptionMap() 
    // Consignment Code => Full Description	
	{ 
   		return array 
   		( 
			'AD' => 'Consignment is at carrier depot closest to receiver',    
      		'BI' => 'Consignment held at carrier depot closest to receiver until a date and time authorised by receiver',   
      		'CO' => 'Sender has provided consignment information to carrier in preparation for shipment',     
      		'DE' => 'Sender has deleted consignment information prepared earlier in preparation for shipment',     
      		'DF' => 'All freight items in the consignment have been delivered',     
      		'DL' => 'Some or all freight items in the consignment have been delivered and Proof of Delivery is available',     
      		'FS' => 'Delivery is complete but not all items were able to be delivered',     
      		'IC' => 'Sender has partially completed consignment information for carrier in preparation for shipment',     
      		'IT' => 'Consignment is in transit between two carrier depots (initial/intermediate/final)',     
      		'OD' => 'Consignment is in a local delivery vehicle',     
      		'PD' => 'Some but not all freight items in the consignment have been delivered',     
      		'PP' => 'Some but not all items in the consignment were scanned by carrier on pickup from sender',     
      		'PU' => 'Carrier has picked up from the sender all freight items in the consignment',     
      		'RC' => 'Consignment information incorrect or incomplete: corrected information supplied in a new consignment',     
      		'RD' => 'Consignment has been returned to local carrier depot as undeliverable: to be re-delivered on a following day',
      		'RP' => 'Consignment awaiting pickup by carrier',     
      		'UC' => 'Sender has completed consignment information for carrier in preparation for shipment but has not yet finalised the consignment',     
      		'UD' => 'Consignment could not be delivered' 		
   		); 
	} 

private function freightItemBriefDescriptionMap()
    // Freight Item Code => Brief Description	
	{ 
   		return array 
   		( 
			'AD' => 'At Delivery Depot',    
      		'BI' => 'Booked In',  
      		'CO' => 'Confirmed',     
      		'DE' => 'Deleted',   
      		'DF' => 'Item Delivered', 
      		'FS' => 'Final Shortage',     
      		'IC' => 'Incomplete',   
      		'IT' => 'In Transit',     
      		'OD' => 'On Board for Delivery',   
      		'PU' => 'Picked Up',    
      		'RC' => 'Re-Consigned',   
      		'RD' => 'To be Re-Delivered',
      		'RP' => 'Ready for Pickup',  
      		'UC' => 'Unconfirmed',    
      		'UD' => 'Unsuccessful Delivery'
   		); 
	} 

	private function freightItemFullDescriptionMap()
    // Freight Item Code => Full Description	
	{ 
   		return array 
   		( 
			'AD' => 'Freight item is at carrier depot closest to receiver',    
      		'BI' => 'Freight item held at carrier depot closest to receiver until a date and time authorised by receiver',  
      		'CO' => 'Sender has provided consignment information to carrier in preparation for shipment',     
      		'DE' => 'Sender has deleted consignment information prepared earlier in preparation for shipment',   
      		'DF' => 'Freight item has been delivered', 
      		'FS' => 'The freight item was not able to be delivered and delivery of the consignment is complete',     
      		'IC' => 'Sender has partially completed consignment information for carrier in preparation for shipment',   
      		'IT' => 'Freight item is in transit between two carrier depots (initial/intermediate/final)',     
      		'OD' => 'Freight item is in a local delivery vehicle',   
      		'PU' => 'Carrier has picked up freight item from the sender',    
      		'RC' => 'Consignment information was incorrect or incomplete: corrected information supplied in a new consignment',   
      		'RD' => 'Freight item has been returned to local carrier depot as undeliverable: to be re-delivered on a following day',
      		'RP' => 'Freight item awaiting pickup by carrier',  
      		'UC' => 'Sender has completed consignment information for carrier in preparation for shipment but has not yet finalised the consignment',
      		'UD' => 'Freight item could not be delivered'
   		); 
	} 

	private function serviceDescriptionMap()
    // Service Code => Service Description	
	{ 
		return $this->getJSONArray("ServiceCodes.json");
	} 
	
	private function locationDescriptionMap()
    // Location Code => Location Description	
	{ 
		return $this->getJSONArray("Depots.json");
	}
	
	private function qualityControlDescriptionMap()	
    // Quality Control Code => Quality Control Description	
	{ 
		return $this->getJSONArray("QCCodes.json");
	}
	
	private function podSignatoryDescriptionMap()	
    // POD Description Code => Quality Control Description	
	{ 
   		return array 
   		( 
			'*CHECK ADDRESS' => 'RECEIVER ADDRESS APPEARS TO BE INCORRECT',
			'*CLOSED' => 'RECEIVER PREMISES CLOSED',
			'*LAI' => 'LEFT AS INSTRUCTED',
			'*OTHER RETURN' => 'RETURNED TO CARRIER DEPOT',
			'*REFUSED DELIV.' => 'RECEIVER REFUSED DELIVERY',
			'*UAS' => 'SIGNATURE NOT REQUIRED - AIR SATCHEL',

			'*UNDELIVERED' => 'UNDELIVERED'
		);
	}
	private function stateAbbreviationMap()	
    // Australian State Code => State Abbreviation
	{ 
   		return array 
   		( 
			'0' => 'NT',
			'2' => 'NSW',
			'3' => 'VIC',
			'4' => 'QLD',
			'5' => 'SA',
			'6' => 'WA',
			'7' => 'TAS',
			'9' => 'INT',
			'A' => 'ACT',
			'Z' => 'NZ'
		);
	}
	
		private function consignmentTransitStateMap()	
    // Consignment transit state code => Description
	{ 
   		return array 
   		( 
			'AT' => 'POD Attachment',
			'B' => 'Booked in for Delivery',
			'C' => 'Late Data',
			'D' => 'Delivered',
			'E' => 'Pickup Cancelled',
			'F' => 'Final Shortage',
			'G' => 'Refused - Pending Further Instructions',
			'H' => 'Held',
			'I' => 'Scanned in Transit',
			'IM' => 'POD Image',
			'J' => 'Held at Delivery Depot',
			'L' => 'Label Scanned In Transit',
			'M' => 'On Board for Delivery',
			'N' => 'NZ Scanning',
			'O' => 'POD On File',
			'P' => 'Picked Up',
			'Q' => 'Truck Out',
			'QC' => 'Inspection Quality Control',
			'R' => 'Unsuccessful Delivery',
			'S' => 'Shortage',
			'T' => 'POD Returned',
			'U' => 'Left as Instructed',
			'V' => 'Redeliver',
			'W' => 'Transfer',
			'X' => 'Reconsigned',
			'Y' => 'Returned To Sender',
			'Z' => 'Registered For Bookin'
		);
	}

		private function freightItemTransitStateMap()	
    // Freight Item transit state code => Description
	{ 
   		return array 
   		( 
			'A' => 'Scanned at Delivery Depot',
			'C' => 'Scanned at Control Depot',
			'D' => 'Item Delivered',
			'E' => 'Pickup Cancelled',
			'F' => 'Freight Handling',
			'H' => 'Held',
			'I' => 'Scanned in Transit',
			'J' => 'Held at Delivery Depot',
			'K' => 'Known Not Seen',
			'M' => 'On Board for Delivery',
			'O' => 'POD On File',
			'P' => 'Picked Up',
			'R' => 'Scanned at Receiver Location',
			'S' => 'In Transit',
			'T' => 'POD Returned',
			'U' => 'Unknown (or Uncertain)',
			'W' => 'Transfer',
			'X' => 'Reconsigned',
			'Z' => 'Registered For Bookin'
		);
	}

	private function suburbsMap()
	// Suburb => Postcode and State
	// Note: Suburb is as per StarTrack list of suburbs
	{
		return $this->getJSONArray("Locations.json");
	}
	
	private function nearestDepotMap()	
    // Postcode => Nearest Depot	
	{ 
		return $this->getJSONArray("NearestDepots.json");
	}
	
	private function fastServiceCodeMap()	
    // Service Code => Fast Service Code
	{ 
		return $this->getJSONArray("FastServiceCodes.json");
	}
		
	private function getJSONArray($fileSpec)
	// Returns associative array corresponding to the specified JSON file
	
	// If customer is globally caching the JSON files, calls back to customer code to get the value to be returned.
	// Otherwise, locally caches the JSON files
	
	{
		$callback = array('globalJSONCache', 'getJSONFileContents');			// Callback to CustomerConnect.php
		if (is_callable($callback))												// Has customer implemented global caching?
		{
			return call_user_func($callback, $fileSpec); 						// Yes, get value to return from customer code
		}
		else
		{
			static $cache = array();
			if (is_null($cache[$fileSpec]))										// Not already in the local cache?
			{
				$jO = new SecurePath;
				$fullPath = $jO->getSecurePath() . $fileSpec;						// Note: Directory containing JSON files comes 
																				// from customer code
				$contents = file_get_contents($fullPath) or die("Problem with JSON file $fullPath");  // No, get file contents
				$cache[$fileSpec] = json_decode($contents, true);				// Store in local cache
			}
			return $cache[$fileSpec];
		}
	}
	
}

?>
