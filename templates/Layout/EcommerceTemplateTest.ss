<div id="EcommerceTaskTemplateTest" class="mainSection content-container noSidebar typography">

<h1>Ecommerce Template Test Page</h1>
<div id="TOCGoesHere"></div>
<p>
	Welcome to the ecommerce template test.
	On this page you can find a bunch of information on making smart e-commerce templates.
	We have just highlighted some options, not all of them are listed.
</p>

<h2>The Cart</h2>
<p>The Cart can be accessed from any ContentController using, for example:</p>
<pre>
&lt;% if Cart %&gt;
	&lt;% with Cart %&gt;
		&lt;% include Sidebar_Cart %&gt;
	&lt;% end_with %&gt;
&lt;% else %&gt;
	&lt;p&gt;No cart was found!&lt;/p&gt;
&lt;% end_if %&gt;
</pre>

<% if Cart %>
	<% with Cart %>
		<% include Sidebar_Cart %>
	<% end_with %>
<% else %>
<p>No cart was found!</p>
<% end_if %>


<h2>Cart Info</h2>
<p>
	Useful if you would like to display some information about the current cart ...
</p>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 40%;">Link to display order<br />&#36;Cart.Link</th><td width="60%">$Cart.Link</td></tr>
	<tr><th scope="row" style="width: 40%;">Display Page<br />e.g. &#36;Cart.DisplayPage.Title</th><td width="60%">$Cart.DisplayPage.Title</td></tr>
	<tr><th scope="row" style="width: 40%;">Number of products in cart<br />&#36;Cart.TotalItems</th><td width="60%">$Cart.TotalItems</td></tr>
	<tr><th scope="row" style="width: 40%;">Number of items in cart <br />&#36;Cart.TotalItemsTimesQuantity</th><td width="60%">$Cart.TotalItemsTimesQuantity</td></tr>
	<tr><th scope="row" style="width: 40%;">More than one item in cart?<br />&#36;Cart.MoreThanOneItemInCart</th><td width="60%"><% if Cart.MoreThanOneItemInCart %>YES<% else %>NO<% end_if %></td></tr>
	<tr><th scope="row" style="width: 40%;">Link to load unsubmitted order as current order<br />&#36;Cart.RetrieveLink</th><td width="60%">$Cart.RetrieveLink</td></tr>
	<tr><th scope="row" style="width: 40%;">Sub-Total as currency object<br />&#36;Cart.SubTotalAsCurrencyObject.Nice</th><td width="60%">$Cart.SubTotalAsCurrencyObject.Nice</td></tr>
	<tr><th scope="row" style="width: 40%;">Sub-Total as money object<br />&#36;Cart.SubTotalAsMoney.NiceDefaultFormat</th><td width="60%">$Cart.SubTotalAsMoney.NiceDefaultFormat</td></tr>
	<tr><th scope="row" style="width: 40%;">Extras as currency object<br />&#36;Cart.ModifiersSubTotalAsCurrencyObject.Nice</th><td width="60%">$Cart.ModifiersSubTotalAsCurrencyObject.Nice</td></tr>
	<tr><th scope="row" style="width: 40%;">Extras as money object<br />&#36;Cart.ModifiersSubTotalAsMoneyObject.NiceDefaultFormat</th><td width="60%">$Cart.ModifiersSubTotalAsMoneyObject.NiceDefaultFormat</td></tr>
	<tr><th scope="row" style="width: 40%;">Total as currency object<br />&#36;Cart.TotalAsCurrencyObject.Nice</th><td width="60%">$Cart.TotalAsCurrencyObject.Nice</td></tr>
	<tr><th scope="row" style="width: 40%;">Total as money object<br />&#36;Cart.TotalAsMoney.NiceDefaultFormat</th><td width="60%">$Cart.TotalAsMoney.NiceDefaultFormat</td></tr>
	<tr><th scope="row" style="width: 40%;">Total Paid as currency object<br />&#36;Cart.TotalPaidAsCurrencyObject.Nice</th><td width="60%">$Cart.TotalPaidAsCurrencyObject.Nice</td></tr>
	<tr><th scope="row" style="width: 40%;">Total Paid as money object<br />&#36;Cart.TotalPaidAsMoney.NiceDefaultFormat</th><td width="60%">$Cart.TotalPaidAsMoney.NiceDefaultFormat</td></tr>
	<tr><th scope="row" style="width: 40%;">Total Oustanding as currency object<br />&#36;Cart.TotalOutstandingAsCurrencyObject.Nice</th><td width="60%">$Cart.TotalOutstandingAsCurrencyObject.Nice</td></tr>
	<tr><th scope="row" style="width: 40%;">Total Oustanding as money object<br />&#36;Cart.TotalOutstandingAsMoney.NiceDefaultFormat</th><td width="60%">$Cart.TotalOutstandingAsMoney.NiceDefaultFormat</td></tr>
	<tr><th scope="row" style="width: 40%;">Country for current order<br />&#36;Cart.Country / &#36;Cart.FullNameCountry </th><td width="60%">$Cart.Country / $Cart.FullNameCountry</td></tr>
</table>

<h2>Submitted Order Info</h2>
<p>
	Useful if you would like to display some information about the a Submitted Order.
	For this example, we have created a method <i>SubmittedOrder</i> in the Controller.
	This method returns a random submitted order for the use of displaying the information below.
	Some links and other methods may not work as they are unavailable for the Order at hand.
</p>
<% with SubmittedOrder %>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 40%;">Order Title<br />&#36;SubmittedOrder.Title</th><td width="60%">$Title</td></tr>
	<tr><th scope="row" style="width: 40%;">Email Address for communication with customer<br />&#36;SubmittedOrder.OrderEmail</th><td width="60%">$OrderEmail</td></tr>
	<tr><th scope="row" style="width: 40%;">Link for submitted order that can be used in Emails<br />&#36;SubmittedOrder.EmailLink</th><td width="60%">$EmailLink</td></tr>
	<tr><th scope="row" style="width: 40%;">Link for submitted order for printing<br />&#36;SubmittedOrder.PrintLink</th><td width="60%">$PrintLink</td></tr>
	<tr><th scope="row" style="width: 40%;">Link for submitted order for packing slip<br />&#36;SubmittedOrder.PackingSlipLink</th><td width="60%">$PackingSlipLink</td></tr>
	<tr><th scope="row" style="width: 40%;">Link for submitted order to delete it<br />&#36;SubmittedOrder.DeleteLink</th><td width="60%">$DeleteLink</td></tr>
	<tr><th scope="row" style="width: 40%;">Link for submitted order to copy it<br />&#36;SubmittedOrder.CopyOrderLink</th><td width="60%">$CopyOrderLink</td></tr>
	<tr><th scope="row" style="width: 40%;">Current Step<br />e.g. &#36;SubmittedOrder.MyStep.Title</th><td width="60%">$MyStep.Title</td></tr>
	<tr><th scope="row" style="width: 40%;">Is Paid<br />e.g. &#36;SubmittedOrder.IsPaid</th><td width="60%">$IsPaid</td></tr>
	<tr><th scope="row" style="width: 40%;">Is Cancelled<br />e.g. &#36;SubmittedOrder.IsCancelled</th><td width="60%">$IsCancelled</td></tr>
	<tr><th scope="row" style="width: 40%;">Last step visible to customer (i.e. some steps are hidden, e.g. Check customer credit rating)<br />e.g. &#36;SubmittedOrder.CurrentStepVisibleToCustomer.Title</th><td width="60%">$CurrentStepVisibleToCustomer.Title</td></tr>
</table>
<% end_with %>

<h2>Ecommerce Configurations</h2>
<p>
	These configurations are set in a special DataObject in the database.
	They contain lots of settings, but here are the onest most useful for templates.
	These can be accessed from anywhere.
</p>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 40%;">Shop Address <br />&#36;EcomConfig.ShopPhysicalAddress</th><td width="60%">$EcomConfig.ShopPhysicalAddress</td></tr>
	<tr><th scope="row" style="width: 40%;">Receipt Email <br />&#36;EcomConfig.ReceiptEmail</th><td width="60%">$EcomConfig.ReceiptEmail</td></tr>
	<tr><th scope="row" style="width: 40%;">PostalCodeURL <br />&#36;EcomConfig.PostalCodeURL</th><td width="60%">$EcomConfig.PostalCodeURL</td></tr>
	<tr><th scope="row" style="width: 40%;">Postal Code Label <br />&#36;EcomConfig.PostalCodeLabel</th><td width="60%">$EcomConfig.PostalCodeLabel</td></tr>
	<tr><th scope="row" style="width: 40%;">Currency <br />&#36;EcomConfig.Currency - you can also use Currencies for a list</th><td width="60%">$EcomConfig.Currency</td></tr>
	<tr><th scope="row" style="width: 40%;">Account Page Link <br />&#36;EcomConfig.AccountPageLink</th><td width="60%">$EcomConfig.AccountPageLink</td></tr>
	<tr><th scope="row" style="width: 40%;">Checkout Page Link <br />&#36;EcomConfig.CheckoutLink</th><td width="60%">$EcomConfig.CheckoutLink</td></tr>
	<tr><th scope="row" style="width: 40%;">Cart Page Link <br />&#36;EcomConfig.CartPageLink</th><td width="60%">$EcomConfig.CartPageLink</td></tr>
	<tr><th scope="row" style="width: 40%;">Order Confirmation Page Link <br />&#36;EcomConfig.OrderConfirmationPageLink</th><td width="60%">$EcomConfig.OrderConfirmationPageLink</td></tr>
	<tr><th scope="row" style="width: 40%;">Default Image Link <br />&#36;EcomConfig.DefaultImageLink</th><td width="60%">$EcomConfig.DefaultImageLink</td></tr>
	<tr><th scope="row" style="width: 40%;">Default Image @ Width = 100px <br />&#36;EcomConfig.DefaultImage.SetWidth(100)</th><td width="60%">$EcomConfig.DefaultImage.SetWidth(100)</td></tr>
	<tr><th scope="row" style="width: 40%;">Current Customer Name <br />&#36;EcomConfig.Customer.Title, instead of Title, you can also use Email, FirstName, etc...</th><td width="60%">$EcomConfig.Customer.Title</td></tr>
	<tr><th scope="row" style="width: 40%;">Is E-commerce Page? (This will be TRUE (YES) for the Product and Checkout type pages only.)</th><td width="60%"><% if IsEcommercePage %>YES<% else %>NO<% end_if %></td></tr>
</table>


<h2>Ajax Definitions</h2>
<p>
	Ajax definitions are used to add IDs and Classes to html tags.
	When the cart updates the content of these HTML elements will be updated at the same time.
	An example would be a div with an ID like "number_of_items".
	The content of this div would automatically change when the number of items in the cart is updated.
	NOTE: many of these ajax definitions are meant to be used within the <em>control</em> (context) of an order, order item, or order modifier.
</p>
<h4>Updating a page ...</h4>
<p>
	To update a page, you can use the following Javascript, <i>EcomCart.getChanges</i>, defined as follows in the EcomCart object:
</p>
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
	You can view a sample of the response here: <a href="shoppingcart/ajaxtest/?ajax=1" target="_blank">/shoppingcart/ajaxtest/</a> (you need to be logged in as Admin).
</p>
<p>
	The key variable in the JSON is the cart that is being returned. The templates used, and the IDs / Classes loaded into,
	are set in the yml configs.  This is the default (KEY: name of AJAX variable (loaded into); VALUE: template used):
</p>
<pre>
CartResponse:
&nbsp;&nbsp;cart_responses_required:
&nbsp;&nbsp;&nbsp;&nbsp;TinyCartClassName: CartTinyInner
&nbsp;&nbsp;&nbsp;&nbsp;SmallCartID: CartShortInner
&nbsp;&nbsp;&nbsp;&nbsp;SideBarCartID: Sidebar_Cart_Inner
</pre>
<h4>Available variables for your templates</h4>
<h5>without context (can be used at <i>root level</i> in any template)</h5>
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.SideBarCartID</th>
			<td width="60%">$AJAXDefinitions.SideBarCartID<br /><i>$AJAXDefinitions.Define(SideBarCartID)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.SmallCartID</th>
			<td width="60%">$AJAXDefinitions.SmallCartID<br /><i>$AJAXDefinitions.Define(SmallCartID)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.TinyCartClassName</th>
			<td width="60%">$AJAXDefinitions.TinyCartClassName<br /><i>$AJAXDefinitions.Define(TinyCartClassName)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.TotalItemsClassName</th>
			<td width="60%">$AJAXDefinitions.TotalItemsClassName<br /><i>$AJAXDefinitions.Define(TotalItemsClassName)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.TotalItemsTimesQuantityClassName</th>
			<td width="60%">$AJAXDefinitions.TotalItemsTimesQuantityClassName<br /><i>$AJAXDefinitions.Define(TotalItemsTimesQuantityClassName)</i></td>
		</tr>
	</table>

	<h5>without context, ajaxified product list</h5>
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.HiddenPageTitleID</th>
			<td width="60%">$AJAXDefinitions.HiddenPageTitleID<br /><i>$AJAXDefinitions.Define(HiddenPageTitleID)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.ProductListHolderID</th>
			<td width="60%">$AJAXDefinitions.ProductListHolderID<br /><i>$AJAXDefinitions.Define(ProductListHolderID)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.ProductListAjaxifiedLinkClassName</th>
			<td width="60%">$AJAXDefinitions.ProductListAjaxifiedLinkClassName<br /><i>$AJAXDefinitions.Define(ProductListAjaxifiedLinkClassName)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.ProductListItemClassName</th>
			<td width="60%">$AJAXDefinitions.ProductListItemClassName<br /><i>$AJAXDefinitions.Define(ProductListItemClassName)</i></td>
		</tr>
	</table>

	<h5>without context, products in cart (or not in cart)</h5>
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>

			<th scope="row" width="40%">&#36;AJAXDefinitions.ProductListItemInCartClassName</th>
			<td width="60%">$AJAXDefinitions.ProductListItemInCartClassName<br /><i>$AJAXDefinitions.Define(ProductListItemInCartClassName)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.ProductListItemNotInCartClassName</th>
			<td width="60%">$AJAXDefinitions.ProductListItemNotInCartClassName<br /><i>$AJAXDefinitions.Define(ProductListItemNotInCartClassName)</i></td>
		</tr>
	</table>

	<h5>without context, country and region related</h5>
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.ExpectedCountryClassName</th>
			<td width="60%">$AJAXDefinitions.ExpectedCountryClassName<br /><i>$AJAXDefinitions.Define(ProductListItemInCartClassName)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.CountryFieldID</th>
			<td width="60%">$AJAXDefinitions.CountryFieldID<br /><i>$AJAXDefinitions.Define(ProductListItemInCartClassName)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.RegionFieldID</th>
			<td width="60%">$AJAXDefinitions.RegionFieldID<br /><i>$AJAXDefinitions.Define(RegionFieldID)</i></td>
		</tr>
	</table>

	<h5>within context of order, order item or order modifier</h5>
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.TableID</th>
			<td width="60%">$AJAXDefinitions.TableID<br /><i>$AJAXDefinitions.Define(TableID)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.TableTotalID</th>
			<td width="60%">$AJAXDefinitions.TableTotalID<br /><i>$AJAXDefinitions.Define(TableTotalID)</i></td>
		</tr>
	</table>

	<h5>within context of order</h5>
	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.TableMessageID</th>
			<td width="60%">$AJAXDefinitions.TableMessageID<br /><i>$AJAXDefinitions.Define(TableMessageID)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.TableSubTotalID</th>
			<td width="60%">$AJAXDefinitions.TableSubTotalID<br /><i>$AJAXDefinitions.Define(TableSubTotalID)</i></td>
		</tr>
	</table>
	<h5>within context of order item or order modifier</h5>

	<table style="width: 95%;" border="1" cellspacing="5">
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.TableTitleID</th>
			<td width="60%">$AJAXDefinitions.TableTitleID<br /><i>$AJAXDefinitions.Define(TableTitleID)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.CartTitleID</th>
			<td width="60%">$AJAXDefinitions.CartTitleID<br /><i>$AJAXDefinitions.Define(CartTitleID)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.TableSubTitleID</th>
			<td width="60%">$AJAXDefinitions.TableSubTitleID<br /><i>$AJAXDefinitions.Define(TableSubTitleID)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.CartSubTitleID</th>
			<td width="60%">$AJAXDefinitions.CartSubTitleID<br /><i>$AJAXDefinitions.Define(CartSubTitleID)</i></td>
		</tr>
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.QuantityFieldName</th>
			<td width="60%">$AJAXDefinitions.QuantityFieldName<br /><i>$AJAXDefinitions.Define(QuantityFieldName)</i></td>
		</tr>
	</table>
	<h5>within context of buyable</h5>
	<table style="width: 95%;">
		<tr>
			<th scope="row" width="40%">&#36;AJAXDefinitions.UniqueIdentifier</th>
			<td width="60%">$AJAXDefinitions.UniqueIdentifier<br /><i>$AJAXDefinitions.Define(UniqueIdentifier)</i></td>
		</tr>
	</table>

<h4>Ajax Shopping Cart</h4>
<p>
	To add a cart anywhere, you can create a custom ajax request for $SimpleCartLinkAjax (&#36;$SimpleCartLinkAjax): <a href="$SimpleCartLinkAjax">shopping cart</a>.
</p>

<h2>Product</h2>
<p>The following product has been randomly selected product for this exercise: <strong><a href="$RandomProduct.Link">$RandomProduct.MenuTitle</a></strong></p>
<p>You can <a href="$Link">reload</a> this page to view another product.</p>
<% with RandomProduct %>

<h4>Image Controllers</h4>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 40%;">&#36;Image.Link</th><td width="60%">$Image.SetWidth(100) $Image.Link</td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;BestAvailableImage.Link</th><td width="60%">$BestAvailableImage.SetWidth(100) $BestAvailableImage.Link</td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;DefaultImage.Link</th><td width="60%">$DefaultImage.SetWidth(100) $DefaultImage.Link</td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;DummyImage.Link</th><td width="60%">$DummyImage.SetWidth(100) $DummyImage.Link</td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;DefaultImageLink.Link</th><td width="60%">$DefaultImageLink</td></tr>
</table>

<h4>Links</h4>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 40%;">&#36;AddVariationsLink</th><td width="60%"><a href="$AddVariationsLink">$AddVariationsLink</a></td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;AddLink</th><td width="60%"><a href="$AddLink">$AddLink</a></td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;IncrementLink</th><td width="60%"><a href="$IncrementLink">$IncrementLink</a></td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;DecrementLink</th><td width="60%"><a href="$DecrementLink">$DecrementLink</a></td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;RemoveLink</th><td width="60%"><a href="$RemoveLink">$RemoveLink</a></td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;RemoveAllLink</th><td width="60%"><a href="$RemoveAllLink">$RemoveAllLink</a></td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;RemoveAllAndEditLink</th><td width="60%"><a href="$RemoveAllLink">$RemoveAllAndEditLink</a></td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;SetSpecificQuantityItemLink - adding eleven here</th><td width="60%"><a href="$SetSpecificQuantityItemLink(11)">$SetSpecificQuantityItemLink(11)</a></td></tr>
</table>

<h4>Status and Price</h4>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 40%;">&#36;IsInCart</th><td width="60%"><% if IsInCart %> YES <% else %>NO<% end_if %></td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;Price</th><td width="60%">$Price</td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;Price.Nice</th><td width="60%">$Price.Nice</td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;CalculatedPrice.Nice</th><td width="60%">$CalculatedPrice.Nice</td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;CalculatedPriceAsMoney.NiceDefaultFormat</th><td width="60%">$CalculatedPriceAsMoney.NiceDefaultFormat</td></tr>
</table>

<h4>Fields</h4>
<p>The e-commerce quanity field provides the ability for the customer to change the quantity for a product in the cart (this is ajaxfied) </p>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 40%;">&#36;EcomQuantityField</th><td width="60%">$EcomQuantityField</td></tr>
</table>

<h4>Only Available on Product Page (through controller so not visible here ...)</h4>
<table style="width: 95%;" border="1" cellspacing="5">
	<tr><th scope="row" style="width: 40%;">&#36;AddProductForm</th><td width="60%">$AddProductForm</td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;PreviousProduct</th><td width="60%">$PreviousProduct</td></tr>
	<tr><th scope="row" style="width: 40%;">&#36;NextProduct</th><td width="60%">$NextProduct</td></tr>
</table>

<% end_with %>

</div>

<script type="text/javascript">

var ToC =
	"<nav role='navigation' class='table-of-contents'>" +
		"<h2>On this page:</h2>" +
		"<ul>";

var newLine, el, title, link, id, basehref;
basehref = window.location.href;
jQuery("#EcommerceTaskTemplateTest h2").each(
	function() {
		el = jQuery(this);
		title = el.text();
		id = title.replace(/[^a-z0-9]/gmi, "");
		id = "TOCGoTo"+id;
		el.attr("id", id);
		link = basehref + "#" + id;

		newLine =
			"<li>" +
				"<a href='" + link + "'>" +
					title +
				"</a>" +
			"</li>";

		ToC += newLine;

	}
);

ToC +=
	 "</ul>" +
	"</nav>";

jQuery("#TOCGoesHere").html(ToC);

</script>
