=== Limit Attempts ===
Contributors: bestwebsoft
Donate link: https://www.2checkout.com/checkout/purchase?sid=1430388&quantity=1&product_id=94
Tags: limit attempts, login, blacklist, whitelist, blocked ip, failed attempts, block, limited attempts, limit attempts plugin, lemet, limet, atempts, attemps, ettempts, etempts, block user, add to blackilist, add to whitelist, unblock, login attempt, block address, block automatically, limit of locks
Requires at least: 3.6
Tested up to: 4.1
Stable tag: 1.0.7
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

The plugin Limit Attempts allows you to limit rate of login attempts by the ip, and create whitelist and blacklist.

== Description ==

Limit Attempts - This plugin allows you to limit users' attempts to log in to your website, as well as create and edit black- and whitelists. This way, you will be able to manage access to your website and its content and protect it from spam and unwanted visitors. There is also a premium version of Limit Attempts plugin - Limit Attempts Pro, with more useful features available. With the pro version, you recieve a possibility to add ranges of IP addresses or IP masks to black- and whitelists. Also, it is compatible with Captcha Pro plugin, so all functionality of Limit Attempts Pro can be applied to all forms when Captcha Pro is activated.

http://www.youtube.com/watch?v=xZCTEjVfu4Q

<a href="http://wordpress.org/plugins/limit-attempts/faq/" target="_blank">FAQ</a>
<a href="http://support.bestwebsoft.com" target="_blank">Support</a>

<a href="http://bestwebsoft.com/products/limit-attempts/?k=cb8137a688618f00aad733d4b0b2d014" target="_blank">Upgrade to Pro Version</a>

= Features = 

* Actions: Blocks IP addresses when the limit of login attempts is reached. 
* Actions: Adds IP addresses to the blacklist upon reaching the limit of locks. 
* Actions: Allows adjusting the settings for blocking and automatic adding to the blacklist. 
* Display: Keeps a log, in which the overall number of failed login attempts and the number of locks is documented, and shows the currents status. 
* Actions: Allows adding single IP addresses to black- and whitelists. 
* Notify: Sends email notifications to the administartor. 
* Display: Allows customizing messages that are displayed in case of the failed login attempt, address blocking or when an address is added to the blacklist. 
* Display: Allows customizing the text and layout of an email message that is sent to the administrator when an address is automatically blocked or added to the blacklist. 
* Actions: Is compatible with Htaccess plugin (by BestWebSoft), allowing to add IP address blocking data to htaccess file. 
* Actions: Is compatible with Captcha plugin (by BestWebSoft), allowing to specify whether an incorrect captcha input should be considered a failed login attempt. 

= Translation =

* Russian (ru_RU)
* Ukrainian (uk)

If you would like to create your own language pack or update the existing one, you can send <a href="http://codex.wordpress.org/Translating_WordPress" target="_blank">the text of PO and MO files</a> for <a href="http://support.bestwebsoft.com" target="_blank">BestWebSoft</a> and we'll add it to the plugin. You can download the latest version of the program for work with PO and MO files <a href="http://www.poedit.net/download.php" target="_blank">Poedit</a>.

= Technical support =

Dear users, our plugins are available for free download. If you have any questions or recommendations regarding the functionality of our plugins (existing options, new options, current issues), please feel free to contact us. Please note that we accept requests in English only. All messages in another languages won't be accepted.

If you notice any bugs in the plugin's work, you can notify us about it and we'll investigate and fix the issue then. Your request should contain URL of the website, issues description and WordPress admin panel credentials.
Moreover we can customize the plugin according to your requirements. It's a paid service (as a rule it costs $40, but the price can vary depending on the amount of the necessary changes and their complexity). Please note that we could also include this or that feature (developed for you) in the next release and share with the other users then.
We can fix some things for free for the users who provide translation of our plugin into their native language (this should be a new translation of a certain plugin, you can check available translations on the official plugin page).

== Installation == 

1. Upload the `limit-attempts` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin using the 'Plugins' menu in your WordPress admin panel.
3. You can adjust the necessary settings using your WordPress admin panel in "BWS Plugins" > "Limit Attempts".
4. Set your own options or use defaylts, create, if you need, whitelist or/and blacklist.

== Frequently Asked Questions ==

= What can the options on the "Settings" tab be used for? =

The "Settings" tab includes all the basic plugin settings that allow blocking addresses, displaying notifications and interacting with other BestWebSoft plugins. 
"Lock options:". This block includes settings for automatic blocking of the user's IP address for a certain period ( "Block address for 'x' days 'y' hours 'z' minutes" ), after a certain number of failed login attempts ( "after 'x' failed attempts" ) within a specified time frame ( "per 'x' days 'y' hours 'z' minutes" ). 
"Block options:". Here you can find settings for automatic adding of the user's IP address to the blacklist after a certain number of blocks ( "Add to the blacklist after 'x' blocks" ) within a specified period of time ( "per 'x' days 'y' hours 'z' minutes" ).
"Show additonal options for block message." This block includes fields for customizing messages displayed in the login form. To display certain variables, you can use their names, which can be found to the left of the field itself.
"Send mail with notify to administrator". This option enables sending messages to the administrator concerning users recently blocked or added to the blacklist. Also, you can specify the email address these notifications will be sent from. 
"Show additonal options for email message". Here you can find fields for customizing email messages concerning the blocking of a user. Similar to "Show additonal options for block message" block, you can use the names to display certain variables, which are located to the left of the field itself. 
"Htaccess plugin". This block enables the interaction with Htaccess plugin. All blacklist and blocking data is copied to the .htaccess file, which reduces your website's workload and improves site security. 
"Captcha". This option enables the interaction with Captcha plugin. Also, this is where you can specify whether incorrect captcha input should be considered a failed login attempt. 

= Where can I find the list of the blocked users? =

All blocked users are listed on the "Blocked addresses" tab. Also, this is where the time a block will be removed is displayed. However, there is also an option to remove the block manually. 

= How do I add users to the black- or whitelist? =

Both "Blacklist" and "Whitelist" tabs have separate fields for address input. Also, there is an option to add a range of addresses or subnets with the help of various masks. 

= There is a lot of entries in my white- and blacklists, mostly masks, how can I find out whether a certain IP address is on one of these lists?  =

To do so, you need to enter the necessary IP address in the search field. When done, all entries related to the sought-for address will be displayed in the chart. 

= Where can I find failed login attempts statistics?  =

The statistics of IP addresses of users who failed to enter login data correctly at least once is displayed on the "Log" tab. Also, this tab is a place to search for the number of failed login attempts and blocks, as well as the current status of this IP address. 

= How can I unblock a user manually? =
 
To unblock a certain user, go to the "Blocked adresses" tab on the plugin's page and search for the necessary address in the "IP adress" column. This done, a "Reset block" option will appear when you move the cursor to the user's address. Click on this caption and the ip address will be unblocked. To unblock a group of users, you can use "Bulk Actions": mark the addresses that have to be unblocked, choose the "Reset block" action and click "Apply."

= What will happen if I add a user to both the white and black lists?  =

In case it happened so that a user is on both the black- and whitelist, the whitelist will have a higher priority. 

= I accidentally added my address to the blacklist, how can I fix that?  =

There are several ways to fix this issue:

1. Log in to your account from another computer with a different ip address and remove your ip address from the blacklist. 
2. Log in to your account through Proxy Avoidance program or website and remove your ip address from the blacklist. 
3. If you have access to the database, find the datasheet with the ip addresses on the blacklist (it ends with "lmtttmpts_blacklist") and remove your ip address from this datasheet. However, this method should only be used at the very outside, as, chances are, the plugin will not function properly as a result. 

= I do not receive email notifications about blocked ip addresses, what shall I do?  =

First off, make sure you have selected the option to send email notifications to the administrator on the plugins settings page. Also, make sure your email is entered correctly. 
If you have checked all of the abovementioned and everything seems to be correct, it is possible that the mailout was blocked or delayed significantly by your hosting. Also, it is likely that your emails are automatically moved to the spam box, so you might want to check it. 

= I've noticed a short delay with automatic blocking of a user. Did I do something wrong?  =

This may happen when you enable sending email notifications. No need to worry, your site's and your plugin's performance will not be affected whatsoever. 

= I have some problems with the plugin's work. What Information should I provide to receive proper support? =

Please make sure that the problem hasn't been discussed yet on our forum (<a href="http://support.bestwebsoft.com" target="_blank">http://support.bestwebsoft.com</a>). If no, please provide the following data along with your problem's description:
1. the link to the page where the problem occurs
2. the name of the plugin and its version. If you are using a pro version - your order number.
3. the version of your WordPress installation
4. copy and paste into the message your system status report. Please read more here: <a href="https://docs.google.com/document/d/1Wi2X8RdRGXk9kMszQy1xItJrpN0ncXgioH935MaBKtc/edit" target="_blank">Instuction on System Status</a>

== Screenshots ==

1. Plugin settings in WordPress admin panel.
2. Additonal settings which allow to customize error messages in the form.
3. Plugin additonal settings which allow to customize email messages.
4. Tab with Blocked adresses.
5. Tab with Blacklist settings.
6. Tab with Whitelist settings.
7. Tab with Statistics.
8. Message with allowed retries.
9. Message with error when user has been blocked.
10. Message with error when user has been added to the blacklist.

== Changelog ==

= V1.0.7 - 30.01.2015 =
* Update : Compatibility with new Htaccess was added.
* Update : The work of IP unblocking function was improved.

= V1.0.6 - 30.12.2014 =
* Update : Settings tab on plugin settings page was updated (interactivity was improved).
* Update : The name of the 'Log' tab on the plugin settings page was changed to 'Statistics'.
* Bugfix : Performance issue on 'Statistics' page was fixed.

= V1.0.5 - 11.09.2014 =
* Update : We updated all functionality for wordpress 4.0.
* Bugfix : Added missing closing tags </a>.

= V1.0.4 - 08.08.2014 =
* Budfix : Security Exploit was fixed.

= V1.0.3 - 04.08.2014 =
* Update : We updated all functionality for wordpress 4.0-beta2.
* Budfix : Bug with Number of failed attempts is fixed.

= V1.0.2  - 19.06.2014 = 
* Bugfix : Added support for working with multisite.
* NEW : Added the ability to customize error messages in login form.
* NEW : Added the ability to customize customize email messages.
* NEW : Java scripts was added.

= V1.0.1  - 27.05.2014 = 
* Bugfix : Deleting unused sql query.
* NEW : Added messages in admin page.

= V1.0.0  - 05.05.2014 = 
* Bugfix : The code refactoring was performed.
* NEW : Css-style was added.
* NEW : Added messages in login form.

== Upgrade Notice ==

= V1.0.7 =
Compatibility with new Htaccess was added. The work of IP unblocking function was improved.

= V1.0.6 =
Settings tab on plugin settings page was updated (interactivity was improved). The name of the 'Log' tab on the plugin settings page was changed to 'Statistics'. Performance issue on 'Statistics' page was fixed.

= V1.0.5 =
We updated all functionality for wordpress 4.0. Added missing closing tags </a>.

= V1.0.4 =
Security Exploit was fixed.

= V1.0.3 =
We updated all functionality for wordpress 4.0-beta2. Bug with Number of failed attempts is fixed.

= V1.0.2  = 
Added support for working with multisite. Added the ability to customize error messages in login form. Added the ability to customize customize email messages. Java scripts was added.

= V1.0.1  = 
Deleting unused sql query. Added messages in admin page.

= V1.0.0 =
The code refactoring. Css-style was added. Added messages in login form.
