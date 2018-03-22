Contributors: adamgoucher
Tags: tether
Requires at least: 4.8
Tested up to: 4.9.2
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

The Tether Plugin for Contact Form 7 sends form submission information into your Tether client.

= Configuration =

Once installed, in the sidebar in the Contact menu, there will now be a 'Tether Settings' option. Here are the settings you need from your Client Services person
- Client ID: 
- OAuth Client ID: 
- OAuth Client Secret: 
- OAuth Client Username: 
- OAuth Client Password: 

Once configured, you can enable Tether on any CF7 form by checking the 'Enable Tether on this form' on the Tether tab of the form.

If you are using different field names than Tether expects, you need to go through a mapping step.

Here are the set of fields available in Tether;
- phone
- name
- first_name
- last_name
- email
- language
- birth_day
- birth_month
- birth_year
- province
- postal
- message

Note: there must be at least 'phone' or 'email' in your submission to Tether

If you want to add this person to a list by adding a hidden field into your form called 'tether-lists'

[hidden tether-lists "Main Website Contact Form"]
