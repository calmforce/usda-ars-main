<?php

namespace Drupal\usda_ars_saml;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Exception;

class XMLSecurityDSig
{
  const XMLDSIGNS = 'http://www.w3.org/2000/09/xmldsig#';
  const SHA1 = 'http://www.w3.org/2000/09/xmldsig#sha1';
  const SHA256 = 'http://www.w3.org/2001/04/xmlenc#sha256';
  const SHA384 = 'http://www.w3.org/2001/04/xmldsig-more#sha384';
  const SHA512 = 'http://www.w3.org/2001/04/xmlenc#sha512';
  const RIPEMD160 = 'http://www.w3.org/2001/04/xmlenc#ripemd160';

  const C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
  const C14N_COMMENTS = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315#WithComments';
  const EXC_C14N = 'http://www.w3.org/2001/10/xml-exc-c14n#';
  const EXC_C14N_COMMENTS = 'http://www.w3.org/2001/10/xml-exc-c14n#WithComments';

  const template = '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <ds:SignedInfo>
    <ds:SignatureMethod />
  </ds:SignedInfo>
</ds:Signature>';

  const BASE_TEMPLATE = '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
  <SignedInfo>
    <SignatureMethod />
  </SignedInfo>
</Signature>';

  /** @var DOMElement|null */
  public $sigNode = null;

  /** @var array */
  public $idKeys = array();

  /** @var array */
  public $idNS = array();

  /** @var string|null */
  private $signedInfo = null;

  /** @var DomXPath|null */
  private $xPathCtx = null;

  /** @var string|null */
  private $canonicalMethod = null;

  /** @var string */
  private $prefix = '';

  /** @var string */
  private $searchpfx = 'secdsig';

  /**
   * This variable contains an associative array of validated nodes.
   * @var array|null
   */
  private $validatedNodes = null;

  /**
   * @param string $prefix
   */
  public function __construct(string $prefix = 'ds')
  {
    $template = self::BASE_TEMPLATE;
    if (!empty($prefix)) {
      $this->prefix = $prefix . ':';
      $search = array("<S", "</S", "xmlns=");
      $replace = array("<$prefix:S", "</$prefix:S", "xmlns:$prefix=");
      $template = str_replace($search, $replace, $template);
    }
    $sigdoc = new DOMDocument();
    $sigdoc->loadXML($template);
    $this->sigNode = $sigdoc->documentElement;
  }

  /**
   * Returns the XPathObj or null if xPathCtx is set and sigNode is empty.
   *
   * @return DOMXPath|null
   *   XPathObj or NULL.
   */
  private function getXPathObj() {
    if (empty($this->xPathCtx) && !empty($this->sigNode)) {
      $xpath = new DOMXPath($this->sigNode->ownerDocument);
      $xpath->registerNamespace('secdsig', self::XMLDSIGNS);
      $this->xPathCtx = $xpath;
    }
    return $this->xPathCtx;
  }

  /**
   * Canonicalizes Data.
   *
   * @param DOMNode $node
   * @param string $canonicalmethod
   * @param null|array $arXPath
   * @param null|array $prefixList
   *
   * @return string
   */
  private function canonicalizeData($node, $canonicalmethod, $arXPath = null, $prefixList = null) {
    $exclusive = false;
    $withComments = false;
    switch ($canonicalmethod) {
      case 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315':
        $exclusive = false;
        $withComments = false;
        break;
      case 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315#WithComments':
        $withComments = true;
        break;
      case 'http://www.w3.org/2001/10/xml-exc-c14n#':
        $exclusive = true;
        break;
      case 'http://www.w3.org/2001/10/xml-exc-c14n#WithComments':
        $exclusive = true;
        $withComments = true;
        break;
    }

    if (is_null($arXPath) && ($node instanceof DOMNode) && ($node->ownerDocument !== null) && $node->isSameNode($node->ownerDocument->documentElement)) {
      // Check for any PI or comments as they would have been excluded.
      $element = $node;
      while ($refnode = $element->previousSibling) {
        if ($refnode->nodeType == XML_PI_NODE || (($refnode->nodeType == XML_COMMENT_NODE) && $withComments)) {
          break;
        }
        $element = $refnode;
      }
      if ($refnode == null) {
        $node = $node->ownerDocument;
      }
    }

    return $node->C14N($exclusive, $withComments, $arXPath, $prefixList);
  }

  /**
   * Canonicalizes Signed Info.
   *
   * @return null|string
   */
  public function canonicalizeSignedInfo() {

    $doc = $this->sigNode->ownerDocument;
    $canonicalmethod = null;
    if ($doc) {
      $xpath = $this->getXPathObj();
      $query = "./secdsig:SignedInfo";
      $nodeset = $xpath->query($query, $this->sigNode);
      if ($signInfoNode = $nodeset->item(0)) {
        $query = "./secdsig:CanonicalizationMethod";
        $nodeset = $xpath->query($query, $signInfoNode);
        if ($canonNode = $nodeset->item(0)) {
          $canonicalmethod = $canonNode->getAttribute('Algorithm');
        }
        $this->signedInfo = $this->canonicalizeData($signInfoNode, $canonicalmethod);
        return $this->signedInfo;
      }
    }
    return null;
  }

  /**
   * Calculates Digest.
   *
   * @param string $digestAlgorithm
   * @param string $data
   * @param bool $encode
   *
   * @return string
   * @throws Exception
   */
  public function calculateDigest($digestAlgorithm, $data, $encode = true) {
    switch ($digestAlgorithm) {
      case self::SHA1:
        $alg = 'sha1';
        break;
      case self::SHA256:
        $alg = 'sha256';
        break;
      case self::SHA384:
        $alg = 'sha384';
        break;
      case self::SHA512:
        $alg = 'sha512';
        break;
      case self::RIPEMD160:
        $alg = 'ripemd160';
        break;
      default:
        throw new Exception("Cannot validate digest: Unsupported Algorithm <$digestAlgorithm>");
    }

    $digest = hash($alg, $data, true);
    if ($encode) {
      $digest = base64_encode($digest);
    }
    return $digest;

  }

  /**
   * Validates Digest.
   *
   * @param $refNode
   * @param string $data
   *
   * @return bool
   * @throws \Exception
   */
  public function validateDigest($refNode, $data) {
    $xpath = new DOMXPath($refNode->ownerDocument);
    $xpath->registerNamespace('secdsig', self::XMLDSIGNS);
    $query = 'string(./secdsig:DigestMethod/@Algorithm)';
    $digestAlgorithm = $xpath->evaluate($query, $refNode);
    $digValue = $this->calculateDigest($digestAlgorithm, $data, false);
    $query = 'string(./secdsig:DigestValue)';
    $digestValue = $xpath->evaluate($query, $refNode);

    return ($digValue === base64_decode($digestValue));
  }

  /**
   * Processes Transforms.
   *
   * @param $refNode
   * @param DOMNode $objData
   * @param bool $includeCommentNodes
   *
   * @return string
   */
  public function processTransforms($refNode, $objData, $includeCommentNodes = true) {
    $data = $objData;
    $xpath = new DOMXPath($refNode->ownerDocument);
    $xpath->registerNamespace('secdsig', self::XMLDSIGNS);
    $query = './secdsig:Transforms/secdsig:Transform';
    $nodelist = $xpath->query($query, $refNode);
    $canonicalMethod = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    $arXPath = null;
    $prefixList = null;
    foreach ($nodelist AS $transform) {
      $algorithm = $transform->getAttribute("Algorithm");
      switch ($algorithm) {
        case 'http://www.w3.org/2001/10/xml-exc-c14n#':
        case 'http://www.w3.org/2001/10/xml-exc-c14n#WithComments':
          if (!$includeCommentNodes) {
            // We remove comment nodes by forcing it to use a canonicalization
            // without comments.
            $canonicalMethod = 'http://www.w3.org/2001/10/xml-exc-c14n#';
          } else {
            $canonicalMethod = $algorithm;
          }

          $node = $transform->firstChild;
          while ($node) {
            if ($node->localName == 'InclusiveNamespaces') {
              if ($pfx = $node->getAttribute('PrefixList')) {
                $arpfx = array();
                $pfxlist = explode(" ", $pfx);
                foreach ($pfxlist AS $pfx) {
                  $val = trim($pfx);
                  if (!empty($val)) {
                    $arpfx[] = $val;
                  }
                }
                if (count($arpfx) > 0) {
                  $prefixList = $arpfx;
                }
              }
              break;
            }
            $node = $node->nextSibling;
          }
          break;
        case 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315':
        case 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315#WithComments':
          if (!$includeCommentNodes) {
            // We remove comment nodes by forcing it to use a canonicalization
            // without comments.
            $canonicalMethod = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
          } else {
            $canonicalMethod = $algorithm;
          }

          break;
        case 'http://www.w3.org/TR/1999/REC-xpath-19991116':
          $node = $transform->firstChild;
          while ($node) {
            if ($node->localName == 'XPath') {
              $arXPath = array();
              $arXPath['query'] = '(.//. | .//@* | .//namespace::*)[' . $node->nodeValue . ']';
              $arXPath['namespaces'] = array();
              $nslist = $xpath->query('./namespace::*', $node);
              foreach ($nslist AS $nsnode) {
                if ($nsnode->localName != "xml") {
                  $arXPath['namespaces'][$nsnode->localName] = $nsnode->nodeValue;
                }
              }
              break;
            }
            $node = $node->nextSibling;
          }
          break;
      }
    }
    if ($data instanceof DOMNode) {
      $data = $this->canonicalizeData($objData, $canonicalMethod, $arXPath, $prefixList);
    }
    return $data;
  }

  /**
   * Processes Ref Node.
   *
   * @param DOMNode $refNode
   *   The Ref Node.
   *
   * @return bool
   */
  public function processRefNode($refNode) {
    $dataObject = null;
    // Depending on the URI, we may not want to include comments in the result.
    // See: http://www.w3.org/TR/xmldsig-core/#sec-ReferenceProcessingModel

    $includeCommentNodes = true;

    if ($uri = $refNode->getAttribute("URI")) {
      $arUrl = parse_url($uri);
      if (empty($arUrl['path'])) {
        if ($identifier = $arUrl['fragment']) {
          // This reference identifies a node with the given id by using
          // a URI on the form "#identifier". This should not include comments.
          $includeCommentNodes = false;

          $xPath = new DOMXPath($refNode->ownerDocument);
          if ($this->idNS && is_array($this->idNS)) {
            foreach ($this->idNS as $nspf => $ns) {
              $xPath->registerNamespace($nspf, $ns);
            }
          }
          $iDlist = '@Id="' . XPathFilter::filterAttrValue($identifier, XPathFilter::DOUBLE_QUOTE) . '"';
          if (is_array($this->idKeys)) {
            foreach ($this->idKeys as $idKey) {
              $iDlist .= " or @" . XPathFilter::filterAttrName($idKey) . '="' .
                XPathFilter::filterAttrValue($identifier, XPathFilter::DOUBLE_QUOTE) . '"';
            }
          }
          $query = '//*[' . $iDlist . ']';

          $dataObject = $xPath->query($query)->item(0);
        } else {
          $dataObject = $refNode->ownerDocument;
        }
      }
    } else {
      // This reference identifies the root node with an empty URI.
      // This should not include comments.
      $includeCommentNodes = false;

      $dataObject = $refNode->ownerDocument;
    }
    $data = $this->processTransforms($refNode, $dataObject, $includeCommentNodes);
    if (!$this->validateDigest($refNode, $data)) {
      return false;
    }

    if ($dataObject instanceof DOMNode) {
      /* Add this node to the list of validated nodes. */
      if (!empty($identifier)) {
        $this->validatedNodes[$identifier] = $dataObject;
      } else {
        $this->validatedNodes[] = $dataObject;
      }
    }

    return true;
  }

  /**
   * Validates reference.
   *
   * @return bool
   * @throws Exception
   */
  public function validateReference() {
    $docElem = $this->sigNode->ownerDocument->documentElement;
    if (!$docElem->isSameNode($this->sigNode)) {
      if ($this->sigNode->parentNode != null) {
        $this->sigNode->parentNode->removeChild($this->sigNode);
      }
    }
    $xpath = $this->getXPathObj();
    $query = "./secdsig:SignedInfo/secdsig:Reference";
    $nodeset = $xpath->query($query, $this->sigNode);
    if ($nodeset->length == 0) {
      return FALSE;
    }
    // Initialize/reset the list of validated nodes.
    $this->validatedNodes = [];

    foreach ($nodeset AS $refNode) {
      if (!$this->processRefNode($refNode)) {
        // Clear the list of validated nodes.
        $this->validatedNodes = null;
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Retrieves an associative array of the validated nodes.
   *
   * The array will contain the id of the referenced node as the key and
   *  the node itself as the value.
   *
   * @return array
   *   Associative array of validated nodes.
   */
  public function getValidatedNodes()
  {
    return $this->validatedNodes;
  }
}
