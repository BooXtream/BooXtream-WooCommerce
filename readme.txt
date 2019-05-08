=== BooXtream for WooCommerce ===

Contributors: booxtream
Tags: booxtream, ebooks, watermarking, watermark, epub, mobi, kindle, ebook, woocommerce, socialdrm, social drm, drm
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends the Simple product features in order to sell personalised and watermarked (aka 'Social DRM') EPUB ebooks with WooCommerce. A BooXtream contract is required.

== No longer maintained ==
This plugin is NOT supported and maintained by BooXtream. Use and modify as you like.

== Description ==

This plugin requires WooCommerce. This plugin has been tested with version 2.6.11 up to 3.2.3

BooXtream is a cloudbased alternative to Adobe DRM to protect EPUB ebooks. It is used to create watermarked and personalised ebooks, a protection method also known as Social DRM. BooXtream provides download links to uniquely personalised ebooks and fulfils the download when an end user clicks on a link. 

To use the plug-in you'll need a BooXtream Contract and access to the BooXtream Dashboard. The Dashboard offers insight in all ebook transactions, account usage and ebook master file management.

The plug-in extends the Simple product features with a 'BooXtreamable' selection box in order to sell watermarked ebooks with WooCommerce. 
Please note: BooXtream for WooCommerce works independent from Virtual products. Master ebook files are managed from the BooXtream Dashboard, not from WordPress or WooCommerce.

Extensive plug-in documentation can be found on the Support page of the BooXtream Dashboard.

Please note:

You only can use BooXtream if you have a contract and bought some BooXtream credits, the 'currency' which is used for the pay-by-use system of BooXtream.
More information about the status of your contract and credits can be found in your BooXtream Dashboard. To obtain a contract or a free test account, contact info@booxtream.com.

BooXtream Basics: 

BooXtream uses 3 data fields with information about the end users to watermark and personalise the ebooks:

* Customer Name (used to personalise the ebook, see below)
* Customer Email Address (used to personalise the ebook, see below)
* WooCommerce Order ID (used for reports and transaction logging)

Every ebook processed by BooXtream for WooCommerce contains invisible watermarks. Optionally, the ebooks also contain a combination of visible extra's based on 'Customer Name' and 'Customer Email Address':

* a personalised ex libris image on the bookplate (the page after the cover page)
* a footer text at the end of every chapter
* a personalised page (disclaimer page) at the end of the ebook.

More info: www.booxtream.com

== Installation ==

1. Upload the plugin files to the '/wp-content/plugins/plugin-name' directory, or install the plugin through the WordPress plugins screen directly.

2. Activate the plugin through the 'Plugins' screen in WordPress

3. Configure your BooXteam contract:

	- click on WooCommerce > Settings

	- click on Integration (if you have other plugins installed, the BooXtream setting are available via a secondary menu)

	- enter your BooXtream contract credentials and click 'Save changes'

	- when contract credentials are correct, you can select an account

	- configure when plugin contacts BooXtream: this can be right after payment of on order completion. Some other plugins that change the default way WooCommerce handles status changes may require you to change this.

	- configure default settings (these values can be overwritten on product level when creating a BooXtreamable Simple product)

		- Ex Libris: drop down selection with all available Ex Libris image files in your BooXTream Dashboard account; use the BooXtream Dashboard 'Stored Files' section to upload and manage your Ex Libris image files

		- Language: drop down selection for the language used for all visible ebook personalisation texts (like Chapter Footer text and Disclaimer Page text)

		- Download limit: enter a value from 1 to 255 (times); this value represents how many times a download link can be activated before it expires

		- Days until download expires: Enter a value between 1 and 730 (days). This value represents the lifetime of a download link in days before it expires

When you click Save changes, the installation and configuration process is finished and BooXtream for WooCommerce is ready for use!

To use BooXtream for WooCommerce, select the Booxtreamable checkbox in the Product Data section (Simple product).

Extensive plug-in documentation can be found on the Support page of the BooXtream Dashboard.

== Changelog ==

= Known issue: Your download is not ready yet =
The download link can redirect to the page 'Your download is not ready yet'. It is possible that the watermarking process has not finished yet when a customer clicks on the download link. However, if this takes to long, chances are that the ebook you are trying to watermark is invalid.
If this happens you should check the Transaction page on the BooXtream Dashboard for any watermarking errors and cancel/refund the WooCommerce order manually.
The actual error message shown in the BooXtream Dashboard is not shown to the end user in WooCommerce or in the admin. We will improve this in a future release of the BooXtream plug-in.

= Default menu shows 'Your download is not ready yet' =
Please refer to https://en.support.wordpress.com/pages/hide-pages/

= Why can an ebook fail to watermark? =
An ebook can fail to watermark when the ebook file is invalid. We therefore strongly recommend to test your ebooks by validating and watermarking every ebook before adding them to your web shop.
To validate an ebook go to the Stored Files tab in the BooXtream Dashboard and use the ? button. A proper ebook shouldn’t have any warnings or errors.
To test the watermarking of an ebook you can use the Manual Mode and your free BooXtream test account. The watermarking process is ok when a download link is created.

= 1.1.1 =
* No longer maintained message added
* Fixed small issue with download limits and expiry date

= 1.1.0 =
* Moved to SemVer
* Added Bulgarian, Chinese, Finnish, Polish and Romanian to list of languages

= 1.0.0.0 =
* Performance improvements
* Plugin contacts BooXtream asynchronously

= 0.9.9.9 =
* Supports WooCommerce 3.2.3 and Wordpress 4.9
* Set processing default

= 0.9.9.8 =
* Supports WooCommerce 3.0.8 and Wordpress 4.8
* fixed a php warning

= 0.9.9.7 =
* fixed a bug where downloadlinks did not show in e-mails
* fixed a bug where only one downloadlink was shown when both epub and mobi were selected
* changed presentation of downloadlinks (no longer added as a string but added as clickable link)

= 0.9.9.6 =
* Some bugfixes
* Performance improvements
* Now supports Wordpress 4.7.5
* Supports WooCommerce 3.0, tested up to 3.0.7
* Added option to change when shop contacts BooXtream (moment of transaction)
* WooCommerce 3.0 does not allow html in meta tags. Downloadlinks are now added as string (which will make them automatically linkable). The generated urls have been shortened.

= 0.9.9.5 =
* Fixed a bug that cropped up when WooCommerce updated to > 3.0

