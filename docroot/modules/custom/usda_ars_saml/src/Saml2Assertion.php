<?php

namespace Drupal\usda_ars_saml;

use DOMElement;
use DOMText;
use Exception;

class Saml2Assertion {

  protected bool $wasSignedAtConstruction = FALSE;

  private $id;

  private $issueInstant;

  private string $issuer;

  private ?array $nameId;

  private $encryptedNameId;

  private $encryptedAttribute;

  private $notBefore;

  private $notOnOrAfter;

  private $validAudiences;

  private $sessionNotOnOrAfter;

  private $sessionIndex;

  private $authnInstant;

  private $authnContextClassRef;

  private $authnContextDecl;

  private $authnContextDeclRef;

  private $AuthenticatingAuthority;

  private array $attributes;

  private string $nameFormat;

  private $certificates;

  private $signatureData;

  private $SubjectConfirmation;

  /**
   * Constructor for Saml2Assertion class.
   *
   * @param \DOMElement|NULL $xml
   *   The DOMElement.
   *
   * @throws \Exception
   */
  public function __construct(DOMElement $xml = NULL) {
    $this->id = Utilities::generateId();
    $this->issueInstant = Utilities::generateTimestamp();
    $this->issuer = '';
    $this->authnInstant = Utilities::generateTimestamp();
    $this->attributes = array();
    $this->nameFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
    $this->certificates = array();
    $this->AuthenticatingAuthority = array();
    $this->SubjectConfirmation = array();

    if ($xml === NULL) {
      return;
    }

    if (!$xml->hasAttribute('ID')) {
      throw new Exception('Missing ID attribute on SAML assertion.');
    }
    $this->id = $xml->getAttribute('ID');
    if ($xml->getAttribute('Version') !== '2.0') {
      // Currently a very strict check.
      throw new Exception('Unsupported version: ' . $xml->getAttribute('Version'));
    }
    $this->issueInstant = Utilities::xsDateTimeToTimestamp($xml->getAttribute('IssueInstant'));

    $issuer = Utilities::xpQuery($xml, './saml_assertion:Issuer');
    if (empty($issuer)) {
      throw new Exception('Missing <saml:Issuer> in assertion.');
    }
    $this->issuer = trim($issuer[0]->textContent);

    $this->parseConditions($xml);
    $this->parseAuthnStatement($xml);
    $this->parseAttributes($xml);
    $this->parseEncryptedAttributes($xml);
    $this->parseSignature($xml);
    $this->parseSubject($xml);
  }

  /**
   * Parses conditions in assertion.
   *
   * @param DOMElement $xml The assertion XML element.
   *
   * @throws Exception
   */
  private function parseConditions(DOMElement $xml) {
    $conditions = Utilities::xpQuery($xml, './saml_assertion:Conditions');
    if (empty($conditions)) {
      // No <saml:Conditions> node.
      return;
    }
    elseif (count($conditions) > 1) {
      throw new Exception('More than one <saml:Conditions> in <saml:Assertion>.');
    }
    $conditions = $conditions[0];

    if ($conditions->hasAttribute('NotBefore')) {
      $notBefore = Utilities::xsDateTimeToTimestamp($conditions->getAttribute('NotBefore'));
      if ($this->notBefore === NULL || $this->notBefore < $notBefore) {
        $this->notBefore = $notBefore;
      }
    }
    if ($conditions->hasAttribute('NotOnOrAfter')) {
      $notOnOrAfter = Utilities::xsDateTimeToTimestamp($conditions->getAttribute('NotOnOrAfter'));
      if ($this->notOnOrAfter === NULL || $this->notOnOrAfter > $notOnOrAfter) {
        $this->notOnOrAfter = $notOnOrAfter;
      }
    }

    for ($node = $conditions->firstChild; $node !== NULL; $node = $node->nextSibling) {
      if ($node instanceof DOMText) {
        continue;
      }
      if ($node->namespaceURI !== 'urn:oasis:names:tc:SAML:2.0:assertion') {
        throw new Exception('Unknown namespace of condition: ' . var_export($node->namespaceURI, TRUE));
      }
      switch ($node->localName) {
        case 'AudienceRestriction':
          $audiences = Utilities::extractStrings($node, 'urn:oasis:names:tc:SAML:2.0:assertion', 'Audience');
          if ($this->validAudiences === NULL) {
            /* The first (and probably last) AudienceRestriction element. */
            $this->validAudiences = $audiences;

          }
          else {
            // The set of AudienceRestriction are ANDed together, so we need
            // the subset that are present in all of them.
            $this->validAudiences = array_intersect($this->validAudiences, $audiences);
          }
          break;
        case 'ProxyRestriction':
        case 'OneTimeUse':
          // Currently ignored.
          break;
        default:
          throw new Exception('Unknown condition: ' . var_export($node->localName, TRUE));
      }
    }
  }

  /**
   * Parses AuthnStatement in assertion.
   *
   * @param DOMElement $xml The assertion XML element.
   *
   * @throws Exception
   */
  private function parseAuthnStatement(DOMElement $xml) {
    $authnStatements = Utilities::xpQuery($xml, './saml_assertion:AuthnStatement');
    if (empty($authnStatements)) {
      $this->authnInstant = NULL;

      return;
    }
    elseif (count($authnStatements) > 1) {
      throw new Exception('More that one <saml:AuthnStatement> in <saml:Assertion> not supported.');
    }
    $authnStatement = $authnStatements[0];

    if (!$authnStatement->hasAttribute('AuthnInstant')) {
      throw new Exception('Missing required AuthnInstant attribute on <saml:AuthnStatement>.');
    }
    $this->authnInstant = Utilities::xsDateTimeToTimestamp($authnStatement->getAttribute('AuthnInstant'));

    if ($authnStatement->hasAttribute('SessionNotOnOrAfter')) {
      $this->sessionNotOnOrAfter = Utilities::xsDateTimeToTimestamp($authnStatement->getAttribute('SessionNotOnOrAfter'));
    }

    if ($authnStatement->hasAttribute('SessionIndex')) {
      $this->sessionIndex = $authnStatement->getAttribute('SessionIndex');
    }

    $this->parseAuthnContext($authnStatement);
  }

  /**
   * Parses AuthnContext in AuthnStatement.
   *
   * @param DOMElement $authnStatementEl
   *
   * @throws Exception
   */
  private function parseAuthnContext(DOMElement $authnStatementEl) {
    // Get the AuthnContext element.
    $authnContexts = Utilities::xpQuery($authnStatementEl, './saml_assertion:AuthnContext');
    if (count($authnContexts) > 1) {
      throw new Exception('More than one <saml:AuthnContext> in <saml:AuthnStatement>.');
    }
    elseif (empty($authnContexts)) {
      throw new Exception('Missing required <saml:AuthnContext> in <saml:AuthnStatement>.');
    }
    $authnContextEl = $authnContexts[0];

    // Get the AuthnContextDeclRef (if available).
    $authnContextDeclRefs = Utilities::xpQuery($authnContextEl, './saml_assertion:AuthnContextDeclRef');
    if (count($authnContextDeclRefs) > 1) {
      throw new Exception('More than one <saml:AuthnContextDeclRef> found?');
    }
    elseif (count($authnContextDeclRefs) === 1) {
      $this->setAuthnContextDeclRef(trim($authnContextDeclRefs[0]->textContent));
    }

    // Get the AuthnContextDecl (if available).
    $authnContextDecls = Utilities::xpQuery($authnContextEl, './saml_assertion:AuthnContextDecl');
    if (count($authnContextDecls) > 1) {
      throw new Exception('More than one <saml:AuthnContextDecl> found?');
    }
    elseif (count($authnContextDecls) === 1) {
      $this->setAuthnContextDecl(new SAML2_XML_Chunk($authnContextDecls[0]));
    }

    // Get the AuthnContextClassRef (if available).
    $authnContextClassRefs = Utilities::xpQuery($authnContextEl, './saml_assertion:AuthnContextClassRef');
    if (count($authnContextClassRefs) > 1) {
      throw new Exception('More than one <saml:AuthnContextClassRef> in <saml:AuthnContext>.');
    }
    elseif (count($authnContextClassRefs) === 1) {
      $this->setAuthnContextClassRef(trim($authnContextClassRefs[0]->textContent));
    }

    // Constraint from XSD: MUST have one of the three.
    if (empty($this->authnContextClassRef) && empty($this->authnContextDecl) && empty($this->authnContextDeclRef)) {
      throw new Exception(
        'Missing either <saml:AuthnContextClassRef> or <saml:AuthnContextDeclRef> or <saml:AuthnContextDecl>'
      );
    }

    $this->AuthenticatingAuthority = Utilities::extractStrings(
      $authnContextEl,
      'urn:oasis:names:tc:SAML:2.0:assertion',
      'AuthenticatingAuthority'
    );
  }

  /**
   * Sets the authentication method used to authenticate the user.
   *
   * If this is set to NULL, no authentication statement will be
   * included in the assertion. The default is NULL.
   *
   * @param string|NULL $authnContextClassRef The authentication method.
   */
  public function setAuthnContextClassRef($authnContextClassRef) {
    $this->authnContextClassRef = $authnContextClassRef;
  }

  /**
   * Parses attribute statements in assertion.
   *
   * @param DOMElement $xml The XML element with the assertion.
   *
   * @throws Exception
   */
  private function parseAttributes(DOMElement $xml) {
    $firstAttribute = TRUE;
    $attributes = Utilities::xpQuery($xml, './saml_assertion:AttributeStatement/saml_assertion:Attribute');
    foreach ($attributes as $attribute) {
      if (!$attribute->hasAttribute('Name')) {
        throw new Exception('Missing name on <saml:Attribute> element.');
      }
      $name = $attribute->getAttribute('Name');

      if ($attribute->hasAttribute('NameFormat')) {
        $nameFormat = $attribute->getAttribute('NameFormat');
      }
      else {
        $nameFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
      }

      if ($firstAttribute) {
        $this->nameFormat = $nameFormat;
        $firstAttribute = FALSE;
      }
      else {
        if ($this->nameFormat !== $nameFormat) {
          $this->nameFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
        }
      }

      if (!array_key_exists($name, $this->attributes)) {
        $this->attributes[$name] = array();
      }

      $values = Utilities::xpQuery($attribute, './saml_assertion:AttributeValue');
      foreach ($values as $value) {
        $this->attributes[$name][] = trim($value->textContent);
      }
    }
  }

  /**
   * Parses encrypted attribute statements in assertion.
   *
   * @param DOMElement $xml The XML element with the assertion.
   */
  private function parseEncryptedAttributes(DOMElement $xml) {
    $this->encryptedAttribute = Utilities::xpQuery(
      $xml,
      './saml_assertion:AttributeStatement/saml_assertion:EncryptedAttribute'
    );
  }

  /**
   * Parses signature on assertion.
   *
   * @param DOMElement $xml The assertion XML element.
   *
   * @throws \Exception
   */
  private function parseSignature(DOMElement $xml) {
    // Validate the signature element of the message.
    $sig = Utilities::validateElement($xml);
    if ($sig !== FALSE) {
      $this->wasSignedAtConstruction = TRUE;
      $this->certificates = $sig['Certificates'];
      $this->signatureData = $sig;
    }
  }

  /**
   * Parses subject in assertion.
   *
   * @param DOMElement $xml The assertion XML element.
   *
   * @throws Exception
   */
  private function parseSubject(DOMElement $xml) {
    $subject = Utilities::xpQuery($xml, './saml_assertion:Subject');
    if (empty($subject)) {
      // No Subject node.
      return;
    }
    elseif (count($subject) > 1) {
      throw new Exception('More than one <saml:Subject> in <saml:Assertion>.');
    }

    $subject = $subject[0];

    $nameId = Utilities::xpQuery(
      $subject,
      './saml_assertion:NameID | ./saml_assertion:EncryptedID/xenc:EncryptedData'
    );
    if (empty($nameId)) {
      throw new Exception('Missing <saml:NameID> or <saml:EncryptedID> in <saml:Subject>.');
    }
    elseif (count($nameId) > 1) {
      throw new Exception('More than one <saml:NameID> or <saml:EncryptedD> in <saml:Subject>.');
    }
    $nameId = $nameId[0];
    if ($nameId->localName === 'EncryptedData') {
      // The NameID element is encrypted.
      $this->encryptedNameId = $nameId;
    }
    else {
      $this->nameId = Utilities::parseNameId($nameId);
    }
  }

  /**
   * Retrieves the issuer if this assertion.
   *
   * @return string The issuer of this assertion.
   */
  public function getIssuer() {
    return $this->issuer;
  }

  /**
   * Retrieves the NameId of the subject in the assertion.
   * The returned NameId is in the format used by Utilities::addNameId().
   *
   * @return array|NULL
   *   The name identifier of the assertion.
   *
   * @throws Exception
   * @see Utilities::addNameId()
   *
   */
  public function getNameId() {
    if ($this->encryptedNameId !== NULL) {
      throw new Exception('Attempted to retrieve encrypted NameID without decrypting it first.');
    }

    return $this->nameId;
  }

  /**
   * Gets valid audiences.
   *
   * @return mixed
   */
  public function getValidAudiences() {
    return $this->validAudiences;
  }

  /**
   * Retrieves all attributes.
   *
   * @return array
   *   All attributes, as an associative array.
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * Retrieves signature data.
   *
   * @return mixed
   */
  public function getSignatureData() {
    return $this->signatureData;
  }

}
