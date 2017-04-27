# Usage
See the ManualTest class.
# Objects
1. Invoice
2. Wallet
3. GatewayTransaction
4. Wallet
# Exceptions
If something wrong happen when calling an API function using the `PaymentClient` class,
an exception of type `PaymentException` will be raised.
Be prepared to catch the exception and take the appropriate actions.

# Tests
Before running test, `putenv` a valid `OAUT_TOKEN` in `tests/.env.php`.
The tests are compatible with server's test seeds run by the following command:

    php artisan db:seed --class TestDatabaseSeeder

Feel free to overwrite the environment variables defined in `phpunit.xml` if you desire.
