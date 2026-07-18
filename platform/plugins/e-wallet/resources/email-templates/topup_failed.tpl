{{ header }}

<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td class="bb-content bb-pb-0" align="center">
                <table class="bb-icon bb-icon-lg bb-bg-red" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <td valign="middle" align="center">
                                <img src="{{ 'alert-triangle' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <h1 class="bb-text-center bb-m-0 bb-mt-md">{{ 'plugins/e-wallet::email-templates.topup_failed_title' | trans }}</h1>
            </td>
        </tr>
        <tr>
            <td class="bb-content">
                <p>{{ 'plugins/e-wallet::email-templates.topup_failed_greeting' | trans({'customer_name': customer_name}) }}</p>
                <p>{{ 'plugins/e-wallet::email-templates.topup_failed_message' | trans({'topup_amount': topup_amount}) }}</p>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-pt-0">
                <table class="bb-row bb-mb-md" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td class="bb-col">
                                <h4 class="bb-m-0">{{ 'plugins/e-wallet::email-templates.topup_details' | trans }}</h4>
                                <div>{{ 'plugins/e-wallet::email-templates.topup_code' | trans }}: <strong>{{ topup_code }}</strong></div>
                                <div>{{ 'plugins/e-wallet::email-templates.topup_amount' | trans }}: <strong>{{ topup_amount }}</strong></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-border-top">
                <p>{{ 'plugins/e-wallet::email-templates.topup_failed_help' | trans }}</p>
            </td>
        </tr>
        </tbody>
    </table>
</div>

{{ footer }}
