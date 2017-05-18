=== BooXtream for WooCommerce ===

Contributors: booxtream
Tags: booxtream, ebooks, watermarking, watermark, epub, mobi, kindle, ebook, woocommerce, socialdrm, social drm, drm
Requires at least: 4.0
Tested up to: 4.7.5
Stable tag: 0.9.9.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends the Simple product features in order to sell watermarked 'Social DRM' eBooks with WooCommerce. A BooXtream contract is required.

== Description ==

This plugin requires WooCommerce. This plugin has been tested with version 2.3.0 up to 3.0.7

BooXtream is a cloudbased alternative to Adobe DRM to protect ePub eBooks. It is used to create watermarked and personalised eBooks, a protection method also known as Social DRM. BooXtream provides download links to uniquely personalised eBooks and fulfils the download when an end user clicks on a link. 

To use the plug-in you'll need a BooXtream Contract and access to the BooXtream Dashboard. The Dashboard offers insight in all eBook transactions, account usage and eBook master file management.

The plug-in extends the Simple product features with a 'BooXtreamable' selection box in order to sell watermarked eBooks with WooCommerce. 
Please note: BooXtream for WooCommerce works independent from Virtual products. Master eBook files are managed from the BooXtream Dashboard, not from WordPress or WooCommerce.

Extensive plug-in documentation can be found on the Support page of the BooXtream Dashboard.

Please note:

You only can use BooXtream if you have a contract and bought some BooXtream credits, the 'currency' which is used for the pay-by-use system of BooXtream.
More information about the status of your contract and credits can be found in your BooXtream Dashboard. To obtain a contract or a free test account, contact info@booxtream.com.

BooXtream Basics: 

BooXtream uses 3 data fields with information about the end users to watermark and personalise the eBooks:

* Customer Name (used to personalise the eBook, see below)
* Customer Email Address (used to personalise the eBook, see below)
* WooCommerce Order ID (used for reports and transaction logging)

Every eBook processed by BooXtream for WooCommerce contains invisible watermarks. Optionally, the eBooks also contain a combination of visible extra's based on 'Customer Name' and 'Customer Email Address':

* a personalised ex libris image on the bookplate (the page after the cover page)
* a footer text at the end of every chapter
* a personalised page (disclaimer page) at the end of the eBook.

More info: www.booxtream.com

== Installation ==

The installation of BooXtream for WooCommerce s required; this plugin has been tested with version 2.3.0 up to 3.0.7.

1. Upload the plugin files to the '/wp-content/plugins/plugin-name' directory, or install the plugin through the WordPress plugins screen directly.

2. Activate the plugin through the 'Plugins' screen in WordPress

3. Configure your BooXteam contract:

	- click on WooCommerce > Settings

	- click on Integration (if you have other plugins installed, the BooXtream setting are available via a secondary menu)

	- enter your BooXtream contract credentials and click 'Save changes'

	- when contract credentials are correct, you can select an account

	- configure default settings (these values can be overwritten on product level when creating a BooXtreamable Simple product)

		- Ex Libris: drop down selection with all available Ex Libris image files in your BooXTream Dashboard account; use the BooXtream Dashboard 'Stored Files' section to upload and manage your Ex Libris image files

		- Language: drop down selection for the language used for all visible eBook personalisation texts (like Chapter Footer text and Disclaimer Page text)

		- Download limit: enter a value from 1 to 9999 (times); this value represents how many times a download link can be activated before it expires

		- Days until download expires: Enter a value between 1 and 999 (days). This value represents the lifetime of a download link in days before it expires

When you click Save changes, the installation and configuration process is finished and BooXtream for WooCommerce is ready for use! 

To use BooXtream for WooCommerce, select the Booxtreamable checkbox in the Product Data section (Simple product).

Extensive plug-in documentation can be found on the Support page of the BooXtream Dashboard.

== Changelog ==

= 0.9.9.6 =
* Some bugfixes
* Performance improvements
* Now supports Wordpress 4.7.5
* Supports WooCommerce 3.0, tested up to 3.0.7

= 0.9.9.5 =
* Fixed a bug that cropped up when WooCommerce updated to > 3.0



