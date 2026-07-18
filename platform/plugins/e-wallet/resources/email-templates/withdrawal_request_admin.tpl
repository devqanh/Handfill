{{ header }}

<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td class="bb-content bb-pb-0" align="center">
                <table class="bb-icon bb-icon-lg bb-bg-orange" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <td valign="middle" align="center">
                                <img src="{{ 'wallet' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <h1 class="bb-text-center bb-m-0 bb-mt-md">{{ 'plugins/e-wallet::email-templates.withdrawal_request_admin_title' | trans }}</h1>
            </td>
        </tr>
        <tr>
            <td class="bb-content">
                <p>{{ 'plugins/e-wallet::email-templates.withdrawal_request_admin_message' | trans({'customer_name': customer_name}) }}</p>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-pt-0">
                <table class="bb-row bb-mb-md" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td class="bb-col">
                                <h4 class="bb-m-0">{{ 'plugins/e-wallet::email-templates.customer_info' | trans }}</h4>
                                <div>{{ 'plugins/e-wallet::email-templates.customer_name' | trans }}: <strong>{{ customer_name }}</strong></div>
                                <div>{{ 'plugins/e-wallet::email-templates.customer_email' | trans }}: <strong>{{ customer_email }}</strong></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                                {% if bank_info %}
                                <div>{{ 'plugins/e-wallet::email-templates.bank_info' | trans }}: <strong>{{ bank_info }}</strong></div>
                                {% endif %}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-border-top" align="center">
                <a href="{{ withdrawal_link }}" class="bb-btn bb-bg-blue">{{ 'plugins/e-wallet::email-templates.view_withdrawal_request' | trans }}</a>
            </td>
        </tr>
        </tbody>
    </table>
</div>

{{ footer }}
