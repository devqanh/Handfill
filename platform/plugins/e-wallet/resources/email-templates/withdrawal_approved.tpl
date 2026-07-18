{{ header }}

<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td class="bb-content bb-pb-0" align="center">
                <table class="bb-icon bb-icon-lg bb-bg-green" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <td valign="middle" align="center">
                                <img src="{{ 'check' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <h1 class="bb-text-center bb-m-0 bb-mt-md">{{ 'plugins/e-wallet::email-templates.withdrawal_approved_title' | trans }}</h1>
            </td>
        </tr>
        <tr>
            <td class="bb-content">
                <p>{{ 'plugins/e-wallet::email-templates.withdrawal_approved_greeting' | trans({'customer_name': customer_name}) }}</p>
                <p>{{ 'plugins/e-wallet::email-templates.withdrawal_approved_message' | trans({'withdrawal_amount': withdrawal_amount}) }}</p>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-pt-0">
                <table class="bb-row bb-mb-md" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td class="bb-col">
                                <h4 class="bb-m-0">{{ 'plugins/e-wallet::email-templates.withdrawal_details' | trans }}</h4>
                                <div>{{ 'plugins/e-wallet::email-templates.withdrawal_amount' | trans }}: <strong>{{ withdrawal_amount }}</strong></div>
                                {% if payment_method %}
                                <div>{{ 'plugins/e-wallet::email-templates.payment_method' | trans }}: <strong>{{ payment_method }}</strong></div>
                                {% endif %}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-border-top">
                <table class="bb-row" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td class="bb-col">
                                <h4 class="bb-m-0">{{ 'plugins/e-wallet::email-templates.remaining_balance' | trans }}</h4>
                                <div class="bb-text-lg"><strong>{{ wallet_balance }}</strong></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
</div>

{{ footer }}
