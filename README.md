# SendByte Mail

Send all WordPress emails through SendByte SMTP with logging, test email, sandbox mode, delivery dashboard, and connection health check.

This plugin hooks into WordPress's built-in PHPMailer to route all outgoing emails through SendByte's SMTP servers (`smtp.sendbyte.africa`). No third-party API keys required — just your SendByte API key and you're set.

## Features

* **SMTP integration** — Configures PHPMailer with STARTTLS on port 587 automatically.
* **Sandbox mode** — Test your email flow without sending to real inboxes.
* **Email logging** — Store every outbound email with delivery status (sent, delivered, failed).
* **Test email** — Send a quick test from the settings page to verify everything works.
* **Delivery dashboard** — See delivered, bounced, failed, and pending counts pulled straight from the SendByte API.
* **Connection health** — Verify your API key, check sandbox/live mode, view verified domains, and see monthly usage.
* **From name & email** — Customize the sender details that recipients see.
* **Onboarding flow** — Step-by-step guide when you first activate the plugin.

## Installation

1. Upload the `sendbyte-mail` folder to `/wp-content/plugins/`, or install via **Plugins > Add New** using the provided release zip.
2. Activate the plugin.
3. Go to **Settings > SendByte**.
4. Enter your SendByte API key (get one from your SendByte dashboard).
5. Set your From Email and From Name.
6. (Optional) Send a test email to verify everything is working.

## Disclaimer

This plugin is **not officially built or maintained by SendByte**. It was created by Mansoor Saidu to provide a simple, reliable SMTP integration for WordPress users who send transactional email through SendByte.
