<div id="EcommereTemplateTest" class="mainSection content-container noSidebar">

<h1>Ecommerce Template Test Page</h1>
<p>
	Welcome to the ecommerce template test.
	On this page you can see the most useful e-commerce template controls.
</p>

<h2>The Cart</h2>
<p>The Cart can be accessed from anywhere using:</p>
<pre>
&lt;% if Cart %&gt;
	&lt;% control Cart %&gt;
		//do cart stuff here
	&lt;% end_control %&gt;
&lt;% else %&gt;
	&lt;p&gt;No cart was found!&lt;/p&gt;
&lt;% end_if %&gt;
</pre>

<% if Cart %>
	<% control Cart %>

	<% end_control %>
<% else %>
<p>No cart was found!</p>
<% end_if %>


<h2>Ecommerce Configurations</h2>
<p>
	These configurations are set in a special DataObject in the database.
	They contain lots of settings, but here are the onest most useful for templates.
	These can be accessed from anywhere.
</p>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">Shop Address (&#36;EcomConfig.ShopPhysicalAddress):</th><td>$EcomConfig.ShopPhysicalAddress</td></tr>
	<tr><th scope="row" style="width: 60%;">Receipt Email (&#36;EcomConfig.ReceiptEmail):</th><td>$EcomConfig.ReceiptEmail</td></tr>
	<tr><th scope="row" style="width: 60%;">PostalCodeURL (&#36;EcomConfig.PostalCodeURL):</th><td>$EcomConfig.PostalCodeURL</td></tr>
	<tr><th scope="row" style="width: 60%;">Postal Code Label (&#36;EcomConfig.PostalCodeLabel):</th><td>$EcomConfig.PostalCodeLabel</td></tr>
	<tr><th scope="row" style="width: 60%;">Currency (&#36;EcomConfig.Currency - you can also use Currencies for a list):</th><td>$EcomConfig.Currency</td></tr>
	<tr><th scope="row" style="width: 60%;">Account Page Link (&#36;EcomConfig.AccountPageLink):</th><td>$EcomConfig.AccountPageLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Checkout Page Link (&#36;EcomConfig.CheckoutLink):</th><td>$EcomConfig.CheckoutLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Cart Page Link (&#36;EcomConfig.CartPageLink):</th><td>$EcomConfig.CartPageLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Order Confirmation Page Link (&#36;EcomConfig.OrderConfirmationPageLink):</th><td>$EcomConfig.OrderConfirmationPageLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Default Image Link (you can also use &#36;DefaultImage.SetWidth(100) and that sort of jazz) (&#36;EcomConfig.DefaultImageLink):</th><td>$EcomConfig.DefaultImageLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Current Customer Name (&#36;EcomConfig.Customer.Title, instead of Title, you can also use Email, FirstName, etc...):</th><td>$EcomConfig.Customer.Title</td></tr>
	<tr><th scope="row" style="width: 60%;">Is E-commerce Page? (This will be TRUE (YES) for the Product and Checkout type pages only.)</th><td><% if IsEcommercePage %>YES<% else %>NO<% end_if %></td></tr>
</table>


<h2>Ajax Definitions</h2>
<p>
	Ajax definitions are used to add IDs and Classes to tags so that when the cart update they can update at the same time.
	An example would be a div with an ID like "number_of_items".
	The content of this div would automatically change when the number of items in the cart is updated.
	NOTE: many of these ajax definitions are meant to be used within the <em>control</em> (context) of an order, order item, or order modifier.
</p>
<h4>Updating a page ...</h4>
<p>To update a page, you can use the following Javascript</p>
<pre>
 //do something
 ...
 ...
 // we are now ready to update the page:
 // we send a URL (e.g. one to remove a product from cart)
 EcomCart.getChanges(url, null, el);
</pre>
<p><i>EcomCart.getChanges</i> accepts the following parameters: </p>
<pre>
	/**
	 * get JSON data from server
	 * @param String url: URL for getting data (ajax request)
	 * @param Array params: parameters to add to ajax request
	 * @param Object loadingElement: the element that is being clicked or should be shown as "loading"
	 */
	getChanges: function(url, params, loadingElement) {
</pre>
<p>
	<i>EcomCart.getChanges</i> will automatically update the elements on your page as defined below.
	You can view a sample of the response here: <a href="shoppingcart/ajaxtest/" target="_blank">/shoppingcart/ajaxtest/</a> (you need to be logged in as Admin).
</p>
<h4>Available variables for your templates ...</h4>
<h5>without context (can be used at <i>root level</i> in any template)</h5>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.SideBarCartID</th><td>$AJAXDefinitions.SideBarCartID</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.SmallCartID</th><td>$AJAXDefinitions.SmallCartID</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.TinyCartClassName</th><td>$AJAXDefinitions.TinyCartClassName</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.TotalItemsClassName</th><td>$AJAXDefinitions.TotalItemsClassName</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.TotalItemsTimesQuantityClassName</th><td>$AJAXDefinitions.TotalItemsTimesQuantityClassName</td></tr>
</table>
<h5>without context, country and region related</h5>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.ExpectedCountryClassName</th><td>$AJAXDefinitions.ExpectedCountryClassName</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.CountryFieldID</th><td>$AJAXDefinitions.CountryFieldID</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.RegionFieldID</th><td>$AJAXDefinitions.RegionFieldID</td></tr>
</table>
<h5>within context of order, order item or order modifier</h5>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.TableID</th><td>$AJAXDefinitions.TableID</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.TableTotalID</th><td>$AJAXDefinitions.TableTotalID</td></tr>
</table>
<h5>within context of order</h5>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.TableMessageID</th><td>$AJAXDefinitions.TableMessageID</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.TableSubTotalID</th><td>$AJAXDefinitions.TableSubTotalID</td></tr>
</table>
<h5>within context of order item or order modifier</h5>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.TableTitleID</th><td>$AJAXDefinitions.TableTitleID</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.CartTitleID</th><td>$AJAXDefinitions.CartTitleID</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.TableSubTitleID</th><td>$AJAXDefinitions.TableSubTitleID</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.CartSubTitleID</th><td>$AJAXDefinitions.CartSubTitleID</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.QuantityFieldName</th><td>$AJAXDefinitions.QuantityFieldName</td></tr>
</table>
<h5>within context of buyable</h5>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">&#36;AJAXDefinitions.UniqueIdentifier</th><td>$AJAXDefinitions.UniqueIdentifier</td></tr>
</table>

<h4>Ajax Shopping Cart</h4>
<p>
	To view an ajax response for a <a href="$SimpleCartLinkAjax">shopping cart</a>
	- this can be used to add to an html node using an ajax call.
</p>

<h2>Product</h2>
<p>The following product has been randomly selected product for this exercise: <strong><a href="$RandomProduct.Link">$RandomProduct.MenuTitle</a></strong></p>
<p>You can <a href="$Link">reload</a> this page to view another product.</p>
<% control RandomProduct %>
<h4>Image Controllers</h4>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">&#36;Image.Link</th><td>$Image.SetWidth(100) $Image.Link</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;BestAvailableImage.Link</th><td>$BestAvailableImage.SetWidth(100) $BestAvailableImage.Link</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;DefaultImage.Link</th><td>$DefaultImage.SetWidth(100) $DefaultImage.Link</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;DummyImage.Link</th><td>$DummyImage.SetWidth(100) $DummyImage.Link</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;DefaultImageLink.Link</th><td>$DefaultImageLink</td></tr>
</table>

<h4>Links</h4>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">&#36;AddVariationsLink</th><td><a href="$AddVariationsLink">$AddVariationsLink</a></td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;AddLink</th><td><a href="$AddLink">$AddLink</a></td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;IncrementLink</th><td><a href="$IncrementLink">$IncrementLink</a></td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;DecrementLink</th><td><a href="$DecrementLink">$DecrementLink</a></td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;RemoveLink</th><td><a href="$RemoveLink">$RemoveLink</a></td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;RemoveAllLink</th><td><a href="$RemoveAllLink">$RemoveAllLink</a></td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;RemoveAllAndEditLink</th><td><a href="$RemoveAllLink">$RemoveAllAndEditLink</a></td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;SetSpecificQuantityItemLink - adding eleven here</th><td><a href="$SetSpecificQuantityItemLink(11)">$SetSpecificQuantityItemLink(11)</a></td></tr>
</table>

<h4>Status and Price</h4>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">&#36;IsInCart</th><td><% if IsInCart %> YES <% else %>NO<% end_if %></td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;Price</th><td>$Price</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;Price.Nice</th><td>$Price.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;CalculatedPrice.Nice</th><td>$CalculatedPrice.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;DisplayPrice.Nice</th><td>$DisplayPrice.Nice</td></tr>
</table>

<h4>Actions</h4>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">&#36;EcomQuantityField</th><td>$EcomQuantityField</td></tr>
</table>

<h4>Only Available on Product Page (through controller)</h4>
<table style="width: 95%;">
	<tr><th scope="row" style="width: 60%;">&#36;AddProductForm</th><td>$AddProductForm</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;PreviousProduct</th><td>$PreviousProduct</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;NextProduct</th><td>$NextProduct</td></tr>
</table>

<% end_control %>

</div>

