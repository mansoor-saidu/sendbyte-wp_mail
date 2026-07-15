=== Mansoor SMTP for SendByte ===
Contributors: mansoor8080
Tags: smtp, email, sendbyte, mail, transactional
Requires at least: 5.5
Tested up to: 7.0
Stable tag: 1.1.4
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send all WordPress emails through SendByte SMTP with logging, test email, sandbox mode, delivery dashboard, and connection health check.

== Description ==

Mansoor SMTP for SendByte hooks into WordPress's built-in PHPMailer to route all outgoing emails through SendByte's SMTP servers (`smtp.sendbyte.africa`). No third-party API keys required — just your SendByte API key and you're set.

This plugin is **not officially built or maintained by SendByte**. It was created by Mansoor Saidu to provide a simple, reliable SMTP integration for WordPress users who send transactional email through SendByte.

= Features =

* **SMTP integration** — Configures PHPMailer with STARTTLS on port 587 automatically.
* **Sandbox mode** — Test your email flow without sending to real inboxes.
* **Email logging** — Store every outbound email with delivery status (sent, delivered, failed).
* **Test email** — Send a quick test from the settings page to verify everything works.
* **Delivery dashboard** — See delivered, bounced, failed, and pending counts pulled straight from the SendByte API.
* **Connection health** — Verify your API key, check sandbox/live mode, view verified domains, and see monthly usage.
* **From name & email** — Customize the sender details that recipients see.
* **Onboarding flow** — Step-by-step guide when you first activate the plugin.

== External services ==

This plugin relies on SendByte to deliver your emails and fetch delivery statistics.

* **SMTP Sending:** The plugin configures WordPress to send all emails via SendByte's SMTP server at `smtp.sendbyte.africa`. The email content (recipient, sender, subject, and body) is transmitted to this server for delivery.
* **API Integration:** The plugin connects to the SendByte API at `api.sendbyte.africa` to verify your API key and retrieve your account's sending stats (delivered, bounced, etc.).

This service is provided by SendByte.
* [Terms of Service](https://sendbyte.africa/terms)
* [Privacy Policy](https://sendbyte.africa/privacy)

== Installation ==

1. Upload the `mansoor-smtp-for-sendbyte` folder to `/wp-content/plugins/`, or install via **Plugins > Add New**.
2. Activate the plugin.
3. Go to **Settings > SendByte**.
4. Enter your SendByte API key (get one from your SendByte dashboard).
5. Set your From Email and From Name.
6. (Optional) Send a test email to verify everything is working.

== Screenshots ==

1. SMTP configuration, sandbox toggle, test email, and email log in the settings page.

== Frequently Asked Questions ==

= Do I need a SendByte account? =

Yes. You need a SendByte account and a valid API key. Sign up at sendbyte.africa.

= Does this plugin send data to external servers? =

Yes. When you configure an API key, the plugin sends email data (to, from, subject, body) to SendByte's SMTP servers and API (api.sendbyte.africa) to deliver your emails. The SendByte API is also queried to show delivery stats and verify your connection. No data is sent to any other third party, and no tracking or analytics are collected by this plugin.

= Can I use this plugin with other SMTP providers? =

No. It is specifically built for SendByte's SMTP service at smtp.sendbyte.africa.

= Is my API key stored securely? =

The API key is stored in your WordPress options table. It is sent only to SendByte's servers during SMTP authentication and API calls. The key is never logged, displayed on the frontend, or sent to any other third party.

= Does this plugin send emails via REST or SMTP? =

It uses WordPress's built-in PHPMailer configured to send via SMTP through smtp.sendbyte.africa:587 with STARTTLS.

== Changelog ==

= 1.1.4 =
* Updated generic and non-compliant prefixes to a unique `mansmtp` prefix.


= 1.1.3 =
* Renamed plugin to Mansoor SMTP for SendByte to comply with WordPress.org guidelines.


= 1.1.0 =
* Added delivery dashboard with real-time stats from SendByte API.
* Added connection health check (key validation, mode, domains, quota).
* Added onboarding flow with step indicator for new users.
* Improved sandbox toggle with visual on/off switch.
* Added activation toast notice prompting API key setup.
* Various UI refinements and security hardening.

= 1.0.0 =
* Initial release. SMTP configuration, email logging, test email, and sandbox mode.

== Upgrade Notice ==

= 1.1.0 =
Adds delivery dashboard and connection health check. New users see an onboarding guide on first activation.
