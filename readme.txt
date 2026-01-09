=== Limit Attempts by BestWebSoft - WordPress Anti-Bot and Security Plugin for Login and Forms ===
Contributors: bestwebsoft
Donate link: https://bestwebsoft.com/donate/
Tags: login, security, limit login attempts, limit attempts, failed attempts
Requires at least: 6.2
Tested up to: 6.8.2
Stable tag: 1.3.2
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Protect your WordPress website from brute force attacks by limiting the number of failed login attempts. Improve security and stop bots.

== Description ==

Limit Attempts is a powerful WordPress security plugin that protects your site from brute-force attacks and bot logins. It limits the number of failed login attempts per user and blocks IP addresses for a configurable time period based on your settings.

You can manage deny and allow lists, receive email alerts, and hide login or contact forms from blocked users. This plugin offers seamless protection without the need for coding and is compatible with other BestWebSoft security tools.

Shield your site against automated attacks and unauthorized access today.

[View Demo](https://bestwebsoft.com/demo-for-limit-attempts/?ref=readme)

https://www.youtube.com/watch?v=xZCTEjVfu4Q

= Free Features =

* Automatically block IP addresses after exceeding allowed login attempts
* Add IPs that exceed block limit to deny list automatically
* Manually add IP addresses to:
	* Deny list
	* Allow list
* Compatible with [Contact Form](https://bestwebsoft.com/products/wordpress/plugins/contact-form/?k=fc7e2e440918324853c2060dbe6d9dc9):
    * Set email sending interval
    * Set number of emails allowed per interval
* Hide login, register, and lost password forms for blocked or denylisted IPs
* Add denylisted IPs to `.htaccess` file with [Htaccess](https://bestwebsoft.com/products/wordpress/plugins/htaccess/?k=0792e5d1f813e0de1fe113076b7706fd) to reduce database load
* Treat incorrect captcha as a failed login with [Captcha](https://bestwebsoft.com/products/wordpress/plugins/captcha/?k=4866b64ad8a5a969edaa66a4a688b46c)
* View detailed login attempt statistics:
	* IP address
	* Failed attempts
	* Block count
	* Status
* Customize error messages for:
	* Failed login
	* Blocked users
	* Denylisted users
* Send email alerts for blocked and denylisted users to:
	* User email
	* Custom email
* Limit Attempts Captcha for default forms
* Limit Attempts export/import
* Compatible with latest WordPress version
* Incredibly simple settings for fast setup without modifying code
* Detailed step-by-step documentation and videos
* Multilingual and RTL ready

> **Pro Features**
>
> All Free features included, plus:
>
> * Add IP ranges and masks to deny/allow list
> * Block IPs by country using GeoIP database
> * Deny or allow access by email address or domain
> * Control total failed attempts before block
> * Manage deny/allow lists with:
>     * Country
>     * IP range
>     * Reason
> * Compatible with:
>     * [Captcha Pro](https://bestwebsoft.com/products/wordpress/plugins/captcha/?k=4866b64ad8a5a969edaa66a4a688b46c)
>     * [Captcha Plus](https://codecanyon.net/item/captcha-plus/9656420)
>     * [reCaptcha](https://bestwebsoft.com/products/wordpress/plugins/google-captcha/?k=fd764017a5f3f57d9c307ef96b4b9935)
> * Configure rules for non-existent usernames:
>     * Use standard block settings
>     * Immediate IP block
>     * Immediate deny list
> * Log tab includes:
>     * IP address
>     * Username
>     * Password
>     * Hostname
>     * Event type
>     * Form source
>     * Timestamp
> * Login statistics and brute-force attempts chart in settings and dashboard widget
> * Multisite network settings support
> * Use plugin's captcha on default forms
> * Priority support within 1 business day ([Support Policy](https://bestwebsoft.com/support-policy/))
>
> [Upgrade to Pro Now](https://bestwebsoft.com/products/wordpress/plugins/limit-attempts/?k=cb8137a688618f00aad733d4b0b2d014)

Got a feature request? We want to hear it: [Suggest a Feature](https://support.bestwebsoft.com/hc/en-us/requests/new)

= Documentation & Videos =

* [[Doc] User Guide](https://docs.google.com/document/d/1fbB5FZ8-wSxg85Huaiha5fUHjp1diEvKe9sOLzc8diQ/)
* [[Doc] Installation](https://docs.google.com/document/d/1-hvn6WRvWnOqj5v5pLUk7Awyu87lq5B_dO-Tv-MC9JQ/)
* [[Video] Installation Instruction](https://www.youtube.com/watch?v=BZ9WZ3G9ves)

= Help & Support =

Need help? Visit our Help Center - <https://support.bestwebsoft.com/>

= Translation =

* Polish (pl_PL) – thanks to [Damian Dąbrowski](mailto:dabek1812@gmail.com)
* Russian (ru_RU)
* Ukrainian (uk)
* Italian (it_IT)
* Portuguese (pt_PT)
* Arabic (ar)
* German (de_DE)
* Spanish (es_ES)
* French (fr_FR)

Help us improve translations! Send PO/MO files via [Support Form](https://support.bestwebsoft.com/hc/en-us/requests/new) or use [Poedit](http://www.poedit.net/download.php).

= Recommended Plugins =

* [Updater](https://bestwebsoft.com/products/wordpress/plugins/updater/?k=1babc7691c564636f8fddb7698f8f43e) – Auto-update WordPress, plugins, and themes.
* [Captcha](https://bestwebsoft.com/products/wordpress/plugins/captcha/?k=4866b64ad8a5a969edaa66a4a688b46c) – Anti-spam captcha plugin for WordPress forms.
* [Htaccess](https://bestwebsoft.com/products/wordpress/plugins/htaccess/?k=0792e5d1f813e0de1fe113076b7706fd) – Allow/deny access by IP, hostname, etc.

== Installation ==

1. Upload `limit-attempts` to `/wp-content/plugins/`.
2. Activate via the "Plugins" menu in WordPress admin.
3. Go to "Limit Attempts" in admin to adjust settings.
4. Configure allow/deny lists or use defaults.

[Step-by-step Installation Guide](https://docs.google.com/document/d/1zBn8PxGMR7v6hWJgT2vogwzP_rzJ6IipbxACnmuNa6E/)

https://www.youtube.com/watch?v=BZ9WZ3G9ves

== Frequently Asked Questions ==

= What are the "Settings" tab options? =

These control how and when to block users, send notifications, and integrate with other plugins:
* "Lock options": Set how long IPs are blocked and after how many failed attempts.
* "Block options": Add IPs to deny list after multiple blocks.
* "Block message": Customize messages shown to blocked users.
* "Send mail": Enable email alerts to admins.
* "Email message": Customize email templates.
* "Htaccess plugin": Sync deny list with `.htaccess`.
* "Captcha": Consider incorrect captcha as failed login.

= Where can I find blocked users? =

Under the "Blocked addresses" tab, along with unblock time and manual unblock option.

= How do I add IPs to deny or allow list? =

Use the "Deny list" or "Allow list" tabs. You can input single IPs, ranges, or masks.

= How do I check if an IP is in the lists? =

Use the search field in the respective list tab to find related entries.

= Where are failed login stats shown? =

Go to the "Log" tab. You’ll see IPs, failed attempts, blocks, and current status.

= How to manually unblock an IP? =

Go to the "Blocked addresses" tab, hover over the IP, and click "Reset block." For multiple IPs, use bulk action.

= What if an IP is in both allow and deny list? =

Deny list takes priority and the user will be blocked.

= I blocked myself. How can I fix it? =

1. Log in from another IP and remove yours.
2. Use a proxy/VPN to access and unblock.
3. Use database access to remove your IP from the `lmtttmpts_blacklist` table (advanced users only).

= Why am I not receiving notification emails? =

Check:
* Notifications are enabled in settings
* Correct email entered
* Emails aren’t going to spam
* Hosting isn’t blocking outgoing mail

= Why is blocking delayed? =

This may happen when sending emails is enabled. It doesn't affect plugin performance.

== Screenshots ==

1. Message with allowed retries.
2. Error message when a user has been blocked.
3. Error message when a user has been added to deny list.
4. Plugin settings in WordPress admin panel.
5. Additional settings which allow to customize error messages in the form.
6. Plugin additional settings which allow to customize email messages.
7. Tab with Blocked IP addresses.
8. Tab with Blocked Email addresses.
9. Deny list with IP addresses.
10. Deny list with email addresses.
11. Add new Ip.
12. Allow list settings tab.
13. Tab with Statistics.

== Changelog ==

= V1.3.2 - 04.08.2025 =
* Update : BWS panel section was updated.
* Update : All functionality was updated for WordPress 6.8.2.
* New: Limit Attempts export/import was added.
* Bugfix : Fixed small bags.

= V1.3.1 - 30.04.2024 =
* Update : Security fixes.
* Update : BWS panel section was updated.
* Update : All functionality was updated for WordPress 6.5.

= V1.3.0 - 08.03.2024 =
* Update : All functionality was updated for WordPress 6.4.
* Update : BWS panel section was updated.
* Pro: Limit Attempts Captcha for default forms was added.
* Bugfix : Database queries have been optimized.

= V1.2.9 - 19.05.2021 =
* Pro : Email priority Deny and Allow lists issue has been fixed.
* Bugfix : The issue with displaying pagination on tables has been fixed.
* Update : Adding items to Deny and Allow lists has been moved to a separate page.
* Update : The plugin settings page has been updated.
* Update : BWS panel section was updated.
* Update : All functionality was updated for WordPress 5.7.2.

= V1.2.8 - 24.11.2020 =
* Bugfix : Database queries have been optimized.
* Update : BWS Panel section was updated.
* Update : Blacklist and whitelist replaced with deny list and allow list.

= V1.2.7 - 15.07.2020 =
* NEW : Ability to block emails sent from certain email address via Contact Form by BestWebSoft was added.
* Bugfix : Fixed small bags.
* Update : BWS panel section was updated.

= V1.2.6 - 04.09.2019 =
* Update : The deactivation feedback has been changed. Misleading buttons have been removed.

= V1.2.5 - 04.06.2019 =
* PRO : Ability to add the email address to the Whitelist has been added.
* Bugfix : Fixed small bags.
* Update : BWS menu has been updated.

= V1.2.4 - 04.03.2019 = 
* PRO : Ability to save the "Username" and "Password" which were used in the failed login attempts.

= V1.2.3 - 15.01.2019 =
* Update : All functionality was updated for WordPress 5.0.2.
* Bugfix : Fixed small bags.

= V1.2.2 - 19.10.2017 =
* Update : The Compatibility with Captcha Pro by BestWebSoft has been improved.

= V1.2.1 - 01.03.2017 =
* Bugfix : The bug with saving settings on the multisite was fixed.
* Bugfix : The bug with saving IP to whitelist or blacklist was fixed.
* PRO : The bug with updating lists was fixed.
* PRO : The bug with adding IP to Htaccess by BestWebSoft plugin has been fixed.

= V1.2.0 - 13.10.2017 =
* Update : All functionality for wordpress 4.8.2 has been updated.
* PRO : Compatibility with the Google Captcha (reCAPTCHA) by BestWebSoft has been added.

= V1.1.9 - 19.06.2017 =
* Update : All functionality for wordpress 4.8 was updated.
* Pro : Statistic displaying has been updated.

= V1.1.8 - 17.03.2017 =
* NEW: An ability to add IP address to the Whitelist from the Blocked List.
* Update : The plugin settings page has been updated.

= V1.1.7 - 06.10.2016 =
* Update : Block and blacklist functionality improved.
* Pro : An ability to edit the reason of adding to black- or whitelist has been added.
* Pro : Compatibility with the Captcha Pro by BestWebSoft plugin has been updated. WooCommerce plugin support has been added.

= V1.1.6 - 08.08.2016 =
* Update : All functionality for WordPress 4.6 was updated.

= V1.1.5 - 27.06.2016 =
* Update : The Polish language file has been updated.
* Update : BWS Panel section is updated.

= V1.1.4 - 08.04.2016 =
* Update : The Polish language file has been updated.
* Bugfix : The bug with the displaying of the HTML tags in error messages has been fixed.
* Bugfix : The bug with the automatic unblocking of users has been fixed.
* Bugfix : The bug with the automatic blacklisting of users has been fixed.
* Bugfix : The bug with the creating of plugin's database tables has been fixed.

= V1.1.3 - 26.01.2016 =
* NEW : Ability to hide the login form, the registration form and the lost password form for blocked or blacklisted IPs.
* Bugfix : Bug with displaying of list of blocked IPs has been fixed.
* Bugfix : Bugs with the recording/removing of statistical data in the database have been fixed.
* Bugfix : Bugs with the pagination on plugin`s settings pages have been fixed.
* Update : Compatibility with the Htaccess by BestWebSoft plugin has been updated.
* Update : Functionality for the login form, the registration form and the lost password form has been updated.
* Update : Functionality for wordpress 4.4.1 has been updated.

= V1.1.2 - 21.10.2015 =
* Bugfix : We fixed the bug with adding IP to the blacklist.
* Update : BWS plugins section is updated.

= V1.1.1 - 09.10.2015 =
* NEW : Ability to add your IP in to the whitelist.
* Update : We updated the list with IP addresses displaying in the black- and whitelist.
* Bugfix : We fixed SQL injection vulnerability in the function of obtaining IP-address of the user.
* Update : We updated all functionality for wordpress 4.3.1.

= V1.1.0 - 21.07.2015 =
* NEW : Ability to restore default settings.

= V1.0.9 - 12.06.2015 =
* Bugfix: We changed the mechanism of unlocking IP addresses on the timer.

= V1.0.8 - 14.05.2015 =
* NEW : The Polish language file is added.
* Bugfix: Undefined user blocking after plugin activation is fixed.
* Bugfix: Access priority when IP is included to the black- and whitelist at the same time (blacklist has higher priority).
* NEW: Ability to search by part IP.
* NEW: Additional notifications on the plugin pages.

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
* Bugfix : Security Exploit was fixed.

= V1.0.3 - 04.08.2014 =
* Update : We updated all functionality for wordpress 4.0-beta2.
* Bugfix : Bug with Number of failed attempts is fixed.

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

= V1.3.2 =
* The compatibility with new WordPress version updated.
* Usability improved.
* New features added.
* Bugs fixed.

= V1.3.1 =
* Bugs fixed.

= V1.3.0 =
* The compatibility with new WordPress version updated.
* Usability improved.
* New features added.
* Bugs fixed.

= V1.2.9 =
* Bugs fixed.
* Usability improved.
* The compatibility with new WordPress version updated.

= V1.2.8 =
* Bugs fixed.
* Plugin optimization completed.

= V1.2.7 =
* New features added.

= V1.2.6 =
* Usability improved.

= V1.2.5 =
* Bugs fixed.
* Functionality improved.

= V1.2.4 =
* New features added.

= V1.2.3 = 
* Functionality improved.
* Bugs fixed.
* The compatibility with new WordPress version updated.

= V1.2.2 =
* Functionality improved.

= V1.2.1 =
* Bugs fixed.

= V1.2.0 =
* The compatibility with new WordPress version updated.

= V1.1.9 =
* Usability improved.

= V1.1.8 =
* Usability improved.

= V1.1.7 =
* Functionality improved.

= V1.1.6 =
* The compatibility with new WordPress version updated.

= V1.1.5 =
The Polish language file has been updated. BWS Panel section is updated.

= V1.1.4 =
The Polish language file has been updated. The bug with the displaying of the HTML tags in error messages has been fixed. The bug with the automatic unblocking of users has been fixed. The bug with the automatic blacklisting of users has been fixed. The bug with the creating of plugin`s database tables has been fixed.

= V1.1.3 =
Ability to hide the login form, the registration form and the lost password form for blocked or blacklisted IPs. Bug with displaying of list of blocked IPs has been fixed. Bugs with the recording/removing of statistical data in the database have been fixed. Bugs with the pagination on plugin`s settings pages have been fixed. Compatibility with the Htaccess by BestWebSoft plugin has been updated. Functionality for the login form, the registration form and the lost password form has been updated. Functionality for wordpress 4.4.1 has been updated.

= V1.1.2 =
We fixed the bug with adding IP to the blacklist. BWS plugins section is updated.

= V1.1.1 =
Ability to add your IP in to the whitelist. We updated the list with IP addresses displaying in the black- and whitelist. We fixed SQL injection vulnerability in the function of obtaining IP-address of the user.
We updated all functionality for wordpress 4.3.1.

= V1.1.0 =
Ability to restore default settings.

= V1.0.9 =
We changed the mechanism of unlocking IP addresses on the timer.

= V1.0.8 =
The Polish language file is added. Undefined user blocking after plugin activation is fixed. Access priority when IP is included to the black- and whitelist at the same time (blacklist has higher priority). Ability to search by part IP. Additional notifications on the plugin pages.

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
