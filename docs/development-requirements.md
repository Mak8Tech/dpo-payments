# Requirements Document: Mak8Tech DPO Payment Processing Package for Laravel 12

## 1. Introduction

### 1.1 Project Overview

This document outlines the requirements for developing a Laravel 12 package named under the vendor "Mak8Tech" (e.g., `mak8tech/dpo-payments`) to facilitate payment processing using the DPO (Direct Pay Online) Group payment gateway. The package will handle both one-time (per-item) and recurring monthly payments, with a primary focus on the Zambian market but configurable support for other DPO-supported countries across Africa. It will include backend logic for API integration, as well as frontend assets: Blade templates and components for an admin dashboard, and React components suitable for integration into a Next.js frontend application.

DPO Group is a leading payment gateway in Africa, operating in over 20 countries including Zambia, Kenya, Tanzania, Uganda, South Africa, Rwanda, Ethiopia, Nigeria, Ghana, Botswana, Namibia, Mauritius, Malawi, Zimbabwe, Côte d'Ivoire, and others. It supports various payment methods such as credit/debit cards (Visa, Mastercard, American Express, Diners Club) and mobile money options, which vary by country (e.g., Airtel Money, MTN MoMo, and Zamtel Kwacha in Zambia). The package will leverage DPO's API for transaction creation, verification, and management, with flexibility for multi-country operations.

### 1.2 Objectives

- Provide a reusable Laravel package for seamless DPO integration.
- Support one-time payments for individual items and recurring monthly subscriptions.
- Include publishable Blade assets for admin interfaces to manage payments, transactions, and subscriptions.
- Provide React components for a Next.js frontend to handle payment forms, status displays, and user interactions.
- Ensure compliance with payment regulations in supported countries and DPO's security standards.
- Follow Laravel 12 best practices for package development, including Vite for asset bundling.

### 1.3 Stakeholders

- Developers integrating the package into Laravel applications.
- End-users in primarily Zambia and other supported African countries making payments via local methods.
- Administrators managing transactions through the dashboard.

## 2. Scope

### 2.1 In-Scope

- Geographical focus: Primarily Zambia, with configurable support for other DPO-supported countries (e.g., Kenya, Tanzania, Uganda, South Africa, Rwanda, Ethiopia, Nigeria, Ghana, Botswana, Namibia, Mauritius, Malawi, Zimbabwe, Côte d'Ivoire).
- Currencies: Primarily Zambian Kwacha (ZMW), but configurable for other local currencies (e.g., KES for Kenya, ZAR for South Africa, TZS for Tanzania) and global ones like USD.
- Payment types:
  - Per-item: One-time payments for individual products or services.
  - Monthly: Recurring subscriptions billed monthly.
- Integration with DPO API for token creation, verification, updates, refunds, and cancellations, with parameters for country and currency selection.
- Recurring payments via DPO's PaySubs (debit order via credit card) or batched transactions, available across supported countries, allowing immediate processing and subsequent monthly charges.
- Publishable assets:
  - Blade views/components for admin dashboard (e.g., transaction lists, subscription management).
  - React components (e.g., payment form, status modal) exportable for Next.js use.
- Database migrations for storing transaction and subscription data.
- Configuration options for DPO credentials (company token, account type, test mode), default country (Zambia), and currency mappings.

### 2.2 Out-of-Scope

- Support for countries outside DPO's operational footprint (primarily Africa).
- Advanced fraud detection (rely on DPO's built-in features).
- Custom payment methods beyond DPO-supported ones.
- Full admin dashboard or Next.js app development (only provide assets/components).
- Integration with other payment gateways.
- Automatic currency conversion (handle via DPO or external services if needed).

## 3. Functional Requirements

### 3.1 Payment Processing

- **One-Time Payments (Per-Item):**

  - Initiate payment by creating a transaction token via DPO's createToken API, specifying country and currency.
  - Redirect user to DPO payment URL or handle direct payment.
  - Verify token post-payment to confirm status.
  - Support refunds and cancellations.

- **Recurring Payments (Monthly):**

  - Set up subscriptions using DPO's PaySubs endpoint or batch processing, configurable per country.
  - Parameters: Subscription start/end dates, frequency (monthly), amount, immediate processing option.
  - Handle immediate charge and subsequent monthly debits.
  - Manage subscription updates, pauses, and cancellations via API.

- **API Integration:**
  - Use XML-based requests for createToken, including levels for transaction details, currency, and country-specific elements.
  - Endpoints: Create Token, Verify Token, Update Token, Refund Token, Cancel Token, Get Balance (for multi-currency checks).
  - Secure handling of callbacks (back URL, redirect URL).
  - Country-specific configuration: Allow overriding default (Zambia) per transaction or globally.

### 3.2 Admin Dashboard Assets (Blade)

- Publishable Blade components/views:
  - Transaction list table (with filters for date, status, type, country, currency).
  - Subscription management view (view, edit, cancel subscriptions, with country-specific notes).
  - Payment reports (e.g., total revenue by country/currency, failed transactions).

### 3.3 Frontend Assets (React for Next.js)

- Exportable React components:
  - PaymentForm: Handles user input for payment details, includes country/currency selectors if multi-country enabled.
  - PaymentStatus: Displays success/failure modals or pages.
  - SubscriptionSignup: Form for starting monthly subscriptions, with recurring options.
- Components should be bundlable via Vite and importable in Next.js, with props for country and currency.

### 3.4 Database and Configuration

- Migrations for tables: transactions (id, token, status, amount, type, country, currency), subscriptions (id, start_date, end_date, frequency, country).
- Config file for DPO keys, test mode, URLs, default country ('ZM' for Zambia), currency mappings by country.

## 4. Non-Functional Requirements

### 4.1 Security

- PCI DSS compliance via DPO (no card storage in app).
- Use HTTPS for all API calls.
- Validate inputs to prevent injection attacks.
- Secure checksums for requests.

### 4.2 Performance

- Asynchronous handling for payment callbacks if needed.
- Efficient API calls with caching for non-sensitive data like country lists.

### 4.3 Usability

- Intuitive Blade and React components with responsive design and country-specific UI hints (e.g., available payment methods).
- Error handling with user-friendly messages, including country-specific errors.

### 4.4 Compatibility

- Laravel 12+.
- PHP 8.2+.
- Next.js 14+ for React assets.

## 5. Technical Requirements

### 5.1 Package Structure

- `src/`: Core classes (e.g., DpoService for API interactions, with country/currency handlers).
- `resources/views/`: Blade assets.
- `resources/js/components/`: React source files.
- `database/migrations/`: Table setups.
- `config/dpo.php`: Configuration, including supported countries array.
- Use ServiceProvider for publishing assets, routes, migrations.

### 5.2 Dependencies

- Composer: `laravel/framework:^12.0`.
- NPM: React, React-DOM for components.
- DPO SDK if available, or custom HTTP client (e.g., Guzzle).

### 5.3 Testing

- Unit tests for API interactions across multiple countries/currencies.
- Integration tests for payment flows (use mocks for DPO).

## 6. Assumptions and Dependencies

- Assumptions:
  - Users have a DPO merchant account with multi-country access if needed.
  - Recurring payments available in supported countries, primarily via cards or batching.
- Dependencies:
  - External: DPO API availability in configured countries.
  - Internal: Laravel app with authentication for admin features.

## 7. Risks and Mitigations

| Risk                                            | Mitigation                                                                  |
| ----------------------------------------------- | --------------------------------------------------------------------------- |
| Variations in payment methods by country        | Provide configurable options and documentation for country-specific setups. |
| API Changes in DPO                              | Monitor DPO docs and version the package.                                   |
| Recurring Support Limitations in Some Countries | Fall back to manual batching and test per country.                          |
| Asset Compatibility                             | Test React components in Next.js environment.                               |

This document is based on research from DPO documentation and existing integrations. Development should include prototyping to validate multi-country API flows.
