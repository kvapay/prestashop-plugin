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
{extends 'customer/page.tpl'}

{block name='page_content'}

    <div class="box" style="background: #ffffff; border: 1px solid #e2e2e2; padding: 30px;">

        <h2 class="page-subheading">
            {l s='Your order has been successfully paid.' mod='kvapay'}
        </h2>

        <div class="row">
            <div class="col-xs-12 col-sm-12">

                {if !$kvapay_production}
                    <p>
                        <span style="color: red;">{l s='Test mode, payments do not actually take place.' mod='kvapay'}</span>
                    </p>
                {/if}

                <p>
                    {l s='Thank you for your payment.' mod='kvapay'}
                    {l s='We have sent you a confirmation of payment to your email.' mod='kvapay'}
                    {l s='Your order will be processed as soon as possible.' mod='kvapay'}
                </p>

                <table class="table" cellspacing="0">
                    <tr>
                        <td><strong>{l s='Order number' mod='kvapay'}</strong></td>
                        <td>{$kvapay_id_order|escape:'htmlall':'UTF-8'}</td>
                    </tr>
                    <tr>
                        <td><strong>{l s='Order reference' mod='kvapay'}</strong></td>
                        <td>{$kvapay_reference_order|escape:'htmlall':'UTF-8'}</td>
                    </tr>
                    <tr>
                        <td><strong>{l s='Order amount' mod='kvapay'}</strong></td>
                        <td>{$kvapay_total_to_pay|escape:'htmlall':'UTF-8'}</td>
                    </tr>
                </table>

                <p>
                    {l s='If you have any questions or need more information, please contact us at our' mod='kvapay'} <strong class="dark"><a href="{$link->getPageLink('contact-form', true)|escape:'html'}">{l s='customer support' mod='kvapay'}</a>.</strong>
                </p>

            </div>
        </div>

    </div>

{/block}
