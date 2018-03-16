{*
* 2007-2018 PrestaShop
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
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<form action="" method="post">
  <fieldset style="margin-top: 15px;">
    <legend>{l s='Settings' mod='satispay'}</legend>
    <table border="0" width="100%" cellpadding="5" cellspacing="0">
      <tr>
        <td style="text-align: right; width: 150px;">{l s='Sandbox' mod='satispay'}</td>
        <td>
          <select name="SATISPAY_STAGING">
            <option value="1" {if Configuration::get('SATISPAY_STAGING')}selected{/if}>{l s='Yes' mod='satispay'}</option>
            <option value="0" {if !Configuration::get('SATISPAY_STAGING')}selected{/if}>{l s='No' mod='satispay'}</option>
          </select>
        </td>
      </tr>
      <tr>
        <td style="text-align: right; width: 150px;">{l s='Security Bearer' mod='satispay'}</td>
        <td>
          <input type="text" name="SATISPAY_SECURITY_BEARER" value="{Configuration::get('SATISPAY_SECURITY_BEARER')|escape:'htmlall':'UTF-8'}" style="width: 99.4%;" />
          <small>{l s='Get from business.satispay.com' mod='satispay'}</small>
        </td>
      </tr>
    </table>
    <input class="button" style="float: right;" name="submitSatispayModule" value="{l s='Save' mod='satispay'}" type="submit" />
  </fieldset>
  <fieldset style="margin-top: 15px;">
    <legend>{l s='Refund' mod='satispay'}</legend>
    <table border="0" width="100%" cellpadding="5" cellspacing="0">
      <tr>
        <td style="text-align: right; width: 150px;">{l s='Order ID' mod='satispay'}</td>
        <td>
          <input type="text" name="SATISPAY_REFUND_ID" value="" style="width: 99.4%;" />
          <small>{l s='Get from Order list or details' mod='satispay'}</small>
        </td>
      </tr>
      <tr>
        <td style="text-align: right; width: 150px;">{l s='Amount' mod='satispay'}</td>
        <td>
          <input type="text" name="SATISPAY_REFUND_AMOUNT" value="" style="width: 99.4%;" />
          <small>{l s='Leave empty to refund the total amount' mod='satispay'}</small>
        </td>
      </tr>
    </table>
    <input class="button" style="float: right;" name="submitSatispayModule" value="{l s='Save' mod='satispay'}" type="submit" />
  </fieldset>
</form>
