# Customising the e-commerce module

It is most likely that you as a website developer/designer will want to customise the e-commerce module to look and work as your client would like.  Fortunately it is very easy to do so with the SilverStripe e-commerce module.

There are a number of configuration options to be set in your /mysite/_config.php file. See [Configuration](Configuration)  For this you can copy the options from the ecomerce/_config.php file (it is better to edit the mysite _config file then the original e-commerce one so that you can update the e-commerce module without having to "re-apply" the configurations.)

## Theme-ing / Templates

The easiest way to create your own look-and-feel for e-commerce is to create your own css files. The following css files can be themed:
 * ProductGroup.css  - used on the Product Group Pages
 * Products.css - used on the Product Pages but also on the ProductGroup pages.
 * Cart.css - used for CartPage, CheckoutPage and OrderConfirmationPage.css AND anywhere else the cart is being displayed!
 * AccountPage.css - used on the AccountPage.css
 * Order.css - used where an uncompleted order is shown
 * Order_Print.css - print version of Order.css
 * Order_PackingSlip.css - print version of Order.css
 * [OrderModiferName].css - for any OrderModifier that uses a form
 * Cart.css - anywhere the cart appears
 * OrderReport.css - used where a completed order is shown
 * OrderReport_Print.css - print only version for OrderReport.css
 * OrderStepField.css - specifically for the OrderStepField

An easy way to do this is to copy a file like (for example) /e-commerce/css/Products.css to /e-commerce/nameofmytheme_e-commerce/css/Products.css. Firstly, you need to work out the name of your theme, then you add a folder called themes/ in the root directory. Next, you work out the name of your theme (e.g. main or default or mytheme or nameofclientgoeshere). Now you can create a folder called /themes/nameofmytheme_e-commerce.  The "_e-commerce" addition is important - because it allows you to keep the e-commerce theme-ing separate from the general theme of the website.  Now, you can add the css folder and the copy of the css file. To create your own styles, you will edit this version of the Products.css file.

Secondly, all of the templates in the e-commerce/templates folder you can customise as well to create your desired look for the e-commerce module. To make your customisations you need to create your own corresponding version of the template/partial-template with the same name in your mysite/templates (e.g. /mysite/templates/Layout/CheckoutPage.ss) folder or the themes folder (e.g. /themes/nameofmytheme_e-commerce/templates/Layout/CheckoutPage.ss)

More about developing themes [here](http://doc.silverstripe.org/sapphire/en/topics/theme-development).



## Sub modules

Sub-modules provide additional functionality that remains separate from the main e-commerce module to keep the core code as minimal as possible.

When using a module, please make sure that it is intended to work with your version of e-commerce.

Modules can be found [here](https://code.google.com/p/silverstripe-e-commerce/source/browse/#svn%2Fmodules).

If you are interested in developing your own sub-module, see [contributing](Contributing) docs.



## Common Customisations

Here a number of common customisations to the e-commerce module.

### Order form fields

The OrderForm is what you at the checkout.

* Create an Extension of OrderForm eg: MyOrderFormExtension
* Create a function in your extension called either: updateValidator, updateFields, or updateForm

eg:

	function updateFields(&$fields){
		$fields->insertBefore(new TextField('State'),'Country');
	}

If Order does not contain 'State', you'll need to extend Order to add it to the db fields. The OrderForm data also gets saved to Member, if one exists, so you would need to add State to that also.

#### Custom validation

...

### Modify country dropdown field

...


Also see [Development](Development).
