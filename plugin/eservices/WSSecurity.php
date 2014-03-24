<?PHP
// namespace startrackexpress\eservices;	// *** Uncomment this line if PHP V5.3 or later ***
// use SoapClient, SoapVar, SoapHeader; 	// *** Uncomment this line if PHP V5.3 or later ***

// Copyright © 2010 StarTrack
// All rights reserved

// <document_root>/MyWebSite/WSSecurity.php
// Class:   WSSoapClient
// StarTrack
// 27 September 2012
// Version 4.5

// Provides WS-Security support for eServices
// Uses PHP5 SOAP extension


/* Calling sequence (use in place of SoapClient):

  require_once WSSecurity.php;
  $wsdl = "WSDL address";
  $oSC = new WSSoapClient($wsdl, $arguments);
  $oSC->__setUsernameToken('username', 'passphrase');
  $params = array(    ); 		// The service parameters
  $result=$oSC->__soapCall('method_name', $params);
*/

class WSSoapClient extends SoapClient
{
	private $username;
	private $password;
    private $SSLForce;
    private $cacert;
    
// Generates a WS-Security header
	private function WsSecurityHeader()
	{
        // Use PasswordText authentication
        $created = gmdate('Y-m-d\TH:i:s\Z');
        $nonce = mt_rand(); 
        $authentication = '
<wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
<wsse:UsernameToken wsu:Id="UsernameToken-1" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
    <wsse:Username>' . $this->username . '</wsse:Username>
    <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . 
    $this->password . '</wsse:Password>
    <wsse:Nonce>' . base64_encode(pack('H*', $nonce)) . '</wsse:Nonce>
    <wsu:Created xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">' . $created . '</wsu:Created>
   </wsse:UsernameToken>
</wsse:Security>
';

		$authValues = new SoapVar($authentication, XSD_ANYXML); 
		$header = new SoapHeader("http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd", "Security",$authValues, true);

		return $header;
	}

	// Sets a username and passphrase
	public function __setUsernameToken($username,$password)
	{
		$this->username = $username;
		$this->password = $password;
	}
    
    public function __setSSLForce($ver, $cacert){
        $this->SSLForce = $ver;
        $this->cacert   = $cacert;
    }

	// Overrides the original method, adding the security header

	public function __soapCall($function_name, $arguments, $options=NULL, $input_headers=NULL, &$output_headers=NULL)
	{
		try
		{
			$result = parent::__soapCall($function_name, $arguments, $options, $this->WsSecurityHeader());
			return $result;
		}
		catch (SoapFault $e)
		{
			throw new SoapFault($e->faultcode, $e->faultstring, NULL, '');//$e->detail);
		}
	}
	
	public function __doRequest($request, $location, $action, $version, $one_way=NULL)
	{
		/*$cb = new WSSecurityCallbacks();
		if( $cb->displaySoapRequests() )	// Display SOAP request XML prior to call for debugging? Driven by parameter setting in CustomerConnect.php.
		{
			echo '<p>*** Request XML Prior to __doRequest ***</p>';	// Yes
			echo "<p> " . htmlspecialchars($request) . " </p>" ;
		}*/

		if( $this->SSLForce )		// Force SSL version? Driven by parameter setting in CustomerConnect.php.
		{
            $h = curl_init( $location );		// Init with URL
            curl_setopt( $h, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $h, CURLOPT_HTTPHEADER, Array( "SOAPAction: $action", "Content-Type: text/xml; charset=utf-8" ) );
            curl_setopt( $h, CURLOPT_POSTFIELDS, $request );
            curl_setopt( $h, CURLOPT_SSLVERSION, $this->SSLForce );
            // curl_setopt( $h, CURLOPT_SSL_VERIFYHOST, false );				// Omit validation of the StarTrack server's 
                                                                                // Verisign SSL certificate (not recommended)
            curl_setopt( $h, CURLOPT_CAINFO, $this->cacert );						// On Windows, cURL needs to be told about Verisign root cert
            $response = curl_exec( $h );										// Perform SOAP call
            if( empty( $response ) )
            {
                throw new SoapFault( 'CURL Error: '.curl_error( $h ), curl_errno( $h ) );
            }
            curl_close( $h );
            return $response;
		}
		else
		{
			return parent::__doRequest($request, $location, $action, $version);	// No, use default SSL/TLS
		}
	}
}
?>
