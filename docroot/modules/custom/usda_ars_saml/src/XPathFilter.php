<?php

namespace Drupal\usda_ars_saml;

/**
 * Utility class for filtering XPath queries.
 */
class XPathFilter
{
  const ALPHANUMERIC = '\w\d';
  const NUMERIC = '\d';
  const LETTERS = '\w';
  const EXTENDED_ALPHANUMERIC = '\w\d\s\-_:\.';

  const SINGLE_QUOTE = '\'';
  const DOUBLE_QUOTE = '"';
  const ALL_QUOTES = '[\'"]';


  /**
   * Filters an attribute value for safe inclusion in an XPath query.
   *
   * @param string $value
   *   The value to filter.
   * @param string $quotes
   *   The quotes used to delimit the value in the XPath query.
   *
   * @return string
   *   The filtered attribute value.
   */
  public static function filterAttrValue($value, $quotes = self::ALL_QUOTES): string {
    return preg_replace('#' . $quotes . '#', '', $value);
  }

  /**
   * Filters an attribute name for safe inclusion in an XPath query.
   *
   * @param string $name
   *   The attribute name to filter.
   * @param mixed $allow
   *   The set of characters to allow. Can be one of the constants provided by
   * this class, or custom regex excluding '#' character (used as delimiter).
   *
   * @return string
   *   The filtered attribute name.
   */
  public static function filterAttrName($name, $allow = self::EXTENDED_ALPHANUMERIC): string {
    return preg_replace('#[^' . $allow . ']#', '', $name);
  }

}
