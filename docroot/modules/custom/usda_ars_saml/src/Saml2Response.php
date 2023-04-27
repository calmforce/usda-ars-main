<?php

/**
 * This file is part of USDA ARS SAML plugin.
 */
namespace Drupal\usda_ars_saml;

use DOMElement;

/**
 * Class for SAML2 Response messages.
 *
 */
class Saml2Response
{
    // The assertions in this response.
    private array $assertions = [];
    // The destination URL in this response.
    private string $destination = '';
    // The certificates in this response.
    private $certificates = [];
    // The signature data in this response.
    private $signatureData = NULL;

  /**
   * Constructor for SAML 2 response messages.
   *
   * @param DOMElement|NULL $xml
   *   The input DOM object.
   *
   * @throws \Exception
   */
    public function __construct(DOMElement $xml = NULL) {

        if (is_null($xml)) {
            return;
        }

        $sig = Utilities::validateElement($xml);
        if ( $sig !== FALSE ) {
            $this->certificates = $sig['Certificates'];
            $this->signatureData = $sig;
        }
        // Set the destination from saml response.
        if ($xml->hasAttribute('Destination')) {
            $this->destination = $xml->getAttribute('Destination');
        }
        // Set the assertions from saml response.
        for ($node = $xml->firstChild; $node !== NULL; $node = $node->nextSibling) {
            if ($node->namespaceURI !== 'urn:oasis:names:tc:SAML:2.0:assertion') {
                continue;
            }
            if ($node->localName === 'Assertion' || $node->localName === 'EncryptedAssertion') {
                $this->assertions[] = new Saml2Assertion($node);
            }
        }
    }

    /**
     * Retrieves the assertions in this response.
     *
     * @return Saml2Assertion[]
     */
    public function getAssertions(): array {
        return $this->assertions;
    }

  /**
   * Retrieves the destination in this response.
   *
   * @return string
   */
    public function getDestination(): string {
        return $this->destination;
    }

  /**
   * Retrieves the signature data in this response.
   *
   * @return array|NULL
   */
    public function getSignatureData() {
        return $this->signatureData;
    }

  /**
   * Retrieves the certificates in this response.
   *
   * @return array|mixed
   */
  public function getCertificates() {
    return $this->certificates;
  }

}
