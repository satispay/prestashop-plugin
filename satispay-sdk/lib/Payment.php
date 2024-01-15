<?php
/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

namespace SatispayGBusiness;

class Payment {
  private static $apiPath = "/g_business/v1/payments";

  /**
   * Create payment
   * @param array $body
  */
  public static function create($body) {
    return Request::post(self::$apiPath, array(
      "body" => $body,
      "sign" => true
    ));
  }

  /**
   * Get payment
   * @param string $id
  */
  public static function get($id) {
    return Request::get(self::$apiPath."/$id", array(
      "sign" => true
    ));
  }

  /**
   * Get payments list
   * @param array $options
  */
  public static function all($options = array()) {
    $queryString = "";
    if (!empty($options)) {
      $queryString .= "?";
      $queryString .= http_build_query($options);
    }
    return Request::get(self::$apiPath.$queryString, array(
      "sign" => true
    ));
  }

  /**
   * Update payment
   * @param string $id
   * @param array $body
  */
  public static function update($id, $body) {
    return Request::put(self::$apiPath."/$id", array(
      "body" => $body,
      "sign" => true
    ));
  }
}
