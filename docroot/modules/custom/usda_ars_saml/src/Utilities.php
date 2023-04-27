<?php

namespace Drupal\usda_ars_saml;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Drupal;
use Exception;

/**
 * This file is part of USDA ARS SAML plugin.
 */
class Utilities {

  /**
   * Creates SAML 2.0 Authentication Request.
   *
   * @param string $acsUrl
   *   SAML ACS URL.
   * @param string $issuer
   *   The Issuer ID.
   * @param string $nameid_format
   *   NameID format.
   * @param bool $force_authn
   *   ForceAuthn flag.
   * @param bool $rawXml
   *   Return raw XML string.
   *
   * @return string
   *   SAML 2.0 Authentication Request.
   */
  public static function createAuthnRequest(string $acsUrl, string $issuer, string $nameid_format, bool $force_authn = FALSE, bool $rawXml = FALSE) {

    $requestXmlStr = '<?xml version="1.0" encoding="UTF-8"?>' .
      '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="' . Utilities::generateID() .
      '" Version="2.0" IssueInstant="' . Utilities::generateTimestamp() . '"';

    if ($force_authn) {
      $requestXmlStr .= ' ForceAuthn="true"';
    }
    $requestXmlStr .= ' ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"';
    if (!empty($acsUrl)) {
      $requestXmlStr .= ' AssertionConsumerServiceURL="' . $acsUrl . '"';
    }
    $requestXmlStr .= '><saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $issuer .
      '</saml:Issuer><samlp:NameIDPolicy AllowCreate="true" Format="' . $nameid_format . '"/></samlp:AuthnRequest>';

    if ($rawXml) {
      return $requestXmlStr;
    }
    $deflatedStr = gzdeflate($requestXmlStr);
    $base64EncodedStr = base64_encode($deflatedStr);
    $urlEncoded = urlencode($base64EncodedStr);
    return $urlEncoded;
  }

  /**
   * Generates ID for samlp:AuthnRequest.
   *
   * @return string
   *   The ID.
   */
  public static function generateID() {
    return '_' . self::stringToHex(self::generateRandomBytes(21));
  }

  /**
   * Converts string to Hex format.
   *
   * @param $bytes
   *
   * @return string
   */
  public static function stringToHex($bytes) {
    $ret = '';
    for ($i = 0; $i < strlen($bytes); $i++) {
      $ret .= sprintf('%02x', ord($bytes[$i]));
    }
    return $ret;
  }

  /**
   * Generates a string of pseudo-random bytes.
   *
   * @param int $length
   *   The length of the desired string of bytes.
   *
   * @return false|string
   *   The generated string of bytes on success, or false on failure.
   */
  public static function generateRandomBytes($length) {
    return openssl_random_pseudo_bytes($length);
  }

  /**
   * Generates formatted GMT/UTC date/time.
   *
   * @param $instant
   *
   * @return false|string
   *   Formatted date string.
   */
  public static function generateTimestamp($instant = NULL) {
    if (is_null($instant)) {
      $instant = time();
    }
    return gmdate('Y-m-d\TH:i:s\Z', $instant);
  }

  /**
   * Parses NameId DOMElement.
   *
   * @param DOMElement $xml
   *   The NameId DOMElement to parse.
   *
   * @return array
   *   Array of NameId value and attributes.
   */
  public static function parseNameId(DOMElement $xml) {

    $ret = ['Value' => trim($xml->textContent)];

    foreach (array('NameQualifier', 'SPNameQualifier', 'Format') as $attr) {
      if ($xml->hasAttribute($attr)) {
        $ret[$attr] = $xml->getAttribute($attr);
      }
    }

    return $ret;
  }

  /**
   * Converts SAML2 timestamp to Unix timestamp.
   *
   * @param $time
   *   SAML2 timestamp.
   *
   * @return false|int
   *   Unix timestamp if successful, FALSE otherwise.
   */
  public static function xsDateTimeToTimestamp($time) {

    $matches = array();
    // We use a very strict regex to parse the timestamp.
    $regex = '/^(\\d\\d\\d\\d)-(\\d\\d)-(\\d\\d)T(\\d\\d):(\\d\\d):(\\d\\d)(?:\\.\\d+)?Z$/D';
    if (preg_match($regex, $time, $matches) == 0) {
      Drupal::messenger()
        ->addMessage(t('Invalid SAML2 timestamp passed to xsDateTimeToTimestamp.'), 'error');
      return FALSE;
    }

    // Extract the different components of the time from the  matches in the regex.
    // Function intval() will ignore leading zeroes in the string.
    $year = intval($matches[1]);
    $month = intval($matches[2]);
    $day = intval($matches[3]);
    $hour = intval($matches[4]);
    $minute = intval($matches[5]);
    $second = intval($matches[6]);

    // We use gmmktime because the timestamp will always be given in UTC.
    $ts = gmmktime($hour, $minute, $second, $month, $day, $year);

    return $ts;
  }

  /**
   * Extracts strings from DOMElement children.
   *
   * @param \DOMElement $parent
   * @param $namespaceURI
   * @param $localName
   *
   * @return array
   */
  public static function extractStrings(DOMElement $parent, $namespaceURI, $localName) {
    $ret = array();
    for ($node = $parent->firstChild; $node !== NULL; $node = $node->nextSibling) {
      if ($node->namespaceURI !== $namespaceURI || $node->localName !== $localName) {
        continue;
      }
      $ret[] = trim($node->textContent);
    }

    return $ret;
  }

  /**
   * Validates DOMElement.
   *
   * @param DOMElement $root
   *   The DOMElement.
   *
   * @return array|false
   * @throws \Exception
   */
  public static function validateElement(DOMElement $root) {
    // Create an XML security object.
    $objXMLSecDSig = new XMLSecurityDSig();

    /* Both SAML messages and SAML assertions use the 'ID' attribute. */
    $objXMLSecDSig->idKeys[] = 'ID';

    // Locate the XMLDSig Signature element to be used.
    $signatureElement = self::xpQuery($root, './ds:Signature');

    if (count($signatureElement) === 0) {
      // We don't have a signature element to validate.
      return FALSE;
    }
    elseif (count($signatureElement) > 1) {
      echo sprintf("XMLSec: more than one signature element in root.");
    }

    $signatureElement = $signatureElement[0];
    $objXMLSecDSig->sigNode = $signatureElement;

    // Canonicalize the XMLDSig SignedInfo element in the message.
    $objXMLSecDSig->canonicalizeSignedInfo();
    // Validate referenced xml nodes */
    if (!$objXMLSecDSig->validateReference()) {
      throw new Exception("XMLSecDSig: Reference validation failed");
    }

    // Check that $root is one of the signed nodes.
    $rootSigned = FALSE;
    /** @var DOMNode $signedNode */
    foreach ($objXMLSecDSig->getValidatedNodes() as $signedNode) {
      if ($signedNode->isSameNode($root)) {
        $rootSigned = TRUE;
        break;
      }
      elseif ($root->parentNode instanceof DOMElement && $signedNode->isSameNode($root->ownerDocument)) {
        // $root is the root element of a signed document.
        $rootSigned = TRUE;
        break;
      }
    }

    if (!$rootSigned) {
      throw new Exception("XMLSec: The root element is not signed.");
    }

    // Now we extract all available X509 certificates in the signature element.
    $certificates = array();
    foreach (self::xpQuery($signatureElement, './ds:KeyInfo/ds:X509Data/ds:X509Certificate') as $certNode) {
      $certData = trim($certNode->textContent);
      $certData = str_replace(array("\r", "\n", "\t", ' '), '', $certData);
      $certificates[] = $certData;
    }

    $ret = array(
      'Signature' => $objXMLSecDSig,
      'Certificates' => $certificates,
    );
    return $ret;
  }

  /**
   * Executes DOMXPath query and returns results.
   *
   * @param \DOMNode $node
   * @param $query
   *
   * @return array
   */
  public static function xpQuery(DOMNode $node, $query) {
    static $xpCache = NULL;

    if ($node->ownerDocument == NULL) {
      $doc = $node;
    }
    else {
      $doc = $node->ownerDocument;
    }
    if ($xpCache === NULL || !$xpCache->document->isSameNode($doc)) {
      $xpCache = new DOMXPath($doc);
      $xpCache->registerNamespace('soap-env', 'http://schemas.xmlsoap.org/soap/envelope/');
      $xpCache->registerNamespace('saml_protocol', 'urn:oasis:names:tc:SAML:2.0:protocol');
      $xpCache->registerNamespace('saml_assertion', 'urn:oasis:names:tc:SAML:2.0:assertion');
      $xpCache->registerNamespace('saml_metadata', 'urn:oasis:names:tc:SAML:2.0:metadata');
      $xpCache->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
      $xpCache->registerNamespace('xenc', 'http://www.w3.org/2001/04/xmlenc#');
    }

    $results = $xpCache->query($query, $node);
    $ret = array();
    for ($i = 0; $i < $results->length; $i++) {
      $ret[$i] = $results->item($i);
    }

    return $ret;
  }

  /**
   * Validates Issuer and Audience in the SAML Response.
   *
   * @param $samlResponse
   * @param $issuerToValidateAgainst
   * @param $base_url
   *
   * @return bool
   */
  public static function validateIssuerAndAudience($samlResponse, $issuerToValidateAgainst, $base_url) {
    $issuer = current($samlResponse->getAssertions())->getIssuer();
    $audience = current(current($samlResponse->getAssertions())->getValidAudiences());
    if (strcmp($issuerToValidateAgainst, $issuer) === 0) {
      if (strcmp($audience, $base_url) === 0) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns Identity Provider Entity ID.
   *
   * @return string
   */
  public static function getIssuer() {
    $saved_issuer = Drupal::config('usda_ars_saml.settings')
      ->get('ars_saml_idp_issuer');
    return $saved_issuer;
  }

  /**
   * Builds AssertionConsumerServiceURL.
   *
   * @return string
   */
  public static function getAcsUrl() {
    $b_url = self::getBaseUrl();
    return substr($b_url, -1) == '/' ? $b_url . 'saml_response' : $b_url . '/saml_response';
  }

  /**
   * Returns Service Provider base URL.
   * If CDN provides SSL, HTTP needs to be replaced by HTTPS.
   *
   * @return string
   */
  public static function getBaseUrl() {
    global $base_url;
    if (strpos($base_url, 'http://') === 0) {
      $sp_base_url = 'https://' . substr($base_url, 7);
    }
    else {
      $sp_base_url = $base_url;
    }
    return $sp_base_url;
  }

  public static function Print_SAML_Request($samlRequestResponceXML, $type) {
    header("Content-Type: text/html");
    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->formatOutput = TRUE;
    $doc->loadXML($samlRequestResponceXML);
    if ($type == 'displaySAMLRequest') {
      $show_value = 'SAML Request';
    }
    else {
      $show_value = 'SAML Response';
    }
    $out = $doc->saveXML();
    $out1 = htmlentities($out);
    $out1 = rtrim($out1);

    echo '<div class="mo-display-logs" ><p type="text"   id="SAML_type">' . $show_value . '</p></div>

			<div type="text" id="SAML_display" class="mo-display-block"><pre class=\'brush: xml;\'>' . $out1 . '</pre></div>
			<br>
			<div style="margin:3%;display:block;text-align:center;">

			<div style="margin:3%;display:block;text-align:center;" >

            </div>
			<button id="copy" onclick="copyDivToClipboard()"  style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;" >Copy</button>
			&nbsp;
               <button id="dwn_btn" onclick="test_download()" style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;">Download</button>
			</div>
			</div>

			';

    exit;
  }

  /**
   * Prints assertion attributes as HTML table.
   *
   * @param $attrs
   *   The attributes array.
   *
   * @return string
   *   HTML table.
   */
  public static function printAttributes($attrs) {

    $attr_table = '<div style="font-family:Calibri;padding:0 3%;">';
    $attr_table .= '<br/><p style="font-weight:bold;font-size:14pt;margin-left:1%;">ATTRIBUTES RECEIVED:</p>
        <table style="border-collapse:collapse;border-spacing:0; display:table;width:100%; font-size:14pt;background-color:#EDEDED;">
        <tr style="text-align:center;"><td style="font-weight:bold;border:2px solid #949090;padding:2%;">ATTRIBUTE NAME</td><td style="font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;">ATTRIBUTE VALUE</td></tr>';
    if (!empty($attrs)) {
      foreach ($attrs as $key => $value) {
        $attr_table .= "<tr><td style='font-weight:bold;border:2px solid #949090;padding:2%;'>" . $key . "</td><td style='padding:2%;border:2px solid #949090; word-wrap:break-word;'>" . $value[0] /*implode("<br/>",$value)*/ . "</td></tr>";
      }
    }
    else {
      $attr_table .= "<tr><td style='font-weight:bold;border:2px solid #949090;padding:2%;'>" . 'ATTRIBUTES' . "</td><td style='padding:2%;border:2px solid #949090; word-wrap:break-word;'>" . 'NONE' . "</td></tr>";
    }
    $attr_table .= '</table></div>';
    $attr_table .= '<div style="margin:3%;display:block;text-align:center;"><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="button" value="Done" onClick="self.close();"></div>';

    return $attr_table;
  }

}
