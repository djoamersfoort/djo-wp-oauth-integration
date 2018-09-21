=== WP OAuth Integration ===
Contributors: arbet01
Tags:  OAuth2 Service, oauth2, OAuth provider, Provider, OAuth, OAuth client, Meetup
Requires at least: 4.9.1
Tested up to: 4.9.1
Stable tag: 0.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create and Manage an OAuth 2.0 Integration powered by WordPress.

== Description ==
Allow users to login/register via different OAuth2 Providers 

List of features:

* Works with Meetup.com accounts out of the box
* Developers can easily extend in order to work with different OAuth2 providers
* User's Name, URL, description and avatar are automatically updated (with the option to turn it off)
* Use the shortcode [woi_meetup_login_link] to display  sign in link anywhere on your site for provider 'Meetup.com'.
* You can use [woi_meetup_login_link text='Your Custom Link Text'] to generate a sign-in link with your own text.
* [woi_meetup_login_link class = 'class1 class2'] will add the corresponding CSS classes to the generated link

== Installation ==
1. Upload archive to the \"/wp-content/plugins/\" directory.
2. Activate the plugin through the \"Plugins\" menu in WordPress.
3. Enter your API Keys under Settings->Meetup Login

== Changelog ==

= 0.1.3 =
 * Update default provider
 * Change CURL to HTTP API

= 0.1.2 =
 * Update provider username format

= 0.1.1 =
 * Add default provider

= 0.1.0 =
 * Initial First Version WordPress Plugin
