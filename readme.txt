=== Bitcoin payments for GetPaid ===
Contributors: coinsnap
Tags: bitcoin, lightning, getpaid, invoicing, btcpay
Requires at least: 6.0
Tested up to: 7.0.7
Requires PHP: 7.4
Stable tag: 1.1.6
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept Bitcoin and Lightning with GetPaid for payment forms, invoices, checkout buttons, subscriptions, retainers and recurring billing workflows.

== Description ==

= Add Bitcoin and Lightning payments to GetPaid =

Coinsnap for GetPaid adds Bitcoin as a payment option to the existing GetPaid payment and invoicing ecosystem.

Accept **Bitcoin via Lightning or on-chain** in GetPaid payment forms, invoices and subscription flows without changing your existing workflows.

Coinsnap can be offered alongside payment methods such as Stripe or PayPal, or used as a standalone Bitcoin payment option.

Coinsnap is **Bitcoin payment software for merchants** and a brand of **Onlineshop24 d.o.o., Serbia**.

= Key features =

* Accept **Bitcoin and Lightning payments** with GetPaid
* Support for **Lightning and Bitcoin on-chain**
* Works with GetPaid payment forms
* Works with invoices and checkout buttons
* Supports subscriptions, retainers and recurring billing flows
* Suitable for one-time payments, deposits, donations and service billing
* Use Bitcoin alongside Stripe, PayPal and other payment methods
* Support for **Coinsnap** and **BTCPay Server**
* BTCPay Server setup wizard
* Configurable checkout title and description
* Configurable gateway priority
* Automatic or custom redirect after payment
* Custom return URL after payment
* Payment-status mapping for Expired, Processing and Settled
* Optional **Bitcoin discount**
* Can bring Bitcoin payments to GetPaid-powered WordPress integrations
* Suitable for micropayments and pay-per-use models
* No own Lightning node required when using Coinsnap
* Non-custodial / self-custody payment model
* Optional bank settlement through supported Coinsnap partners
* Open source and free to install

= Typical use cases =

Use Coinsnap for GetPaid for:

* one-time payments
* payment forms
* checkout buttons
* invoices
* subscriptions
* retainers
* recurring billing
* donations and tips
* service payments
* deposits
* digital products
* pay-per-use services
* usage-based billing
* micropayments
* paid access or lead qualification

= How it works =

1. Install and activate GetPaid.
2. Install and activate Coinsnap for GetPaid.
3. Go to **GetPaid → Settings → Payment Gateways**.
4. Select Coinsnap and open the gateway settings.
5. Choose Coinsnap or BTCPay Server.
6. Enter the required connection details.
7. Configure checkout text, priority and redirect behavior.
8. Configure Expired, Processing and Settled status mapping.
9. Enable Coinsnap in your GetPaid payment workflows.
10. Make a test Bitcoin payment.

= Coinsnap or BTCPay Server =

The add-on supports:

* **Coinsnap** — accept Bitcoin without operating your own Bitcoin or Lightning payment infrastructure.
* **BTCPay Server** — connect your own self-hosted BTCPay Server.

When BTCPay Server is used, a Coinsnap merchant account is not required for the payment processing itself.

= GetPaid payment flows =

The current Coinsnap GetPaid product supports Bitcoin in:

* payment forms
* invoices
* checkout buttons
* subscriptions
* recurring billing
* retainers

= GetPaid-powered WordPress integrations =

GetPaid can act as a payment layer for other WordPress plugins and applications.

Coinsnap can therefore extend Bitcoin payments into compatible GetPaid-powered integrations, including form, invoicing and directory workflows.

= Bitcoin discount =

The current Coinsnap GetPaid product supports an optional **Bitcoin discount**.

A fixed or percentage-based discount can be applied specifically to Bitcoin payments.

= Micropayments and new billing models =

Lightning makes small payments practical.

Combined with GetPaid, this can support low-value digital services, pay-per-use access, usage-based billing, deposits, tips, donations and paid lead qualification.

= Payment status mapping =

Coinsnap uses the payment states:

* **Expired**
* **Processing**
* **Settled**

These can be mapped to the corresponding GetPaid invoice/payment statuses.

= Settlement =

With Coinsnap, payments can be settled to a compatible Bitcoin or Lightning wallet.

One option is the [Coinsnap Wallet](https://coinsnap.io/wallet/).

Coinsnap follows a non-custodial / self-custody approach and does not hold merchant Bitcoin.

Depending on the merchant setup and supported partner integrations, Bitcoin payments can also be automatically converted and settled to a bank account.

= Fees =

Coinsnap charges a **1% transaction fee**. The fee is handled separately through the merchant's Coinsnap balance and is not deducted from the Bitcoin payment.

= More information =

* Product page: https://coinsnap.io/wp-add-ons/getpaid/
* User Guide: https://coinsnap.io/user-guides/getpaid-bitcoin-user-guide/
* WordPress Plugin Directory: https://wordpress.org/plugins/coinsnap-for-getpaid/
* Demo: https://getpaid.coinsnap.org/
* GitHub: https://github.com/Coinsnap/Coinsnap-for-GetPaid
* Coinsnap Wallet: https://coinsnap.io/wallet/
* Coinsnap API: https://docs.coinsnap.io/
* Coinsnap account: https://app.coinsnap.io/register

== Installation ==

1. Install and activate **GetPaid**.
2. In WordPress, go to **Plugins → Add New**.
3. Search for **Bitcoin payments for GetPaid**.
4. Install and activate the Coinsnap add-on.
5. Open **GetPaid → Settings → Payment Gateways**.
6. Select Coinsnap and open the gateway settings.
7. Connect Coinsnap or BTCPay Server.
8. Configure checkout text, redirects and status mapping.
9. Save the settings.
10. Make a test Bitcoin payment.

== Frequently Asked Questions ==

= Can GetPaid accept Bitcoin payments? =

Yes. Coinsnap adds Bitcoin and Lightning payments to GetPaid payment forms, invoices and subscription flows.

= Can customers pay with both Lightning and Bitcoin on-chain? =

Yes. The integration supports both Lightning and Bitcoin on-chain.

= Can I use Bitcoin for invoices? =

Yes. Bitcoin and Lightning can be offered as payment methods for GetPaid invoices.

= Can I use Bitcoin for subscriptions and recurring billing? =

Yes. The current Coinsnap GetPaid product supports subscriptions, retainers and recurring payment workflows.

= Can Bitcoin be offered alongside Stripe or PayPal? =

Yes. Coinsnap can be offered alongside other GetPaid payment methods or used as a standalone Bitcoin option.

= Can I use my own BTCPay Server? =

Yes. BTCPay Server is supported as an alternative to Coinsnap.

= Can Coinsnap work with other plugins that use GetPaid? =

Yes. Coinsnap can extend Bitcoin payments into compatible WordPress integrations that use GetPaid as their payment layer.

= Does the plugin support a Bitcoin discount? =

Yes. The current Coinsnap product documentation includes an optional fixed or percentage-based Bitcoin discount.

= Can I use GetPaid and Lightning for micropayments? =

Yes. Lightning is suitable for low-value payments, pay-per-use services, deposits, tips and other micropayment workflows.

= Do I need WooCommerce? =

No. GetPaid provides payment forms, invoices and checkout workflows without requiring a full e-commerce shop system.

= Do I need my own Lightning node? =

No. When using Coinsnap, no own Lightning node or Bitcoin payment server is required.

= Does Coinsnap hold my Bitcoin? =

No. Coinsnap follows a non-custodial / self-custody approach and does not hold merchant Bitcoin.

= Can payments be settled to a bank account? =

Depending on the merchant setup and supported partner integrations, Bitcoin payments can be converted and settled to a bank account.

= What does Coinsnap cost? =

Coinsnap charges a 1% transaction fee.

== Screenshots ==

1. Install Bitcoin payments for GetPaid from the WordPress Plugin Directory
2. GetPaid payment-gateway settings
3. Select Coinsnap as payment gateway
4. Choose Coinsnap or BTCPay Server
5. BTCPay Server setup wizard
6. Configure checkout title, description and priority
7. Configure redirect and return URL
8. Configure payment-status mapping
9. GetPaid invoice or payment form with Bitcoin
10. Bitcoin and Lightning payment QR code
11. Successful GetPaid payment

== Changelog ==

= 1.1.6 :: 2026-05-24 =
* Compatibility with GetPaid 2.8.48 is tested.
* Compatibility with WordPress 7.0 is tested.

= 1.1.5 :: 2026-04-07 =
* Updated Coinsnap Invoice client.
* Updated Coinsnap Store client.
* Compatibility with GetPaid 2.8.47 is tested.
* Compatibility with WordPress 6.9.4 is tested.

= 1.1.4 :: 2026-03-06 =
* Compatibility with GetPaid 2.8.41 - 2.8.44 is tested.
* Updated add-on description.

= 1.1.3 :: 2026-02-02 =
* Compatibility with GetPaid 2.8.40 is tested.

= 1.1.2 :: 2025-12-28 =
* Compatibility with GetPaid 2.8.39 is tested.
* Updated PHP file protection.

= 1.1.1 :: 2025-12-04 =
* Compatibility with WordPress 6.9 is tested.
* Compatibility with GetPaid 2.8.36 is tested.

= 1.1.0 :: 2025-10-12 =
* Added handling for currencies not supported by BTCPay Server.
* Added custom redirect after payment.
* Compatibility with GetPaid 2.8.35 is tested.

= 1.0.2 :: 2025-08-19 =
* Added SATS-to-BTC conversion for BTCPay Server.
* Compatibility with GetPaid 2.8.31 is tested.

= 1.0.1 :: 2025-08-05 =
* Improved API-key generation and connection-error handling.

= 1.0.0 :: 2025-08-04 =
* Initial release in WordPress Plugin Directory.
