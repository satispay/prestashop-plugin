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

<script>
$(function() {
  $(".form-wrapper").prependTo("#module_form");
  $("#fieldset_0 .alert").prependTo("#module_form");
  $("#fieldset_0 .panel-footer").clone().appendTo("#settings");
  $("#fieldset_0 .panel-footer").clone().appendTo("#refund");
  $("#fieldset_0").hide();
});
</script>

<div class="panel">
  <p>{l s='Satispay is a new mobile payment system that allows users to pay in physical and online stores, send money to friends, as well as top up phone credit; all within a few simple clicks. The app for consumers is intuitive, secure and available for Android, iOS and Windows Phone.' mod='satispay'}</p>
  <p>{l s='In order to activate the service, you must complete the free signup for Satispay Business via the following link: https://business.satispay.com/signup. Joining Satispay is free as there are no activation costs, monthly fees, or deactivation charges.' mod='satispay'}</p>
  <p>
    {l s='The only fee is a commission of:' mod='satispay'}<br />
    {l s='- 0.5% on payments less than or equal to 10€' mod='satispay'}<br />
    {l s='- 0.5% + 0.20€ for payments greater than 10€' mod='satispay'}
  </p>
  <p>{l s='For more information, visit our pricing page: https://www.satispay.com/en/pricing.' mod='satispay'}</p>
</div>
