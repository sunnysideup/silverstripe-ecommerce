# Payment

The eCommerce module integrates with the Payments module. Originally the payment module was part of the eCommerce module, but for good reason it has been split out.

[Payment module on SilverStripe.org](http://silverstripe.org/payment-module)
The payment code is now [hosted on github](https://github.com/silverstripe-labs/silverstripe-payment).

## Available payment types

See code source for more information - new payment gateways are added all the time.
[Types of payment](https://github.com/downloads/burnbright/silverstripe-payment/PaymentsSequenceDiagram.pdf)

## Creating your own payment type

Create a new class that extends Payment.

This class must implement the process function.

If necessary, add a controller to handle incoming gateway redirects/callbacks.

Here's a template:
http://code.google.com/p/silverstripe-ecommerce/downloads/detail?name=MyPayment.php
