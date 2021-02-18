<?php

if (!isset($secure)) {
	echo 'invalid url';
	die();
}

class SalesforceInboundHandler
{

	public $errorMsg;
	public $isError;
	public $size = 0;
	public $rawXml;
	public $records = array();
	public $isSample;


	public function getJsonWithFields($fields)
	{

		return  json_encode($this->getDataWithFields($fields));
	}

	public function getDataWithFields($fields)
	{
		$ret = array();
		if (count($this->records) > 0) {

			foreach ($this->records as $record) {
				$rec = array();
				foreach ($fields as $field) {
					if (isset($record[$field])) {
						$rec[$field] =  $record[$field];
					} else {
						$rec[$field] =  null;
					}
				}
				array_push($ret, $rec);
			}
		}

		return $ret;
	}

	public function getSample()
	{
		return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">

				  <soapenv:Body>

					<notifications xmlns="http://soap.sforce.com/2005/09/outbound">

				    <OrganizationId>00D5Y000001MPM7UAO</OrganizationId>

				    <ActionId>04k5Y0000008Q1hQAE</ActionId>

				    <SessionId xsi:nil="true"/>

				    <EnterpriseUrl>https://renderatortechnologieslabsi-dev-ed.my.salesforce.com/services/Soap/c/51.0/00D5Y000001MPM7</EnterpriseUrl>

				    <PartnerUrl>https://renderatortechnologieslabsi-dev-ed.my.salesforce.com/services/Soap/u/51.0/00D5Y000001MPM7</PartnerUrl>

				    <Notification>

				     <Id>04l5Y00002lRDRNQA4</Id>

				     <sObject xsi:type="sf:Opportunity" xmlns:sf="urn:sobject.enterprise.soap.sforce.com">

						<sf:Id>0065Y00001Suvw6QAB</sf:Id>
						
						<sf:Modelo_Residencial__c>a1x2E000003jQ23QAE</sf:Modelo_Residencial__c>

						<sf:Name>test</sf:Name>

						<sf:PREMIUM_CHARGES__c>5968.68</sf:PREMIUM_CHARGES__c>

						<sf:PROYECTORESIDENCIAL__c>a0B2E00000beoLJUAY</sf:PROYECTORESIDENCIAL__c>

						<sf:StageName>Prospecting</sf:StageName>

						<sf:UNIT__C>101</sf:UNIT__C>

						<sf:STATUS>No disponible</sf:STATUS>
						
						<sf:VALOR_DE_COMPRA_VENTA__c>142000.0</sf:VALOR_DE_COMPRA_VENTA__c>

				     </sObject>
				    </Notification>

				   </notifications>

				  </soapenv:Body>
				 </soapenv:Envelope>';
	}

	function __construct($isSample = false)
	{
		$this->isSample = $isSample;
	}



	public function SalesforceToMysqlSyncWithApi()
	{


		ob_start();
		$this->records = array();
		$this->size = 0;
		$this->isError =  false;
		$this->errorMsg = '';

		$xml =  $this->getContent();
		try {

			$content  = $this->xmlstr_to_array($xml);
			$records = array();
			if (isset($content['soapenv:Body']) && isset($content['soapenv:Body']['notifications']) && isset($content['soapenv:Body']['notifications']['Notification']['sObject'])) {

				$record = array();
				foreach ($content['soapenv:Body']['notifications']['Notification']['sObject'] as $key => $value) {
					$record[strtolower(str_replace('sf:', '', $key))] = $value;
				}
				array_push($records, $record);
			} else if (isset($content['soapenv:Body']) && isset($content['soapenv:Body']['notifications']) && isset($content['soapenv:Body']['notifications']['Notification'])) {
				foreach ($content['soapenv:Body']['notifications']['Notification']  as $k  => $v) {
					$record = array();
					foreach ($v['sObject'] as $key => $value) {
						$record[strtolower(str_replace('sf:', '', $key))] = $value;
					}
					array_push($records, $record);
				}
			} else {

				$this->errorMsg = 'something wrong with xml' . $xml . '.';
				$this->isError =  true;
				$this->size = 0;
				return false;
			}
			$this->records = $records;
			$this->size = count($records);
		} catch (Exception $e) {
			$this->errorMsg =  $e->getMessage();
			$this->isError =  true;
			$this->size = 0;
			return false;
		}

		return true;
	}

	public function respondSuccess()
	{
		$tf = true;
		print '<?xml version="1.0" encoding="UTF-8"?>
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
				  <soapenv:Body>
				                    <notifications xmlns="http://soap.sforce.com/2005/09/outbound">
				      <Ack>' . $tf . '</Ack>
				    </notifications>
				                  </soapenv:Body>
				</soapenv:Envelope>';
	}

	public function respondBad($msg)
	{ //at this moment salesforce does not have bad message response system so we are just returning string;
		echo   $msg;
	}


	private function getContent()
	{
		if ($this->isSample == true) {
			return $this->getSample();
		}
		return file_get_contents('php://input');
	}


	public function domnode_to_array($node)
	{
		$output = array();
		switch ($node->nodeType) {
			case XML_CDATA_SECTION_NODE:
			case XML_TEXT_NODE:
				$output = trim($node->textContent);
				break;
			case XML_ELEMENT_NODE:
				for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
					$child = $node->childNodes->item($i);
					$v = $this->domnode_to_array($child);
					if (isset($child->tagName)) {
						$t = $child->tagName;
						if (!isset($output[$t])) {
							$output[$t] = array();
						}
						$output[$t][] = $v;
					} elseif ($v || $v === '0') {
						$output = (string) $v;
					}
				}
				if ($node->attributes->length && !is_array($output)) { //Has attributes but isn't an array
					$output = array('@content' => $output); //Change output into an array.
				}
				if (is_array($output)) {
					if ($node->attributes->length) {
						$a = array();
						foreach ($node->attributes as $attrName => $attrNode) {
							$a[$attrName] = (string) $attrNode->value;
						}
						$output['@attributes'] = $a;
					}
					foreach ($output as $t => $v) {
						if (is_array($v) && count($v) == 1 && $t != '@attributes') {
							$output[$t] = $v[0];
						}
					}
				}
				break;
		}
		return $output;
	}


	private	function xmlstr_to_array($xmlstr)
	{
		$doc = new DOMDocument();
		$doc->loadXML($xmlstr);
		$root = $doc->documentElement;
		$output = $this->domnode_to_array($root);
		$output['@root'] = $root->tagName;
		return $output;
	}
}
