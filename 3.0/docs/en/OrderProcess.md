Order Process
=============

Follow the entire ordering process from start to finish...

Customer
--------

 - Visit product group / category page.
 - Browse, Filter, and Search catalogue.
 - Add desired products to cart
 - Update / remove products (quantities)
 - Enter shipping details
 - Choose shipping method
 - Enter promotion code(s)
 - Choose payment type
 - Place order
 - View order status
 - Receive goods
 - (Return goods)
 - (Cancel order)

Store Manager
-------------

 - Receive new order
 - Package
 - Send
 - (Cancel order)

Possibly notify the customer of changes at each stage

The Checkout Process
=============
Within the checkout page the process is as follows:

1. review + edit products
2. enter details that may influence the total cost (e.g. country, postal code)
2. review + edit extras
3. enter billing and shipping address
4. review total cost


---------------------
ORDER STATUS LOG:
---------------------

@description:
Data class that records events for an order like "Payment Checked", "Cheque Cleared", "Goods dispatched", etc...
Order Status Logs describe the history of each Order.
They are different from the OrderSteps which guide the Order through the ordering process.

Basically, an Order Status Log has a title and a piece of text.
In addition, we record if the customer was informed or that it is for internal use only.
We also record who made the log entry.

For your own e-commerce application you can add as many logs types as you need.
For this, you can extend the basic class: OrderStatusLog.

No logs are created "automatically", you will need to use the OrderSteps to create them.
In the code you can see a bunch of examples on how these Logs are written.

There is one special type of Order Status Log: OrderStatusLog_Submitted.
This one is basically hard-wired into the system.
That is, for every order and all e-commerce applications you need to have a moment where the customer commits / submits an order.
This order log basically stores the exact details of the order at the moment of submission.
This is really useful, because, based on the relational database aspect, it is very easy to loose parts of an exact order.
For example, you can change the title of a product, the amount of a tax, etc... and having a log of the exact details
at the moment the order was submitted can for that reason be very important.  Over time the e-commerce system will become
more transactional so that submitted details can not be overriden, but the log of the submitted details will be a good backup
for the time being.

Other logs that you may include:
- OrderStatusLog_Cancel: record about cancellation
- OrderStatusLog_Dispatch: record about dispatch
- OrderStatusLog_PaymentCheck: payment was made (manual check)
- your own log thingy!

HACK NOTE: we have included OrderStatusLog_Archived here as well.
The reason we do this is that the Order CMS fields will try to look for the next Log Class in the
Complex Table Field. This might be different in 3.0 and can be removed then.


---------------------
ORDER STEP:
---------------------


Defines the Order Status Options.	Basically OrderSteps guide the Order from inception to archiving.
Each project can have its own unique order steps - to match the requirements of the shop at hand.
The Order Step typically has (some) of the following functions:
a. a method move the order along
		- email the customer?
		- create a log entry?
b. describe what can be done to the order (edit, view, delete, etc...) and by whom
c. describe the status of the order
d. describe what needs to happen for the order to move along (via CMS fields)
		e.g. for the Order to move to the next step it needs to be paid in full

To make your own order steps, take an OrderStep from the classes listed below (one that is similar in purpose)
and customise it to your needs.

Next, to include the orderstep, you use one of the following methods:

OrderStep::set_order_steps_to_include (RESET ORDERSTEPS)
OR
OrderStep::add_order_steps_to_include (ADD ONE)

There are a lot of comments in the code below so there is no point in repeating that, but four KEY methods are:
- initStep: are we ready?
- doStep: do the step ...
- nextStep: what is next?
- addOrderStepFields: add CMS fields to the Order (e.g. a message stating what is next)
