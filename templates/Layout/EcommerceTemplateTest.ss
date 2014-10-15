<div id="EcommerceTaskTemplateTest" class="mainSection content-container noSidebar typography">

<h1>Ecommerce Template Test Page</h1>
<p>
	Welcome to the ecommerce template test.
	On this page you can see the most useful e-commerce template controls.
</p>

<h2>The Cart</h2>
<p>The Cart can be accessed from anywhere using:</p>
<pre>
&lt;% if Cart %&gt;
	&lt;% with Cart %&gt;
		//do cart stuff here
	&lt;% end_with %&gt;
&lt;% else %&gt;
	&lt;p&gt;No cart was found!&lt;/p&gt;
&lt;% end_if %&gt;
</pre>

<% if Cart %>
	<% with Cart %>

	<% end_with %>
<% else %>
<p>No cart was found!</p>
<% end_if %>


<h2>Cart Info</h2>
<p>
	Useful if you would like to display some information about the current cart ...
</p>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 60%;">Link to display order<br />&#36;Cart.Link</th><td>$Cart.Link</td></tr>
	<tr><th scope="row" style="width: 60%;">Number or products in cart<br />&#36;Cart.TotalItems</th><td>$Cart.TotalItems</td></tr>
	<tr><th scope="row" style="width: 60%;">Number of items in cart <br />&#36;Cart.TotalItemsTimesQuantity</th><td>$Cart.TotalItemsTimesQuantity</td></tr>
	<tr><th scope="row" style="width: 60%;">More than one item in cart?<br />&#36;Cart.MoreThanOneItemInCart</th><td><% if Cart.MoreThanOneItemInCart %>YES<% else %>NO<% end_if %></td></tr>
	<tr><th scope="row" style="width: 60%;">Link for unsubmitted order load it as current order<br />&#36;Cart.RetrieveLink</th><td>$Cart.RetrieveLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Sub-Total without currency<br />&#36;Cart.SubTotalAsCurrencyObject.Nice</th><td>$Cart.SubTotalAsCurrencyObject.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">Sub-Total with currency<br />&#36;Cart.SubTotalAsMoney.Nice</th><td>$Cart.SubTotalAsMoney.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">Extras with currency<br />&#36;Cart.ModifiersSubTotalAsCurrencyObject.Nice</th><td>$Cart.ModifiersSubTotalAsCurrencyObject.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">Total without currency<br />&#36;Cart.TotalAsCurrencyObject.Nice</th><td>$Cart.TotalAsCurrencyObject.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">Total with currency<br />&#36;Cart.TotalAsMoney.Nice</th><td>$Cart.TotalAsMoney.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">Total Paid without currency<br />&#36;Cart.TotalPaidAsCurrencyObject.Nice</th><td>$Cart.TotalPaidAsCurrencyObject.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">Total Paid with currency<br />&#36;Cart.TotalPaidAsMoney.Nice</th><td>$Cart.TotalPaidAsMoney.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">Total Oustanding without currency<br />&#36;Cart.TotalOutstandingAsCurrencyObject.Nice</th><td>$Cart.TotalOutstandingAsCurrencyObject.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">Total Oustanding with currency<br />&#36;Cart.TotalOutstandingAsMoney.Nice</th><td>$Cart.TotalOutstandingAsMoney.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">Country for current order<br />&#36;Cart.Country / &#36;Cart.FullNameCountry </th><td>$Cart.Country / $Cart.FullNameCountry</td></tr>


</table>

<h2>Submitted Order Info</h2>
<p>
	Useful if you would like to display some information about the a Submitted Order ...
	For this example, you will need to create a method SubmittedOrder, accessible to your controller.
	The method should return the submitted order you want to use for displaying the information below.
</p>
<% with SubmittedOrder %>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 60%;">Order Title<br />&#36;SubmittedOrder.Title</th><td>$Title</td></tr>
	<tr><th scope="row" style="width: 60%;">Email Address for communication with customer<br />&#36;SubmittedOrder.OrderEmail</th><td>$OrderEmail</td></tr>
	<tr><th scope="row" style="width: 60%;">Link for submitted order that can be used in Emails<br />&#36;SubmittedOrder.EmailLink</th><td>$EmailLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Link for submitted order for printing<br />&#36;SubmittedOrder.PrintLink</th><td>$PrintLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Link for submitted order for packing slip<br />&#36;SubmittedOrder.PackingSlipLink</th><td>$PackingSlipLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Link for submitted order to delete it<br />&#36;SubmittedOrder.DeleteLink</th><td>$DeleteLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Link for submitted order to copy it<br />&#36;SubmittedOrder.CopyOrderLink</th><td>$CopyOrderLink</td></tr>
</table>
<% end_with %>

<h2>Ecommerce Configurations</h2>
<p>
	These configurations are set in a special DataObject in the database.
	They contain lots of settings, but here are the onest most useful for templates.
	These can be accessed from anywhere.
</p>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 60%;">Shop Address <br />&#36;EcomConfig.ShopPhysicalAddress</th><td>$EcomConfig.ShopPhysicalAddress</td></tr>
	<tr><th scope="row" style="width: 60%;">Receipt Email <br />&#36;EcomConfig.ReceiptEmail</th><td>$EcomConfig.ReceiptEmail</td></tr>
	<tr><th scope="row" style="width: 60%;">PostalCodeURL <br />&#36;EcomConfig.PostalCodeURL</th><td>$EcomConfig.PostalCodeURL</td></tr>
	<tr><th scope="row" style="width: 60%;">Postal Code Label <br />&#36;EcomConfig.PostalCodeLabel</th><td>$EcomConfig.PostalCodeLabel</td></tr>
	<tr><th scope="row" style="width: 60%;">Currency <br />&#36;EcomConfig.Currency - you can also use Currencies for a list</th><td>$EcomConfig.Currency</td></tr>
	<tr><th scope="row" style="width: 60%;">Account Page Link <br />&#36;EcomConfig.AccountPageLink</th><td>$EcomConfig.AccountPageLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Checkout Page Link <br />&#36;EcomConfig.CheckoutLink</th><td>$EcomConfig.CheckoutLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Cart Page Link <br />&#36;EcomConfig.CartPageLink</th><td>$EcomConfig.CartPageLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Order Confirmation Page Link <br />&#36;EcomConfig.OrderConfirmationPageLink</th><td>$EcomConfig.OrderConfirmationPageLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Default Image Link <br />&#36;EcomConfig.DefaultImageLink</th><td>$EcomConfig.DefaultImageLink</td></tr>
	<tr><th scope="row" style="width: 60%;">Default Image @ Width = 100px <br />&#36;EcomConfig.DefaultImage.SetWidth(100)</th><td>$EcomConfig.DefaultImage.SetWidth(100)</td></tr>
	<tr><th scope="row" style="width: 60%;">Current Customer Name <br />&#36;EcomConfig.Customer.Title, instead of Title, you can also use Email, FirstName, etc...</th><td>$EcomConfig.Customer.Title</td></tr>
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
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row">&#36;AJAXDefinitions.SideBarCartID</th>
			<td>$AJAXDefinitions.SideBarCartID<br /><i>$AJAXDefinitions.Define(SideBarCartID)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.SmallCartID</th>
			<td>$AJAXDefinitions.SmallCartID<br /><i>$AJAXDefinitions.Define(SmallCartID)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.TinyCartClassName</th>
			<td>$AJAXDefinitions.TinyCartClassName<br /><i>$AJAXDefinitions.Define(TinyCartClassName)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.TotalItemsClassName</th>
			<td>$AJAXDefinitions.TotalItemsClassName<br /><i>$AJAXDefinitions.Define(TotalItemsClassName)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.TotalItemsTimesQuantityClassName</th>
			<td>$AJAXDefinitions.TotalItemsTimesQuantityClassName<br /><i>$AJAXDefinitions.Define(TotalItemsTimesQuantityClassName)</i></td>
		</tr>
	</table>

	<h5>without context, ajaxified product list</h5>
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row">&#36;AJAXDefinitions.HiddenPageTitleID</th>
			<td>$AJAXDefinitions.HiddenPageTitleID<br /><i>$AJAXDefinitions.Define(HiddenPageTitleID)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.ProductListHolderID</th>
			<td>$AJAXDefinitions.ProductListHolderID<br /><i>$AJAXDefinitions.Define(ProductListHolderID)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.ProductListAjaxifiedLinkClassName</th>
			<td>$AJAXDefinitions.ProductListAjaxifiedLinkClassName<br /><i>$AJAXDefinitions.Define(ProductListAjaxifiedLinkClassName)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.ProductListItemClassName</th>
			<td>$AJAXDefinitions.ProductListItemClassName<br /><i>$AJAXDefinitions.Define(ProductListItemClassName)</i></td>
		</tr>
	</table>

	<h5>without context, products in (or not in cart) cart</h5>
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>

			<th scope="row">&#36;AJAXDefinitions.ProductListItemInCartClassName</th>
			<td>$AJAXDefinitions.ProductListItemInCartClassName<br /><i>$AJAXDefinitions.Define(ProductListItemInCartClassName)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.ProductListItemNotInCartClassName</th>
			<td>$AJAXDefinitions.ProductListItemNotInCartClassName<br /><i>$AJAXDefinitions.Define(ProductListItemNotInCartClassName)</i></td>
		</tr>
	</table>

	<h5>without context, country and region related</h5>
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row">&#36;AJAXDefinitions.ExpectedCountryClassName</th>
			<td>$AJAXDefinitions.ExpectedCountryClassName<br /><i>$AJAXDefinitions.Define(ProductListItemInCartClassName)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.CountryFieldID</th>
			<td>$AJAXDefinitions.CountryFieldID<br /><i>$AJAXDefinitions.Define(ProductListItemInCartClassName)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.RegionFieldID</th>
			<td>$AJAXDefinitions.RegionFieldID<br /><i>$AJAXDefinitions.Define(RegionFieldID)</i></td>
		</tr>
	</table>

	<h5>within context of order, order item or order modifier</h5>
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row">&#36;AJAXDefinitions.TableID</th>
			<td>$AJAXDefinitions.TableID<br /><i>$AJAXDefinitions.Define(TableID)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.TableTotalID</th>
			<td>$AJAXDefinitions.TableTotalID<br /><i>$AJAXDefinitions.Define(TableTotalID)</i></td>
		</tr>
	</table>

	<h5>within context of order</h5>
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row">&#36;AJAXDefinitions.TableMessageID</th>
			<td>$AJAXDefinitions.TableMessageID<br /><i>$AJAXDefinitions.Define(TableMessageID)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.TableSubTotalID</th>
			<td>$AJAXDefinitions.TableSubTotalID<br /><i>$AJAXDefinitions.Define(TableSubTotalID)</i></td>
		</tr>
	</table>
	<h5>within context of order item or order modifier</h5>

	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row">&#36;AJAXDefinitions.TableTitleID</th>
			<td>$AJAXDefinitions.TableTitleID<br /><i>$AJAXDefinitions.Define(TableTitleID)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.CartTitleID</th>
			<td>$AJAXDefinitions.CartTitleID<br /><i>$AJAXDefinitions.Define(CartTitleID)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.TableSubTitleID</th>
			<td>$AJAXDefinitions.TableSubTitleID<br /><i>$AJAXDefinitions.Define(TableSubTitleID)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.CartSubTitleID</th>
			<td>$AJAXDefinitions.CartSubTitleID<br /><i>$AJAXDefinitions.Define(CartSubTitleID)</i></td>
		</tr>
		<tr>
			<th scope="row">&#36;AJAXDefinitions.QuantityFieldName</th>
			<td>$AJAXDefinitions.QuantityFieldName<br /><i>$AJAXDefinitions.Define(QuantityFieldName)</i></td>
		</tr>
	</table>
	<h5>within context of buyable</h5>
	<table style="width: 95%;">
		<tr>
			<th scope="row">&#36;AJAXDefinitions.UniqueIdentifier</th>
			<td>$AJAXDefinitions.UniqueIdentifier<br /><i>$AJAXDefinitions.Define(UniqueIdentifier)</i></td>
		</tr>
	</table>

<h4>Ajax Shopping Cart</h4>
<p>
	To view an ajax response for a <a href="$SimpleCartLinkAjax">shopping cart</a>
	- this can be used to add to an html node using an ajax call.
</p>

<h2>Product</h2>
<p>The following product has been randomly selected product for this exercise: <strong><a href="$RandomProduct.Link">$RandomProduct.MenuTitle</a></strong></p>
<p>You can <a href="$Link">reload</a> this page to view another product.</p>
<% with RandomProduct %>

<h4>Image Controllers</h4>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 60%;">&#36;Image.Link</th><td>$Image.SetWidth(100) $Image.Link</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;BestAvailableImage.Link</th><td>$BestAvailableImage.SetWidth(100) $BestAvailableImage.Link</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;DefaultImage.Link</th><td>$DefaultImage.SetWidth(100) $DefaultImage.Link</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;DummyImage.Link</th><td>$DummyImage.SetWidth(100) $DummyImage.Link</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;DefaultImageLink.Link</th><td>$DefaultImageLink</td></tr>
</table>

<h4>Links</h4>
<table style="width: 95%;" border="1" cellspacing="5">
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
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 60%;">&#36;IsInCart</th><td><% if IsInCart %> YES <% else %>NO<% end_if %></td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;Price</th><td>$Price</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;Price.Nice</th><td>$Price.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;CalculatedPrice.Nice</th><td>$CalculatedPrice.Nice</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;DisplayPrice.Nice</th><td>$DisplayPrice.Nice</td></tr>
</table>

<h4>Fields</h4>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 60%;">&#36;EcomQuantityField</th><td>$EcomQuantityField</td></tr>
</table>

<h4>Only Available on Product Page (through controller)</h4>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 60%;">&#36;AddProductForm</th><td>$AddProductForm</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;PreviousProduct</th><td>$PreviousProduct</td></tr>
	<tr><th scope="row" style="width: 60%;">&#36;NextProduct</th><td>$NextProduct</td></tr>
</table>

<% end_with %>

</div>

