=== Plugin Name ===
Contributors: catalinsendsms
Tags: sms, admin, dashboard, sendsms, marketing, subscribers, campaign, phone, 2fa
Requires at least: 4.0
Tested up to: 6.9
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

IMPORTANT: Our 2fa features were designed to work with the WordPress default login form (the one accessed via wp-admin when you first install WordPress). If you are having another login form, this may break it. Make sure to check compatibility in a development environment.

Please make an account on https://www.sendsms.ro/en/

== Description ==

This plugin has two main functionalities.
1. The ability to add, delete, edit and message subscribers via SMS.
2. The ability to add another security layer to your WordPress site.

This plugin is based on our public API: https://www.sendsms.ro/api/#introduction 


== Installation ==

1. Unzip the folder under `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Features ==

* Two-factor authentication based on the role of the user with a custom verification message
* Subscription system with widgets, IP-ban, SMS verification, and limitation on the number of requests coming from a specific IP
* SMS history table
* Subscribers table
* Sync subscribers with sendSMS contacts
* Phone field in user edit form
* Send SMS to any number (Send a test SMS)
* Send SMS to your SMS subscribers or users (based on role)
* Both subscribe and unsubscribe widget

== Usage ==

Appearance > Widgets:

You will have 2 widgets: 
* SendSMS Subscription: add a title and the link to your gdpr page/document
* SendSMS Unsubscribe: just add a title to it

SendSMS Dashboard > SendSMS Dashboard

* General: add your sendSMS credentials here and set the country code of your phone numbers
* User: here you can enable the 2fa system; each field has a description below it
* Subscription: here you can enable SMS verification, change the subscribe message, set an IP limit, or restrict specific IPs

SendSMS Dashboard > Send a test SMS

Here you can send a message to every number. 
You can add an unsubscribe link or shorten every link you insert inside the message

SendSMS Dashboard > History

Here you can see a log of every message you sent

SendSMS Dashboard > Subscribers

Here you can see, add, edit, delete and sync your contacts.
The synchronization is not needed if you want to send SMS to your subscribers

SendSMS Dashboard > SMS sending

This is the place where you can send an SMS to your subscribers/ users

== Changelog ==

= 1.0 =
* Initial version
