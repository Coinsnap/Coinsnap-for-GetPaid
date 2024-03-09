=== Coinsnap Bitcoin + Lightning payment plug-in 1.0.0 for GetPaid ===
Contributors: coinsnap
Tags:  Coinsnap, GetPaid, Bitcoin, Lightning 
Requires at least: 6.4
Requires PHP: 7.4
Tested up to: 6.4.3
Stable tag: 1.0.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

You use Gravity Forms, AffiliateWP, Contact form 7, Ninja Forms or GeoDirectory and want to accept Bitcoin and Lightning payments there?

Then install the GetPaid plugin and the Coinsnap for GetPaid plugin to give your customers the option of paying you with Bitcoin and Lightning.

GetPaid is a payment plugin for WordPress. With GetPaid, website operators can sell individual products with a payment button or accept donations.

GetPaid can also be implemented as an add-on in other applications (such as Gravity Forms, AffiliateWP, Contact form 7, Ninja Forms or GeoDirectory) for payment processing.

GetPaid provides a large number of payment gateways to various payment service providers for payment processing.

There is a connection to Coinsnap’s Bitcoin Payment Gateway for the processing of Bitcoin and Lightning payments.

So if you want to accept Bitcoin and Lightning payments in Gravity Forms, AffiliateWP, Contact form 7, Ninja Forms or GeoDirectory, install the free plugins from GetPaid and Coinsnap for GetPaid.

== Installation ==

### 1. Install the Coinsnap GetPaid plug-in from the WordPress directory. ###

The Coinsnap GetPaid plug-in can be searched and installed in the WordPress plugin directory.

In your WordPress instance, go to the Plugins > Add New section.
In the search you enter Coinsnap and get as a result the Coinsnap GetPaid plug-in displayed.

Then click Install.

After successful installation, click Activate and then you can start setting up the plugin.

### 1.1. Add plugin ###

If you don’t want to install add-on directly via plugin, you can download Coinsnap GetPaid plug-in from Coinsnap Github page or from WordPress directory and install it via “Upload Plugin” function:

Navigate to Plugins > Add Plugins > Upload Plugin and Select zip-archive downloaded from Github.

Click “Install now” and Coinsnap GetPaid plug-in will be installed in WordPress.

After you have successfully installed the plugin, you can proceed with the connection to Coinsnap payment gateway.

### 1.2. Configure Coinsnap GetPaid plug-in ###

After the Coinsnap GetPaid plug-in is installed and activated, a notice appears that the plugin still needs to be configured.

### 1.3. Deposit Coinsnap data ###

* Navigate to GetPaid > Settings > Payment Gateways and select coinsnap
* Enter Store ID and API Key
* Click Save Setting

If you don’t have a Coinsnap account yet, you can do so via the link shown: Coinsnap Registration

### 2. Create Coinsnap account ####

### 2.1. Create a Coinsnap Account ####

Now go to the Coinsnap website at: https://app.coinsnap.io/register and open an account by entering your email address and a password of your choice.

If you are using a Lightning Wallet with Lightning Login, then you can also open a Coinsnap account with it.

### 2.2. Confirm email address ####

You will receive an email to the given email address with a confirmation link, which you have to confirm. If you do not find the email, please check your spam folder.

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

* Navigate to GetPaid > Settings > Payment Gateways and select coinsnap
* Enter Store ID and API Key
* Click Save Setting

### 4. Test payment ###

### 4.1. Test payment in GetPaid ###

After all the settings have been made, a test payment should be made.

We make a real donation payment in our test GetPaid site.

### 4.2. Bitcoin + Lightning payment page ###

The Bitcoin + Lightning payment page is now displayed, offering the payer the option to pay with Bitcoin or also with Lightning. Both methods are integrated in the displayed QR code.

== Upgrade Notice ==

Follow updates on plugin's GitHub page:
https://github.com/Coinsnap/Coinsnap-for-GetPaid/

== Upgrade Notice ==

Follow updates on plugin's GitHub page:
https://github.com/Coinsnap/Coinsnap-for-GetPaid/

== Frequently Asked Questions ==

Plugin's page on Coinsnap website: https://coinsnap.io/en/

== Screenshot ==
  
== Changelog ==
= 1.0 :: 2024-03-03 =
* Initial release. 
