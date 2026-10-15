# Coinsnap for GetPaid

![Coinsnap for GetPaid](https://resources.coinsnap.org/products/getpaid/images/1.png)

**Bitcoin payment add-on for GetPaid — accept Bitcoin and Lightning across GetPaid payment forms, invoices, checkout buttons, subscriptions, retainers and recurring billing workflows.**

Coinsnap for GetPaid adds Bitcoin as a payment option to the existing GetPaid payment and invoicing ecosystem.

Website owners can accept **Bitcoin via Lightning or on-chain** in GetPaid payment forms, invoices and subscription flows without changing the workflows they already use. Coinsnap can be offered alongside traditional payment providers such as Stripe or PayPal, or as a standalone Bitcoin payment option.

The integration can also extend Bitcoin payments into WordPress plugins and applications that use GetPaid as their payment layer.

Coinsnap is **Bitcoin payment software for merchants** and a brand of **Onlineshop24 d.o.o., Serbia**.

## Key features

- Accept **Bitcoin and Lightning payments** with GetPaid
- Support for **Lightning and Bitcoin on-chain**
- Works with GetPaid payment forms
- Works with invoices and payment buttons
- Supports subscriptions, retainers and recurring billing flows
- Suitable for one-time payments, deposits, donations and service billing
- Use Bitcoin alongside Stripe, PayPal and other payment methods
- Support for **Coinsnap** and **BTCPay Server**
- BTCPay Server setup wizard
- Configurable checkout title and description
- Configurable gateway priority
- Automatic or custom redirect after payment
- Custom return URL after payment
- Payment-status mapping for Expired, Processing and Settled
- Optional **Bitcoin discount** as a fixed amount or percentage
- Can bring Bitcoin payments to GetPaid-powered WordPress integrations
- Suitable for micropayments and pay-per-use models
- No own Lightning node required when using Coinsnap
- **Non-custodial / self-custody** payment model
- Optional settlement to a bank account through supported Coinsnap partners
- Open source and free to install

## Current public release

The current WordPress.org release is **1.1.6**.

WordPress.org currently lists:

- WordPress **6.0 or newer**
- PHP **7.4 or newer**
- Tested up to **WordPress 7.0.7**
- GetPaid compatibility tested up to **2.8.48**

> **Developer note:** the current GitHub `main` branch still identifies the plugin as version **1.1.5**, tested with WordPress 6.9 and GetPaid 2.8.47. The repository code metadata and public WordPress.org release should be synchronized separately.

## Typical use cases

Coinsnap for GetPaid can be used for:

- one-time payments
- payment forms
- checkout buttons
- invoices
- subscriptions
- retainers
- recurring billing
- donations and tips
- service payments
- deposits
- digital products
- pay-per-use services
- usage-based billing
- low-value payments and micropayments
- paid access or lead qualification

## How it works

1. Install and activate **GetPaid**.
2. Install and activate **Coinsnap for GetPaid**.
3. Go to **GetPaid → Settings → Payment Gateways**.
4. Select **Coinsnap** and open the gateway settings.
5. Choose **Coinsnap** or your own **BTCPay Server**.
6. Enter the required Store ID and API credentials.
7. Configure the checkout title, description, priority and redirect behavior.
8. Configure the Expired, Processing and Settled status mappings.
9. Enable Coinsnap as a payment method in your GetPaid workflows.
10. Make a test Bitcoin payment before going live.

## Coinsnap or BTCPay Server

The add-on supports two payment-provider modes:

- **Coinsnap** — for users who want to accept Bitcoin without operating their own Bitcoin or Lightning payment infrastructure.
- **BTCPay Server** — for users who want to connect their own self-hosted BTCPay Server.

When BTCPay Server is used, a Coinsnap merchant account is not required for the payment processing itself.

## GetPaid payment flows

The current Coinsnap product supports Bitcoin across the core GetPaid payment flows, including:

- payment forms
- invoices
- checkout buttons
- subscriptions
- recurring billing
- retainers

This allows existing GetPaid setups to add Bitcoin without rebuilding the underlying billing workflow.

## GetPaid-powered WordPress integrations

GetPaid can act as a payment layer for other WordPress plugins and applications.

Coinsnap can therefore extend Bitcoin payments into compatible GetPaid-powered integrations, including use cases built around forms, invoicing, directories and other WordPress applications that connect to GetPaid.

## Bitcoin discount

The current Coinsnap GetPaid product page supports an optional **Bitcoin discount**.

A fixed or percentage-based discount can be applied specifically to Bitcoin payments to encourage customers to choose Bitcoin when several payment methods are available.

## Micropayments and new billing models

Lightning makes very small payments practical.

Combined with GetPaid, this enables payment models such as:

- low-value digital services
- pay-per-use access
- usage-based billing
- small deposits
- tips and donations
- paid lead qualification
- micropayment-based content or service access

These models can be difficult to offer economically with card-based gateways because of minimum fees and chargeback risk.

## Payment status mapping

Coinsnap uses the payment states:

- **Expired**
- **Processing**
- **Settled**

These can be mapped to the appropriate GetPaid invoice/payment statuses in the gateway settings.

## Settlement

With Coinsnap, payments can be settled to a compatible Bitcoin or Lightning wallet.

One option is the [Coinsnap Wallet](https://coinsnap.io/wallet/).

Coinsnap follows a **non-custodial / self-custody** approach and does not hold merchant Bitcoin.

Depending on the merchant setup and supported partner integrations, Bitcoin payments can also be automatically converted and settled to a bank account.

## Fees

Coinsnap charges a **1% transaction fee**. The fee is handled separately through the merchant's Coinsnap balance and is not deducted from the Bitcoin payment itself.

## Installation

### Install from WordPress.org

1. Install and activate **GetPaid**.
2. In WordPress, go to **Plugins → Add New**.
3. Search for **Bitcoin payments for GetPaid**.
4. Install and activate the Coinsnap add-on.
5. Open **GetPaid → Settings → Payment Gateways**.
6. Select Coinsnap and open its settings.
7. Connect Coinsnap or BTCPay Server.
8. Configure checkout text, redirects and status mapping.
9. Save the settings.
10. Make a test Bitcoin payment.

### Install from GitHub

1. Download this repository as a ZIP archive.
2. Upload it through **Plugins → Add New → Upload Plugin**.
3. Install and activate the add-on.
4. Make sure GetPaid is installed and active.
5. Configure Coinsnap in the GetPaid payment-gateway settings.

## Resources

- [Coinsnap for GetPaid product page](https://coinsnap.io/wp-add-ons/getpaid/)
- [GetPaid Bitcoin User Guide](https://coinsnap.io/user-guides/getpaid-bitcoin-user-guide/)
- [WordPress Plugin Directory](https://wordpress.org/plugins/coinsnap-for-getpaid/)
- [GetPaid demo](https://getpaid.coinsnap.org/)
- [Coinsnap Wallet](https://coinsnap.io/wallet/)
- [Coinsnap API documentation](https://docs.coinsnap.io/)
- [Coinsnap merchant account](https://app.coinsnap.io/register)
- [Telegram community and support](https://t.me/coinsnap)

> **Brand note:** Coinsnap is Bitcoin payment software for merchants and is not the provider of the similarly named coin identifier app "CoinSnap".

## Frequently Asked Questions

### Can GetPaid accept Bitcoin payments?

Yes. Coinsnap adds Bitcoin and Lightning payments to GetPaid payment forms, invoices and subscription flows.

### Can customers pay with both Lightning and Bitcoin on-chain?

Yes. The integration supports both Lightning and Bitcoin on-chain.

### Can I use Bitcoin for invoices?

Yes. Bitcoin and Lightning can be offered as payment methods for GetPaid invoices.

### Can I use Bitcoin for subscriptions and recurring billing?

Yes. The current Coinsnap GetPaid product supports subscriptions, retainers and recurring payment workflows.

### Can Bitcoin be offered alongside Stripe or PayPal?

Yes. Coinsnap can be offered alongside existing GetPaid payment methods or used as a standalone Bitcoin option.

### Can I use my own BTCPay Server?

Yes. BTCPay Server is supported as an alternative to Coinsnap.

### Can Coinsnap work with other plugins that use GetPaid?

Yes. Coinsnap can extend Bitcoin payments into compatible WordPress integrations that use GetPaid as their payment layer.

### Does the plugin support a Bitcoin discount?

Yes. The current Coinsnap product page documents an optional fixed or percentage-based Bitcoin discount.

### Can I use GetPaid and Lightning for micropayments?

Yes. Lightning is suitable for low-value payments, pay-per-use services, deposits, tips and other micropayment workflows.

### Do I need WooCommerce?

No. GetPaid can provide payment forms, invoices and checkout workflows without a full e-commerce shop system.

### Do I need my own Lightning node?

No. When using Coinsnap, no own Lightning node or Bitcoin payment server is required.

### Does Coinsnap hold my Bitcoin?

No. Coinsnap follows a non-custodial / self-custody approach and does not hold merchant Bitcoin.

### Can payments be settled to a bank account?

Depending on the merchant setup and supported partner integrations, Bitcoin payments can be converted and settled to a bank account.

### What does Coinsnap cost?

Coinsnap charges a 1% transaction fee.

## Changelog

### 1.1.6 — 2026-05-24
- Compatibility with GetPaid 2.8.48 tested.
- Compatibility with WordPress 7.0 tested.

### 1.1.5 — 2026-04-07
- Updated the Coinsnap Invoice client.
- Updated the Coinsnap Store client.
- Compatibility with GetPaid 2.8.47 tested.
- Compatibility with WordPress 6.9.4 tested.

### 1.1.4 — 2026-03-06
- Compatibility with GetPaid 2.8.41–2.8.44 tested.
- Updated add-on description.

### 1.1.3 — 2026-02-02
- Compatibility with GetPaid 2.8.40 tested.

### 1.1.2 — 2025-12-28
- Compatibility with GetPaid 2.8.39 tested.
- Updated PHP file protection.

### 1.1.1 — 2025-12-04
- Compatibility with WordPress 6.9 tested.
- Compatibility with GetPaid 2.8.36 tested.

### 1.1.0 — 2025-10-12
- Added handling for currencies not supported by BTCPay Server.
- Added custom redirect after payment.
- Compatibility with GetPaid 2.8.35 tested.

### 1.0.2 — 2025-08-19
- Added SATS-to-BTC conversion for BTCPay Server.
- Compatibility with GetPaid 2.8.31 tested.

### 1.0.1 — 2025-08-05
- Improved API-key generation and connection-error handling.

### 1.0.0 — 2025-08-04
- Initial release in the WordPress Plugin Directory.
