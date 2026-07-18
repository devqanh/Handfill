# Webhooks Documentation

Guide to setting up and using webhook notifications for wallet events.

## Overview

Webhooks allow you to receive real-time HTTP notifications when wallet events occur. This is useful for:
- Integrating with external systems
- Triggering custom workflows
- Syncing data with third-party services
- Sending custom notifications

## Configuration

### Enable Webhooks

1. Navigate to **Admin → E-Wallet → Settings**
2. Scroll to **Webhook Settings**
3. Check **Enable Webhooks**
4. Configure webhook URLs for each event type
5. Click **Save Settings**

### Webhook URLs

Configure URLs for the following events:

| Event | Setting Key | Description |
|-------|-------------|-------------|
| Top-up Created | `topup_created_url` | Customer initiates a top-up |
| Top-up Completed | `topup_completed_url` | Top-up payment successful |
| Top-up Failed | `topup_failed_url` | Top-up payment failed |
| Top-up Cancelled | `topup_cancelled_url` | Top-up cancelled |

## Webhook Payloads

### Top-up Created

Triggered when a customer initiates a new top-up request.

```json
{
  "event": "topup.created",
  "timestamp": "2025-12-22T12:00:00Z",
  "data": {
    "topup": {
      "id": 123,
      "code": "TOPUP-2025-001",
      "customer_id": 456,
      "amount": 5000,
      "currency_code": "USD",
      "payment_amount": 5000,
      "payment_currency": "USD",
      "exchange_rate": 1.0,
      "status": "pending",
      "created_at": "2025-12-22T12:00:00Z"
    },
    "customer": {
      "id": 456,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

### Top-up Completed

Triggered when a top-up payment is successfully completed and wallet is credited.

```json
{
  "event": "topup.completed",
  "timestamp": "2025-12-22T12:05:00Z",
  "data": {
    "topup": {
      "id": 123,
      "code": "TOPUP-2025-001",
      "customer_id": 456,
      "amount": 5000,
      "currency_code": "USD",
      "payment_amount": 5000,
      "payment_currency": "USD",
      "exchange_rate": 1.0,
      "status": "completed",
      "payment_id": "pi_abc123",
      "created_at": "2025-12-22T12:00:00Z",
      "updated_at": "2025-12-22T12:05:00Z"
    },
    "customer": {
      "id": 456,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "wallet": {
      "id": 789,
      "balance": 15000,
      "currency_code": "USD"
    },
    "transaction": {
      "id": 1011,
      "type": "top_up",
      "amount": 5000,
      "balance_before": 10000,
      "balance_after": 15000,
      "created_at": "2025-12-22T12:05:00Z"
    }
  }
}
```

### Top-up Failed

Triggered when a top-up payment fails.

```json
{
  "event": "topup.failed",
  "timestamp": "2025-12-22T12:05:00Z",
  "data": {
    "topup": {
      "id": 123,
      "code": "TOPUP-2025-001",
      "customer_id": 456,
      "amount": 5000,
      "currency_code": "USD",
      "payment_amount": 5000,
      "payment_currency": "USD",
      "exchange_rate": 1.0,
      "status": "failed",
      "payment_id": "pi_abc123",
      "created_at": "2025-12-22T12:00:00Z",
      "updated_at": "2025-12-22T12:05:00Z"
    },
    "customer": {
      "id": 456,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "error": {
      "code": "card_declined",
      "message": "Your card was declined"
    }
  }
}
```

### Top-up Cancelled

Triggered when a top-up is cancelled by admin or customer.

```json
{
  "event": "topup.cancelled",
  "timestamp": "2025-12-22T12:10:00Z",
  "data": {
    "topup": {
      "id": 123,
      "code": "TOPUP-2025-001",
      "customer_id": 456,
      "amount": 5000,
      "currency_code": "USD",
      "payment_amount": 5000,
      "payment_currency": "USD",
      "exchange_rate": 1.0,
      "status": "cancelled",
      "created_at": "2025-12-22T12:00:00Z",
      "updated_at": "2025-12-22T12:10:00Z"
    },
    "customer": {
      "id": 456,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "cancelled_by": {
      "type": "admin",
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

## Webhook Headers

All webhook requests include the following headers:

```
Content-Type: application/json
User-Agent: E-Wallet-Webhook/1.0
X-Webhook-Event: topup.created
X-Webhook-Timestamp: 2025-12-22T12:00:00Z
```

## Receiving Webhooks

### Endpoint Requirements

Your webhook endpoint should:
- Accept POST requests
- Return HTTP 200-299 status code for success
- Respond within 10 seconds
- Handle duplicate deliveries (idempotency)

### Example Endpoint (Laravel)

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleWalletWebhook(Request $request)
    {
        // Get event type
        $event = $request->input('event');
        
        // Log webhook
        Log::info('Wallet webhook received', [
            'event' => $event,
            'data' => $request->all(),
        ]);
        
        // Process based on event type
        switch ($event) {
            case 'topup.created':
                $this->handleTopUpCreated($request->input('data'));
                break;
                
            case 'topup.completed':
                $this->handleTopUpCompleted($request->input('data'));
                break;
                
            case 'topup.failed':
                $this->handleTopUpFailed($request->input('data'));
                break;
                
            case 'topup.cancelled':
                $this->handleTopUpCancelled($request->input('data'));
                break;
        }
        
        // Return success
        return response()->json(['success' => true]);
    }
    
    protected function handleTopUpCreated($data)
    {
        // Send notification to admin
        // Update external system
        // Log for analytics
    }
    
    protected function handleTopUpCompleted($data)
    {
        // Send confirmation email
        // Update loyalty points
        // Trigger promotional offers
    }
    
    protected function handleTopUpFailed($data)
    {
        // Send failure notification
        // Log for fraud detection
        // Suggest alternative payment methods
    }
    
    protected function handleTopUpCancelled($data)
    {
        // Clean up pending processes
        // Send cancellation notification
    }
}
```

### Route Configuration

```php
// routes/web.php
Route::post('/webhooks/wallet', [WebhookController::class, 'handleWalletWebhook']);
```

## Testing Webhooks

### Test Button

Use the built-in test button in the settings page:

1. Go to **Admin → E-Wallet → Settings**
2. Scroll to **Webhook Settings**
3. Enter a webhook URL
4. Click **Send Test Webhook**
5. Check the response status

### Manual Testing

Send a test webhook manually:

```php
use Botble\EWallet\Services\WebhookService;

$webhookService = app(WebhookService::class);

$payload = [
    'event' => 'topup.created',
    'timestamp' => now()->toIso8601String(),
    'data' => [
        'topup' => [
            'id' => 123,
            'code' => 'TEST-001',
            'amount' => 1000,
        ],
    ],
];

$response = $webhookService->send('https://your-site.com/webhook', $payload);
```

### Testing Tools

Use these tools to test webhooks during development:

- **[Webhook.site](https://webhook.site)**: Get a temporary URL to inspect webhooks
- **[RequestBin](https://requestbin.com)**: Capture and inspect HTTP requests
- **[ngrok](https://ngrok.com)**: Expose local server to the internet

## Security Best Practices

### 1. Use HTTPS

Always use HTTPS URLs for webhooks to encrypt data in transit.

```
✅ https://your-site.com/webhooks/wallet
❌ http://your-site.com/webhooks/wallet
```

### 2. Verify Webhook Source

Verify that webhooks are coming from your application:

```php
// Option 1: IP Whitelist
$allowedIps = ['192.168.1.1', '10.0.0.1'];
if (!in_array($request->ip(), $allowedIps)) {
    abort(403, 'Unauthorized');
}

// Option 2: Shared Secret (if implemented)
$signature = $request->header('X-Webhook-Signature');
$expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);
if (!hash_equals($expectedSignature, $signature)) {
    abort(403, 'Invalid signature');
}
```

### 3. Handle Idempotency

Webhooks may be delivered multiple times. Use idempotency to prevent duplicate processing:

```php
$eventId = $request->input('data.topup.id') . '_' . $request->input('event');

if (Cache::has("webhook_processed_{$eventId}")) {
    return response()->json(['success' => true, 'message' => 'Already processed']);
}

// Process webhook
// ...

// Mark as processed
Cache::put("webhook_processed_{$eventId}", true, now()->addDays(7));
```

### 4. Validate Payload

Validate the webhook payload structure:

```php
$validator = Validator::make($request->all(), [
    'event' => 'required|string',
    'timestamp' => 'required|date',
    'data' => 'required|array',
    'data.topup' => 'required|array',
    'data.topup.id' => 'required|integer',
]);

if ($validator->fails()) {
    return response()->json(['error' => 'Invalid payload'], 400);
}
```

### 5. Process Asynchronously

Process webhooks in background jobs to avoid timeouts:

```php
public function handleWalletWebhook(Request $request)
{
    // Validate and queue
    ProcessWalletWebhook::dispatch($request->all());
    
    // Return immediately
    return response()->json(['success' => true]);
}
```

## Retry Logic

If your endpoint returns an error (non-2xx status code):
- The webhook will NOT be automatically retried
- You should implement your own retry logic if needed
- Log all webhook failures for manual review

## Monitoring

### Log Webhooks

Log all webhook deliveries:

```php
Log::channel('webhooks')->info('Webhook sent', [
    'url' => $url,
    'event' => $payload['event'],
    'status_code' => $response->status(),
    'response_time' => $responseTime,
]);
```

### Monitor Failures

Track webhook failures:

```php
if (!$response->successful()) {
    Log::channel('webhooks')->error('Webhook failed', [
        'url' => $url,
        'event' => $payload['event'],
        'status_code' => $response->status(),
        'error' => $response->body(),
    ]);
    
    // Send alert to admin
    // Increment failure counter
}
```

## Troubleshooting

### Webhooks Not Received

1. **Check URL is correct**: Verify the webhook URL in settings
2. **Check firewall**: Ensure your server allows incoming requests
3. **Check SSL certificate**: HTTPS URLs require valid SSL
4. **Check response time**: Endpoint must respond within 10 seconds
5. **Check logs**: Review application logs for errors

### Duplicate Webhooks

Implement idempotency as shown in the security section.

### Webhook Timeouts

- Process webhooks asynchronously
- Return 200 immediately
- Handle actual processing in background job

## Example Use Cases

### 1. Send Custom Email Notifications

```php
protected function handleTopUpCompleted($data)
{
    $customer = Customer::find($data['customer']['id']);
    $amount = format_price($data['topup']['amount'] / 100);
    
    Mail::to($customer->email)->send(
        new TopUpSuccessEmail($customer, $amount)
    );
}
```

### 2. Update External CRM

```php
protected function handleTopUpCompleted($data)
{
    Http::post('https://crm.example.com/api/wallet-topup', [
        'customer_id' => $data['customer']['id'],
        'amount' => $data['topup']['amount'],
        'timestamp' => $data['timestamp'],
    ]);
}
```

### 3. Trigger Loyalty Rewards

```php
protected function handleTopUpCompleted($data)
{
    $amount = $data['topup']['amount'];
    $customerId = $data['customer']['id'];
    
    // Award 1 point per dollar
    $points = floor($amount / 100);
    
    LoyaltyService::awardPoints($customerId, $points, 'wallet_topup');
}
```

### 4. Fraud Detection

```php
protected function handleTopUpCreated($data)
{
    $customerId = $data['customer']['id'];
    $amount = $data['topup']['amount'];
    
    // Check for suspicious activity
    $recentTopUps = WalletTopUp::where('customer_id', $customerId)
        ->where('created_at', '>', now()->subHour())
        ->count();
    
    if ($recentTopUps > 5 || $amount > 100000) {
        // Flag for review
        FraudAlert::create([
            'customer_id' => $customerId,
            'type' => 'suspicious_topup',
            'data' => $data,
        ]);
        
        // Notify admin
        Mail::to('admin@example.com')->send(new FraudAlertEmail($data));
    }
}
```

## Next Steps

- [Troubleshooting Guide](troubleshooting.md)
- [API Reference](api.md)
