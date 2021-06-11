<?php
require_once __DIR__.'/../../vendor/autoload.php';

require_once(dirname(__FILE__).'/ParseDotStatXML.php');

//adding SOAP authentication
require_once(dirname(__FILE__).'/../../BaseURL.php');
require_once(dirname(__FILE__).'/../../02projects/'.$themeURL.'/urlMapperConfig.php');


// fetch XML data via SOAP interface and write into JSON file
function pullSOAPJSON($inputFile, $outputFile, $renames = null) {
	$parser = new OECDSOAPParser();
	$data = $parser->fetchData($inputFile);
	if (is_null($renames)) {
		$data->renameKeys();
	} else {
		$data->renameKeysInDimensions($renames);
	}
	$data->saveJSON($outputFile);
}

class OECDSOAPParser {
	// authentication data
	public $logon;
	public $domain;
	public $password;

	function __construct() {
	// initialize authentication data
		global $ConfigSOAP;
		if (!isset($ConfigSOAP['logon'])) {
			die("\$ConfigSOAP['logon'] not set\n");
		}
		if (!isset($ConfigSOAP['domain'])) {
			die("\$ConfigSOAP['domain'] not set\n");
		}
		if (!isset($ConfigSOAP['password'])) {
			die("\$ConfigSOAP['password'] not set\n");
		}
		$this->logon = $ConfigSOAP['logon'];
		$this->domain = $ConfigSOAP['domain'];
		$this->password = $ConfigSOAP['password'];
	}

	// fetch XML data via SOAP interface and write into JSON file
	public function fetchData($inputFile) {
		if (!headers_sent()) header('Content-Type: text/plain');
		printf("OECDSOAPParser: parsing XML data from %s\n", $inputFile);
		ob_start();
		include($inputFile);
		$query = ob_get_clean();
		if ($query === FALSE) die("could not open $inputFile\n");
		$xmlstr = $this->getGenericData($query);
		$parser = new OECDDataParser();
		return $parser->parseXML($xmlstr);
	}

	// called in case of an error in the SOAP request
	private function soapFailure($client, $ex) {
		if ($client === NULL) {
			die("early SOAP Failure: $ex\n");
		}
  		die("SOAP Failure: $ex\n".
		    $client->__getLastRequestHeaders()."\n".
  		    $client->__getLastRequest()."\n".
  		    $client->__getLastResponseHeaders()."\n".
  	            $client->__getLastResponse()."\n");
	}

	// return results of GetGenericData SOAP method
	public function getGenericData($query) {
		try {
			$client = new SoapClient('https://stats.oecd.org/SDMXWS/sdmx.asmx?WSDL', array(
					'soap_version' => SOAP_1_2,
					'login'        => $this->domain . '\\' . $this->logon,
					'password'     => $this->password,
					'stream_context' => stream_context_create(array(
						'ssl' => array(
							'verify_peer' => TRUE,
							'allow_self_signed' => FALSE,
							'cafile' => \Composer\CaBundle\CaBundle::getBundledCaBundlePath(),
							'verify_depth' => 5,
							'CN_match' => 'stats.oecd.org',
						),
					)),
					'trace'        => TRUE));
			$query = '<QueryMessage xmlns="http://stats.oecd.org/OECDStatWS/SDMX/">' . $query . '</QueryMessage>';
			$res = $client->GetGenericData(array('QueryMessage' => new SoapVar($query, XSD_ANYXML, 'QueryMessage')));
			return $res->GetGenericDataResult->any;
		} catch (Exception $ex) { $this->soapFailure($client, $ex); }
	}
}
