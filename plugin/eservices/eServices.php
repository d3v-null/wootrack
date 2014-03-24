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
    private s_path;
    private wsdl_file;
    private forced_SSL_ver;
    
    public function __construct(string secure_path, string wsdl_file, string forced_SSL_ver){
        //TO DO : assert secure_path is / terminated
        $this->s_path       = secure_path;
        $this->wsdl_file    = wsdl_file;
        $this->forced_SSL_ver     = forced_SSL_ver;
    }
	
    public function invokeWebService(array $connection, $operation, array $request)
	// Invokes StarTrack web service using supplied request and returns the response
	// For details see sample applications and the Usage Guide
	{
        try
        {		
            $clientArguments = array(
                'exceptions' => true,			
                'encoding' => 'UTF-8',
                'soap_version' => SOAP_1_1,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS
            );

            $oClient = new WSSoapClient($this->s_path . $this->wsdl_file, $clientArguments);	
            
            $oClient->__setUsernameToken($connection['username'], $connection['password']);	
            
            if($this->forced_SSL_ver){
                $oClient->__setSSLForce($this->forced_SSL_ver, $this->s_path . 'cacert.crt' );
            }

            return $oClient->__soapCall($operation, $request);																				
        }
        catch (SoapFault $e)
        {
            throw new SoapFault($e->faultcode, $e->faultstring, NULL, "");//$e->detail);
            // It is left to the caller to handle this exception as desired
        }
	}
}

?>
