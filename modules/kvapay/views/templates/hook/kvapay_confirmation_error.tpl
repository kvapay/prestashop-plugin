{**
* Copyright since 2007 PrestaShop SA and Contributors
* PrestaShop is an International Registered Trademark & Property of PrestaShop SA
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License version 3.0
* that is bundled with this package in the file LICENSE.md.
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/AFL-3.0
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* @author    PrestaShop SA and Contributors <contact@prestashop.com>
* @copyright Since 2007 PrestaShop SA and Contributors
* @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
*}
<div class="box">
    <div class="row">
        <div class="col-xs-12 col-sm-12">

            <h3 class="page-subheading">
                {l s='Your payment status' mod='kvapay'}
            </h3>

            {if !$kvapay_production}
                <p>
                    <span style="color: red;">{l s='Test mode, payments do not actually take place.' mod='kvapay'}</span>
                </p>
            {/if}

{*            {if !$kvapay_status_payment}*}

                <div class="alert alert-danger" role="alert">
                    <i class="material-icons rtl-no-flip">error_outline</i>
                    {l s='We do not have information about the successful payment of the payment. You can repeat the payment using the link below.' mod='kvapay'}
                </div>

                <p>
                    <a class="btn btn-primary" href="{$kvapay_repeat_payment_url|escape:'htmlall':'UTF-8'}"><i class="material-icons">payment</i> {l s='Pay with cryptocurrency' mod='kvapay'}</a>
                </p>

{*            {else}*}

{*                <p>*}
{*                    <a class="btn btn-primary" href="{$kvapay_check_url}">{l s='Check payment' mod='kvapay'}</a>*}
{*                </p>*}

{*            {/if}*}

            <p>
                {l s='If you have any questions or need more information, please contact us at our' mod='kvapay'} <strong class="dark"><a href="{$link->getPageLink('contact-form', true)|escape:'html'}">{l s='customer support' mod='kvapay'}</a>.</strong>
            </p>



        </div>
    </div>
</div>
