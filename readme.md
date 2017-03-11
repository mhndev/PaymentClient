# Usage
See the ManualTest class.
# Objects
1. Invoice
2. Wallet
3. GatewayTransaction
4. Wallet
# Exceptions
If something wrong happen, when calling an API function using the `PaymentClient` class,
an exception of type `PaymentException` will return.
Be prepared to appropriately catch the exception and take appropriate action.
# Tests
The tests are compatible with server's test seeds run by the following command:

    php artisan db:seed --class TestDatabaseSeeder

Feel free to overwrite the environment variables defined in `phpunit.xml` if you desire.
