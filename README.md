# Coinsnap for GetPaid payment plugin #
![Coinsnap for GetPaid](https://github.com/Coinsnap/Coinsnap-for-GetPaid/blob/main/assets/images/1.png)

Contributors: coinsnap
Tags:  Lightning, Lightning Payment, SATS, Satoshi sats, bitcoin, Wordpress, Getpaid, Invoicing, Payment button, paywall, payment gateway, accept bitcoin, bitcoin plugin, bitcoin payment processor, bitcoin e-commerce, Lightning Network, cryptocurrency, lightning payment processor
Requires at least: 6.4
Requires PHP: 7.4
Tested up to: 6.7.1
Stable tag: 1.0.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bitcoin and Lightning payment processing with the Coinsnap add-on for payment & invoicing plugin GetPaid for Wordpress.

* Coinsnap for GetPaid Payment & Invoicing Demo Page: https://getpaid.coinsnap.org/
* Blog Article: https://coinsnap.io/en/coinsnap-for-getpaid/
* WordPress: https://wordpress.org/plugins/coinsnap-for-getpaid/
* GitHub: https://github.com/Coinsnap/Coinsnap-for-GetPaid

# Description #

[Coinsnap](https://coinsnap.io/en/) for GetPaid allows you to process Bitcoin Lightning payments over the Lightning network. 
With the Coinsnap Bitcoin-Lightning payment plugin for GetPaid Wordpress plugin you only need a Lightning wallet with a Lightning address to accept Bitcoin Lightning payments on your Wordpress site.

Coinsnap’s payment plugin for GetPaid makes it amazingly simple for your customers to purchase your offerings with Bitcoin-Lightning: They can make their transactions with just a scan of the QR code generated by the Coinsnap plugin, and the authorization of the payment. When authorized, the payment will be credited to your Lightning wallet in real time. 


# Bitcoin and Lightning payments in GetPaid with Coinsnap #

![GetPaid Plugin for Wordpress](https://github.com/Coinsnap/Coinsnap-for-GetPaid/blob/main/assets/images/2.png)

GetPaid is a payment and invoicing plugin for WordPress. With GetPaid, website operators can sell individual products with a payment button or accept donations. GetPaid can also be implemented as an add-on in other applications (such as Gravity Forms, AffiliateWP, Contact form 7, Ninja Forms or GeoDirectory) for payment processing.

GetPaid provides a large number of payment gateways to various payment service providers. Coinsnap payment plug-in is intended for GetPaid plugin to accept Bitcoin and Lightning payments via Coinsnap payment gateway https://app.coinsnap.io/. To do this, you need the free installed and configured GetPaid plugin (https://wordpress.org/plugins/invoicing/) on your WordPress site. Additionally the plugin Coinsnap for GetPaid.

![GetPaid order form](https://github.com/Coinsnap/Coinsnap-for-GetPaid/blob/main/assets/images/7.png)


# Features: #

* **All you need is a Lightning Wallet with a Lightning address. [Here you can find an overview of the matching Lightning Wallets](https://coinsnap.io/en/lightning-wallet-with-lightning-address/)**

* **Accept Bitcoin and Lightning payments** in your online store **without running your own technical infrastructure.** You do not need your own server, nor do you need to run your own Lightning Node.

* **Quick and easy registration at Coinsnap**: Just enter your email address and your Lightning address – and you are ready to integrate the payment module and start selling for Bitcoin Lightning. You will find the necessary IDs and Keys here, too.

* **100% protected privacy**:
    * We do not collect personal data.
    * For the registration you only need an e-mail address, which we will also use to inform you when we have received a payment.
    * No other personal information is required as long as you request a withdrawal to a Lightning address or Bitcoin address.

* **Only 1 % fees!**:
    * No basic fee, no transaction fee, only 1% on the invoice amount with referrer code.
    * Without referrer code the fee is 1.25%.
    * Get a referrer code from our partners and customers and save 0.25% fee.

* **No KYC needed**:
    * Direct, P2P payments (instantly to your Lightning wallet)
    * No intermediaries and paperwork
    * Transaction information is only shared between you and your customer

* **Sophisticated merchant’s admin dashboard in Coinsnap:**:
    * See all your transactions at a glance
    * Follow-up on individual payments
    * See issues with payments
    * Export reports

* **A Bitcoin payment via Lightning offers significant advantages**:
    * Lightning **payments are executed immediately.**
    * Lightning **payments are credited directly to the recipient.**
    * Lightning **payments are inexpensive.**
    * Lightning **payments are guaranteed.** No chargeback risk for the merchant.
    * Lightning **payments can be used worldwide.**
    * Lightning **payments are perfect for micropayments.**

* **Multilingual interface and support**: We speak your language


# Documentation: #

* [Coinsnap API (1.0) documentation](https://docs.coinsnap.io/)
* [Frequently Asked Questions](https://coinsnap.io/en/faq/) 
* [Terms and Conditions](https://coinsnap.io/en/general-terms-and-conditions/)
* [Privacy Policy](https://coinsnap.io/en/privacy/)


# Installation #

### 1. Install the Coinsnap GetPaid plug-in from the WordPress directory. ###

The Coinsnap GetPaid plug-in can be searched and installed in the WordPress plugin directory.

In your WordPress instance, go to the Plugins > Add New section.
In the search you enter Coinsnap and get as a result the Coinsnap GetPaid plug-in displayed.

Then click Install.

After successful installation, click Activate and then you can start setting up the plugin.

### 1.1. Add plugin ###

![Plugin downloading from Github repository](https://github.com/Coinsnap/Coinsnap-for-GetPaid/blob/main/assets/images/3.jpg)

If you don’t want to install add-on directly via plugin, you can download Coinsnap GetPaid plug-in from Coinsnap Github page or from WordPress directory and install it via “Upload Plugin” function:

Navigate to Plugins > Add Plugins > Upload Plugin and Select zip-archive downloaded from Github.

Click “Install now” and Coinsnap GetPaid plug-in will be installed in WordPress.

![Plugin downloading from Github repository](https://github.com/Coinsnap/Coinsnap-for-GetPaid/blob/main/assets/images/4.png)

After you have successfully installed the plugin, you can proceed with the connection to Coinsnap payment gateway.

### 1.2. Configure Coinsnap GetPaid plug-in ###

After the Coinsnap GetPaid plug-in is installed and activated, a notice appears that the plugin still needs to be configured.

![Coinsnap payment gateway settings](https://github.com/Coinsnap/Coinsnap-for-GetPaid/blob/main/assets/images/5.png)

### 1.3. Deposit Coinsnap data ###

* Navigate to GetPaid > Settings > Payment Gateways and select coinsnap
* Enter Store ID and API Key
* Click Save Setting

![Payment gateways list](https://github.com/Coinsnap/Coinsnap-for-GetPaid/blob/main/assets/images/6.png)


If you don’t have a Coinsnap account yet, you can do so via the link shown: Coinsnap Registration

### 2. Create Coinsnap account ####

![Coinsnap register](https://github.com/Coinsnap/Coinsnap-for-GetPaid/blob/main/assets/images/8.png)

### 2.1. Create a Coinsnap Account ####

Now go to the Coinsnap website at: https://app.coinsnap.io/register and open an account by entering your email address and a password of your choice.

If you are using a Lightning Wallet with Lightning Login, then you can also open a Coinsnap account with it.

### 2.2. Confirm email address ####

You will receive an email to the given email address with a confirmation link, which you have to confirm. If you do not find the email, please check your spam folder.

![E-mail address confirmation](https://github.com/Coinsnap/Coinsnap-for-GetPaid/blob/main/assets/images/9.png)

Then please log in to the Coinsnap backend with the appropriate credentials.

### 2.3. Set up website at Coinsnap ###

After you sign up, you will be asked to provide two pieces of information.

In the Website Name field, enter the name of your online store that you want customers to see when they check out.

In the Lightning Address field, enter the Lightning address to which the Bitcoin and Lightning transactions should be forwarded.

A Lightning address is similar to an e-mail address. Lightning payments are forwarded to this Lightning address and paid out. If you don’t have a Lightning address yet, set up a Lightning wallet that will provide you with a Lightning address.

For more information on Lightning addresses and the corresponding Lightning wallet providers, click here:
https://coinsnap.io/lightning-wallet-mit-lightning-adresse/

### 3. Connect Coinsnap account with GetPaid plug-in ###

### 3.1. GetPaid Coinsnap Settings ###

![Connect website with Coinsnap](https://github.com/Coinsnap/Coinsnap-for-GetPaid/blob/main/assets/images/10.png)

* Navigate to GetPaid > Settings > Payment Gateways and select coinsnap
* Enter Store ID and API Key
* Click Save Setting

### 4. Test payment ###

### 4.1. Test payment in GetPaid ###

After all the settings have been made, a test payment should be made.

We make a real donation payment in our test GetPaid site.

### 4.2. Bitcoin + Lightning payment page ###

The Bitcoin + Lightning payment page is now displayed, offering the payer the option to pay with Bitcoin or also with Lightning. Both methods are integrated in the displayed QR code.

![QR code on the Bitcoin payment page](https://github.com/Coinsnap/Coinsnap-for-GetPaid/blob/main/assets/images/11.png)

