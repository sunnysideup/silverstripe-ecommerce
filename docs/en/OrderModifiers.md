# Order Modifiers


## What are modifiers?

Modifiers provide an abstract (and practical) way to add additional (non-product) lines to your order, for things things like:
+ bonus products,
+ tax,
+ delivery charges,
+ staff discounts,
+ etc...

A cart holds two basic items: Order Items and Order Modifiers.
The former are the actual products and services.
The latter are items that add / change / deduct / do whatever to the order.
One of the main differences between Order Items and Modifiers is that an Order Item has a quantity associated to it.
Another way to look at modifiers is everything between <i>sub-total</i> and <i>grand-total</i>.


## What can they do?

What modifiers offer is the ability to:
+ to deduct / add charges / (and have no charges)
+ to access product order items to work out, for example, the total weight of the order.
+ to access the modifier total (the difference between sub-total and grand-total) to work out things like tax (some of the modifiers may have their own tax component)
++ excluding the current modifier
++ up to the current modifier
+ be saved in the database and be updated everytime an order is updated
+ be hidden / show depending on customer choices (e.g. if the customer chooses to pick up then the delivery charge should be set to zero and hidden)
+ be grouped and group sorted
+ add a form to the bottom of the editable order form (or elsewhere by changing the templates)


## How to add a modifier

+ create a new class "MyModifierOne" that extends the OrderModifier class
+ use Order::set_modifiers(array("MyModifierOne", "MyModifierTwo")); in your _config file to "include"
  the modifier in all carts.

## Extending the modifier class

The best way to write a modifier is to copy the modifier class itself and, as per usual
+ delete any methods that should stay the same as the parent class
+ keep + adjust any methods that are different
+ add any other methods and (static) variables that you may need to use.

There is also a special module that contains an example modifier that you can use to see how you can use modifiers.
@see: https://silverstripe-ecommerce.googlecode.com/svn/modules/ecommerce_modifier_example/trunk

There are lots of comments in the modifier code that will help you.

You may also want to review two other classes:
+ OrderAttribute - the parent class of OrderModifier (you may want to overload some of the methods)
+ Other OrderModifier sub-classes
