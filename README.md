# Mak8Tech DPO Payment Package for Laravel 12

A comprehensive Laravel 12 package for integrating DPO (Direct Pay Online) payment gateway with support for African markets, focusing primarily on Zambia but with configurable support for 15+ African countries.

## Features

- ✅ **One-time Payments**: Process single transactions with card and mobile money options
- ✅ **Recurring Subscriptions**: Monthly, quarterly, and yearly subscription management
- ✅ **Multi-Country Support**: Pre-configured for 15+ African countries
- ✅ **Multiple Payment Methods**: Credit/debit cards and country-specific mobile money
- ✅ **Admin Dashboard**: Blade components for transaction and subscription management
- ✅ **React Components**: Ready-to-use components for Next.js integration
- ✅ **Comprehensive Logging**: Track all payment activities
- ✅ **Event System**: Hook into payment lifecycle events
- ✅ **Refunds & Cancellations**: Full payment management capabilities

## Supported Countries

| Country      | Currency | Mobile Money                              | Recurring |
| ------------ | -------- | ----------------------------------------- | --------- |
| Zambia       | ZMW      | Airtel Money, MTN MoMo, Zamtel Kwacha     | ✅        |
| Kenya        | KES      | M-Pesa, Airtel Money                      | ✅        |
| Tanzania     | TZS      | M-Pesa, Airtel Money, Tigo Pesa, HaloPesa | ✅        |
| Uganda       | UGX      | MTN MoMo, Airtel Money                    | ✅        |
| South Africa | ZAR      | Card payments only                        | ✅        |
| Rwanda       | RWF      | MTN MoMo, Airtel Money                    | ✅        |
| Nigeria      | NGN      | Card payments only                        | ✅        |
| Ghana        | GHS      | MTN MoMo, Vodafone Cash, AirtelTigo Money | ✅        |
| Zimbabwe     | USD      | EcoCash, OneMoney                         | ✅        |

| And 6+ more countries...

## Installation

### 1. Install via Composer

```bash
composer require mak8tech/dpo-payments
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="Mak8Tech\DpoPayments\DpoPaymentServiceProvider"
```

### 3. Configure Environment Variables

Add to your `.env` file:

```env
DPO_COMPANY_TOKEN=your-company-token
DPO_SERVICE_TYPE=3854
DPO_TEST_MODE=true
DPO_DEFAULT_COUNTRY=ZM
DPO_DEFAULT_CURRENCY=ZMW
```

### 4. Run Migrations

```bash
php artisan migrate
```

### 5. (Optional) Publish Assets

For Blade views:

```bash
php artisan vendor:publish --provider="Mak8Tech\DpoPayments\DpoPaymentServiceProvider" --tag="dpo-views"
```

For React components:

```bash
php artisan vendor:publish --provider="Mak8Tech\DpoPayments\DpoPaymentServiceProvider" --tag="dpo-react"
```

## Quick Start

### Creating a Payment

```php
use Mak8Tech\DpoPayments\Facades\DpoPayment;

// Create a one-time payment
$transaction = DpoPayment::createPayment([
    'amount' => 100.00,
    'currency' => 'ZMW',
    'country' => 'ZM',
    'customer_email' => 'customer@example.com',
    'customer_name' => 'John Doe',
    'description' => 'Purchase of Product X',
]);

// Redirect to payment URL
return redirect($transaction->payment_url);
```

### Creating a Subscription

```php
use Mak8Tech\DpoPayments\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

$subscription = $subscriptionService->createSubscription([
    'amount' => 50.00,
    'frequency' => 'monthly',
    'currency' => 'ZMW',
    'country' => 'ZM',
    'customer_email' => 'subscriber@example.com',
    'customer_name' => 'Jane Doe',
    'start_date' => now()->addDay(),
]);
```

### Handling Callbacks

The package automatically handles callbacks at `/dpo/callback`. You can listen to events:

```php
// In your EventServiceProvider
use Mak8Tech\DpoPayments\Events\PaymentSuccessful;
use Mak8Tech\DpoPayments\Events\PaymentFailed;

protected $listen = [
    PaymentSuccessful::class => [
        SendPaymentConfirmation::class,
        UpdateUserSubscription::class,
    ],
    PaymentFailed::class => [
        NotifyPaymentFailure::class,
    ],
];
```

## Blade Components

### Payment Form Component

```blade
<x-dpo-payment-form
    :amount="100.00"
    default-country="ZM"
    default-currency="ZMW"
/>
```

### Transaction Table Component

```blade
<x-dpo-transaction-table :transactions="$transactions" />
```

### Subscription Manager Component

```blade
<x-dpo-subscription-manager :subscriptions="$subscriptions" />
```

## React Components for Next.js

### Installation in Next.js

1. Copy the React components to your Next.js project:

```bash
cp -r vendor/mak8tech/dpo-payments/resources/js/components/* components/dpo/
```

2. Install required dependencies:

```bash
npm install lucide-react
```

### Usage in Next.js

```jsx
import { DpoPaymentForm, DpoPaymentStatus, DpoSubscriptionSignup } from '@/components/dpo';

// Payment Form
export default function PaymentPage() {
    return (
        <DpoPaymentForm
            amount={100.00}
            defaultCountry="ZM"
            onSuccess={(data) => {
                window.location.href = data.payment_url;
            }}
            apiEndpoint="/api/dpo/payments"
        />
    );
}

// Subscription Signup
export default function SubscriptionPage() {
    const plans = [
        { id: 1, name: 'Basic', amount: 50, currency: 'ZMW', features: ['Feature 1', 'Feature 2'] },
        { id: 2, name: 'Pro', amount: 100, currency: 'ZMW', features: ['All Basic', 'Feature 3'] },
    ];

    return (
        <DpoSubscriptionSignup
            plans={plans}
            defaultCountry="ZM"
            onSuccess={(data) => console.log('Subscription created:', data)}
        />
    );
}
```

## API Endpoints

### Payments

- `POST /api/dpo/payments` - Create payment
- `GET /api/dpo/payments/{reference}/status` - Get payment status
- `POST /api/dpo/payments/{reference}/refund` - Process refund
- `POST /api/dpo/payments/{reference}/cancel` - Cancel payment

### Subscriptions

- `POST /api/dpo/subscriptions` - Create subscription
- `GET /api/dpo/subscriptions/{reference}` - Get subscription details
- `PUT /api/dpo/subscriptions/{reference}` - Update subscription
- `POST /api/dpo/subscriptions/{reference}/cancel` - Cancel subscription
- `POST /api/dpo/subscriptions/{reference}/pause` - Pause subscription
- `POST /api/dpo/subscriptions/{reference}/resume` - Resume subscription

### Utility

- `GET /api/dpo/countries` - Get supported countries

## Admin Dashboard

Access the admin dashboard at `/dpo/admin` (requires authentication).

Features:

- Transaction management with filtering and search
- Subscription overview and management
- Revenue reports by country and currency
- Payment success/failure analytics
- Export capabilities

## Artisan Commands

### Check DPO Status

```bash
php artisan dpo:status
```

### Process Due Subscriptions

```bash
php artisan dpo:status --process-subscriptions
```

Set up a cron job for automatic processing:

```cron
0 0 * * * cd /path-to-your-app && php artisan dpo:status --process-subscriptions >> /dev/null 2>&1
```

## Testing

### Running Tests

```bash
composer test
```

### Test Mode

Enable test mode in your `.env`:

```env
DPO_TEST_MODE=true
```

This will use DPO's sandbox environment for testing.

## Advanced Usage

### Custom Payment Flow

```php
use Mak8Tech\DpoPayments\Services\DpoService;
use Mak8Tech\DpoPayments\Data\TransactionData;

$dpoService = app(DpoService::class);

$transactionData = new TransactionData(
    amount: 150.00,
    currency: 'ZMW',
    reference: 'CUSTOM-REF-123',
    description: 'Custom Payment',
    customerEmail: 'customer@example.com',
    customerName: 'John Doe',
    customerPhone: '+260977123456',
    customerCountry: 'ZM',
    services: [
        ['description' => 'Service 1', 'date' => now()->format('Y/m/d H:i')],
        ['description' => 'Service 2', 'date' => now()->format('Y/m/d H:i')],
    ],
    isRecurring: false
);

$tokenResponse = $dpoService->createToken($transactionData);

if ($tokenResponse->isSuccessful()) {
    // Process payment
}
```

### Multi-Currency Operations

```php
use Mak8Tech\DpoPayments\Services\CountryService;

$countryService = app(CountryService::class);

// Get all countries that support recurring payments
$recurringCountries = $countryService->getRecurringCountries();

// Get currency for a specific country
$currency = $countryService->getCurrencyForCountry('KE'); // Returns 'KES'

// Format currency amount
$formatted = $countryService->formatCurrency(1000.50, 'ZMW'); // Returns 'K 1,000.50'
```

### Webhook Security

The package automatically validates webhook callbacks. You can add additional IP whitelisting:

```php
// config/dpo.php
'allowed_ips' => [
    '41.77.245.104',  // DPO IP addresses
    '41.77.245.105',
],
```

## Troubleshooting

### Common Issues

1. **Token Creation Failed**

   - Verify your company token is correct
   - Check if test mode matches your DPO account type
   - Ensure amount is greater than 0

2. **Recurring Payments Not Working**

   - Confirm the country supports recurring payments
   - Verify PaySubs is enabled on your DPO account
   - Check subscription start date is in the future

3. **Mobile Money Not Appearing**
   - Ensure the selected country supports mobile money
   - Verify mobile money is enabled for your merchant account
   - Check that the currency matches the country

### Debug Mode

Enable detailed logging:

```php
// config/dpo.php
'logging' => [
    'enabled' => true,
    'channel' => 'dpo', // Create custom channel in config/logging.php
],
```

## Security

- Never expose your company token in frontend code
- Always validate webhook callbacks
- Use HTTPS in production
- Implement rate limiting on payment endpoints
- Regular security updates

## Support

For issues and questions:

- GitHub Issues: [github.com/mak8tech/dpo-payments](https://github.com/mak8tech/dpo-payments)
- Email: support@mak8tech.com
- Documentation: [docs.mak8tech.com/dpo-payments](https://docs.mak8tech.com/dpo-payments)

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

- Developed by Mak8Tech
- DPO Group for payment gateway services
- Laravel community for framework support

## Changelog

### v0.0.1 (2025-10-05)

- Initial release
- Support for 15+ African countries
- One-time and recurring payments
- Admin dashboard
- React components for Next.js
- Comprehensive test suite
