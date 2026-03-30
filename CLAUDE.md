# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Module Identity

This is the `Bidzi_Cards` Magento 2 payment module (`bidzi/magento` composer package). It integrates with the Bidzi payment gateway to process credit/debit card payments for Latin American merchants (Mexico, Colombia, Peru).

## Development Commands

### Installation

```bash
composer require bidzi/magento
php bin/magento module:enable Bidzi_Cards --clear-static-content
php bin/magento setup:upgrade
php bin/magento cache:clean
```

### After Code Changes

```bash
php bin/magento setup:upgrade          # Run after modifying etc/module.xml or db_schema.xml
php bin/magento cache:clean            # Always clear cache after config/template changes
php bin/magento setup:static-content:deploy   # After changing JS/CSS/templates
```

### Update Module

```bash
composer clear-cache && composer update bidzi/magento
php bin/magento setup:upgrade
php bin/magento cache:clean
```

## Architecture Overview

### Payment Flow

1. **Checkout page** loads Bidzi's external JS library (`https://checkout.bidzi.mx/script/bidzi.js`) and initializes it via `initOrkestaPay()` in [cc-form.js](view/frontend/web/js/view/payment/method-renderer/cc-form.js)
2. On "Place Order": the frontend tokenizes the card via the Bidzi library, then POSTs to `/bidzi/payment/order`
3. [Controller/Payment/Order.php](Controller/Payment/Order.php) creates a Bidzi order and payment via the API
4. Based on payment status:
   - `COMPLETED` → place Magento order immediately
   - `PAYMENT_ACTION_REQUIRED` → handle 3DS (redirect or modal)
   - `FAILED/REJECTED` → show error
5. After 3DS: [Controller/Payment/Success.php](Controller/Payment/Success.php) fetches payment details, imports to quote, and creates the Magento order via `CartManagementInterface`

### Key Classes

| Class                                                          | Purpose                                                                                             |
| -------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| [Model/Payment.php](Model/Payment.php)                         | Main payment method, extends `Magento\Payment\Model\Method\Cc`. Handles authorize, capture, refund. |
| [Model/BidziConfigProvider.php](Model/BidziConfigProvider.php) | Provides checkout JS config (merchant credentials, cart total, card metadata)                       |
| [Model/Utils/BidziRequest.php](Model/Utils/BidziRequest.php)   | All HTTP communication with Bidzi API using GuzzleHTTP. Handles OAuth token retrieval.              |
| [Model/Utils/AddressFormat.php](Model/Utils/AddressFormat.php) | Formats addresses per country (MX/CO/PE have different field structures)                            |
| [Model/Utils/ProductFormat.php](Model/Utils/ProductFormat.php) | Extracts product data from orders for Bidzi API payload                                             |
| [Model/Utils/Currency.php](Model/Utils/Currency.php)           | Maps supported currencies per country                                                               |

### Bidzi API Endpoints

Base URLs: `https://api.sand.bidzi.mx` (sandbox) / `https://api.bidzi.mx` (production)

- `POST /v1/oauth/tokens` — get Bearer token (client credentials)
- `POST /v1/orders` — create order
- `POST /v1/payments` — create payment
- `GET /v1/payments/{id}` — get payment status
- `POST /v1/payments/{id}/complete` — complete 3DS
- `POST /v1/payments/{id}/refund` — refund
- `GET /v1/payment-methods/{id}` — get card details

### Frontend Components

- [view/frontend/web/js/view/payment/method-renderer/cc-form.js](view/frontend/web/js/view/payment/method-renderer/cc-form.js) — main checkout JS (card tokenization, 3DS handling, order placement)
- [view/frontend/web/template/payment/bidzi-form.html](view/frontend/web/template/payment/bidzi-form.html) — payment form Knockout template
- [view/frontend/templates/load_bootstrap.phtml](view/frontend/templates/load_bootstrap.phtml) — loads external Bidzi JS library

### Routes

- `POST /bidzi/payment/order` — create Bidzi order+payment, return status to frontend
- `POST /bidzi/payment/complete` — complete 3DS authentication
- `GET /bidzi/payment/success` — post-payment redirect handler, creates Magento order
- `POST /bidzi/cards/webhook` — Bidzi webhook endpoint (CSRF-exempt)

### Configuration

Admin path: **Stores > Configuration > Sales > Payment Methods > Bidzi**

Encrypted fields in database: `client_secret`, `whsec` (webhook signing secret).

Default payment action: `authorize_capture`. Supported card types: Visa, Mastercard, American Express, Carnet (CN).

### Supported Countries & Currencies

- **Mexico (MX):** MXN, USD
