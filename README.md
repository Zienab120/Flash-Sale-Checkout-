1. Assumptions & Invariants

The system enforces the following:

Product Stock:
Stock cannot go below zero.
Stock is reserved using “holds” before creating orders.
On failed payment, the reserved stock is automatically released.

Orders:
Orders have statuses: prepayment → paid or cancelled.

Payment Webhooks:
Webhooks are idempotent: repeated requests with the same idempotency_key are ignored.
Webhooks arriving before order creation are accepted and stored for later processing.

Idempotency:
Each webhook key is stored in webhook_idempotency_keys.
Reprocessing a key does not change the order status or stock.

2. How to run the app:
git clone <repo-url>
cd <project-folder>
composer install
cp .env.example .env
cp .env. .env.testing

To run queue and schedule:
php artisan queue:work 
php artisan schedule:work

and to run the job separately:
php artisan holds:expire

6. Logging

All logs are isolated by module for clarity:

Product:	storage/logs/product.log
Order:	storage/logs/order.log
Hold:	storage/logs/holding.log
Payment:	storage/logs/payment.log

And an isolated log for testing: storage/logs/testing.log
