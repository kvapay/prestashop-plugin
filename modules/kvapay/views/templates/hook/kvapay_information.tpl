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
<div class="tab">
    <button class="tablinks" onclick="changeTab(event, 'Information')" id="defaultOpen">{l s='Information' mod='kvapay'}</button>
    <button class="tablinks" onclick="changeTab(event, 'Configure Settings')">{l s='Configure Settings' mod='kvapay'}</button>
</div>

<!-- Tab content -->
<div id="Information" class="tabcontent">
    <div class="wrapper">
        <img src="../modules/kvapay/views/img/invoice.png" style="float:right;"/>
        <h2 class="kvapay-information-header">
            {l s='Accept Bitcoin, Litecoin, Ethereum and other digital currencies on your PrestaShop store with KvaPay' mod='kvapay'}
        </h2><br/>
        <strong>{l s='What is KvaPay?' mod='kvapay'}</strong> <br/>
        <p>
            {l s='We offer a fully automated cryptocurrency processing platform and invoice system. Accept any cryptocurrency and get paid in Euros or
       U.S. Dollars directly to your bank account (for verified merchants), or just keep bitcoins!' mod='kvapay'}
        </p><br/>
        <strong>{l s='Getting started' mod='kvapay'}</strong><br/>
        <p>
        <ul>
            <li>{l s='Install the KvaPay module on PrestaShop' mod='kvapay'}</li>
            <li>
                {l s='Visit ' mod='kvapay'}<a href="https://kvapay.com" target="_blank">{l s='kvapay.com' mod='kvapay'}</a>
                {l s='and create an account' mod='kvapay'}
            </li>
        </ul>
        </p>
        <p class="sign-up"><br/>
            <a href="https://kvapay.com/sign_up" class="sign-up-button">{l s='Sign up on KvaPay' mod='kvapay'}</a>
        </p><br/>
        <strong>{l s='Features' mod='kvapay'}</strong>
        <p>
        <ul>
            <li>{l s='The gateway is fully automatic - set and forget it.' mod='kvapay'}</li>
            <li>{l s='Payment amount is calculated using real-time exchange rates' mod='kvapay'}</li>
            <li>{l s='Your customers can select to pay with Bitcoin, Litecoin, Ethereum and other cryptocurrencies at checkout, while your payouts are in single currency of your choice.' mod='kvapay'}</li>
            <li>
                <a href="https://dev.crypay.com" target="_blank">
                    {l s='Sandbox environment' mod='kvapay'}
                </a> {l s='for testing with Testnet Bitcoin.' mod='kvapay'}
            </li>
            <li>{l s='Transparent pricing: no setup or recurring fees.' mod='kvapay'}</li>
            <li>{l s='No chargebacks - guaranteed!' mod='kvapay'}</li>
        </ul>
        </p>

        <p><i>{l s='Questions? Contact support@kvapay.com !' mod='kvapay'}</i></p>
    </div>
</div>

<div id="Configure Settings" class="tabcontent">
    {html_entity_decode($form|escape:'htmlall':'UTF-8')}
</div>

<script>
    document.getElementById("defaultOpen").click();
</script>
