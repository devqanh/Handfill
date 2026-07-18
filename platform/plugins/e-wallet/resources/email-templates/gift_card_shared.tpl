{{ header }}

<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td class="bb-content bb-pb-0" align="center">
                <table class="bb-icon bb-icon-lg bb-bg-blue" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <td valign="middle" align="center">
                                <img src="{{ 'gift' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <h1 class="bb-text-center bb-m-0 bb-mt-md">{{ 'plugins/e-wallet::email-templates.gift_card_shared_title' | trans }}</h1>
            </td>
        </tr>
        <tr>
            <td class="bb-content">
                {% if sender_name %}
                <p>{{ 'plugins/e-wallet::email-templates.gift_card_shared_greeting_from' | trans({'recipient_name': recipient_name, 'sender_name': sender_name}) }}</p>
                {% else %}
                <p>{{ 'plugins/e-wallet::email-templates.gift_card_shared_greeting' | trans({'recipient_name': recipient_name}) }}</p>
                {% endif %}
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-pt-0">
                <table class="bb-row bb-mb-md" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td class="bb-col">
                                <h4 class="bb-m-0">{{ 'plugins/e-wallet::email-templates.gift_card_details' | trans }}</h4>
                                <div>{{ 'plugins/e-wallet::email-templates.gift_card_code' | trans }}: <strong style="font-size: 18px; letter-spacing: 2px; color: #2563eb; font-family: monospace;">{{ gift_card_code }}</strong></div>
                                <div>{{ 'plugins/e-wallet::email-templates.gift_card_value' | trans }}: <strong>{{ gift_card_value }}</strong></div>
                                {% if gift_card_expiry %}
                                <div>{{ 'plugins/e-wallet::email-templates.gift_card_expiry' | trans }}: <strong>{{ gift_card_expiry }}</strong></div>
                                {% endif %}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        {% if gift_message %}
        <tr>
            <td class="bb-content bb-border-top">
                <h4 class="bb-m-0">{{ 'plugins/e-wallet::email-templates.gift_message_label' | trans }}</h4>
                <p style="font-style: italic; color: #666;">"{{ gift_message }}"</p>
            </td>
        </tr>
        {% endif %}
        <tr>
            <td class="bb-content bb-border-top">
                <h4 class="bb-m-0">{{ 'plugins/e-wallet::email-templates.how_to_use' | trans }}</h4>
                <ol style="margin: 10px 0; padding-left: 20px;">
                    <li>{{ 'plugins/e-wallet::email-templates.step_1' | trans }}</li>
                    <li>{{ 'plugins/e-wallet::email-templates.step_2' | trans }}</li>
                    <li>{{ 'plugins/e-wallet::email-templates.step_3' | trans }}</li>
                </ol>
            </td>
        </tr>
        <tr>
            <td class="bb-content" align="center">
                <a href="{{ redeem_url }}" class="bb-btn bb-btn-primary">{{ 'plugins/e-wallet::email-templates.check_balance_button' | trans }}</a>
            </td>
        </tr>
        </tbody>
    </table>
</div>

{{ footer }}
