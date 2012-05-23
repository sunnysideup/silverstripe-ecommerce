<div class="typography">

<h1>Ecommerce Test Page</h1>
<p>
	Welcome to the ecommerce template test.
	On this page you can see the most useful e-commerce template controls.
</p>

<h2>The Cart</h2>
The Cart can be accessed from anywhere using:</p>
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
<ul>
	<li><strong>Shop Address (&#36;EcomConfig.ShopPhysicalAddress):</strong> $EcomConfig.ShopPhysicalAddress</li>
	<li><strong>Receipt Email (&#36;EcomConfig.ReceiptEmail):</strong> $EcomConfig.ReceiptEmail</li>
	<li><strong>PostalCodeURL (&#36;EcomConfig.PostalCodeURL):</strong> $EcomConfig.PostalCodeURL</li>
	<li><strong>Postal Code Label (&#36;EcomConfig.PostalCodeLabel):</strong> $EcomConfig.PostalCodeLabel</li>
	<li><strong>Currency (&#36;EcomConfig.Currency):</strong> $EcomConfig.Currency</li>
	<li><strong>Account Page Link (&#36;EcomConfig.AccountPageLink):</strong> $EcomConfig.AccountPageLink</li>
	<li><strong>Checkout Page Link (&#36;EcomConfig.CheckoutLink):</strong> $EcomConfig.CheckoutLink</li>
	<li><strong>Cart Page Link (&#36;EcomConfig.CartPageLink):</strong> $EcomConfig.CartPageLink</li>
	<li><strong>Order Confirmation Page Link (&#36;EcomConfig.OrderConfirmationPageLink):</strong> $EcomConfig.OrderConfirmationPageLink</li>
	<li><strong>Default Image Link (you can also use &#36;DefaultImage.SetWidth(100) and that sort of jazz) (&#36;EcomConfig.DefaultImageLink):</strong> $EcomConfig.DefaultImageLink</li>


	<li>
		<strong>Is E-commerce Page:?</strong>
		<% if IsEcommercePage %>YES<% else %>NO<% end_if %>
		This will be TRUE (say YES) for the Product and Checkout type pages only.
	</li>
</ul>

<h2>Ajax Definitions</h2>
<p>
	Ajax definitions are used to add IDs and Classes to tags so that when the cart update they can update at the same time.
	An example would be a div with an ID like "number_of_items".
	The content of this div would automatically change when the number of items in the cart is updated.
	NOTE: many of these ajax definitions are meant to be used within the <em>control</em> (context) of an order, order item, or order modifier.
</p>
<h5>without context</h5>
<ul>
	<li><strong>&#36;AJAXDefinitions.SideBarCartID</strong> $AJAXDefinitions.SideBarCartID</li>
	<li><strong>&#36;AJAXDefinitions.SmallCartID</strong> $AJAXDefinitions.SmallCartID</li>
	<li><strong>&#36;AJAXDefinitions.TinyCartClassName</strong> $AJAXDefinitions.TinyCartClassName</li>
	<li><strong>&#36;AJAXDefinitions.TotalItemsClassName</strong> $AJAXDefinitions.TotalItemsClassName</li>
</ul>
<h5>without context, country and region related</h5>
<ul>
	<li><strong>&#36;AJAXDefinitions.ExpectedCountryClassName</strong> $AJAXDefinitions.ExpectedCountryClassName</li>
	<li><strong>&#36;AJAXDefinitions.CountryFieldID</strong> $AJAXDefinitions.CountryFieldID</li>
	<li><strong>&#36;AJAXDefinitions.RegionFieldID</strong> $AJAXDefinitions.RegionFieldID</li>
</ul>
<h5>within context of order, order item or order modifier</h5>
<ul>
	<li><strong>&#36;AJAXDefinitions.TableID</strong> $AJAXDefinitions.TableID</li>
	<li><strong>&#36;AJAXDefinitions.TableTotalID</strong> $AJAXDefinitions.TableTotalID</li>
</ul>
<h5>within context of order</h5>
<ul>
	<li><strong>&#36;AJAXDefinitions.TableMessageID</strong> $AJAXDefinitions.TableMessageID</li>
	<li><strong>&#36;AJAXDefinitions.TableSubTotalID</strong> $AJAXDefinitions.TableSubTotalID</li>
</ul>
<h5>within context of order item or order modifier</h5>
<ul>
	<li><strong>&#36;AJAXDefinitions.TableTitleID</strong> $AJAXDefinitions.TableTitleID</li>
	<li><strong>&#36;AJAXDefinitions.CartTitleID</strong> $AJAXDefinitions.CartTitleID</li>
	<li><strong>&#36;AJAXDefinitions.TableSubTitleID</strong> $AJAXDefinitions.TableSubTitleID</li>
	<li><strong>&#36;AJAXDefinitions.CartSubTitleID</strong> $AJAXDefinitions.CartSubTitleID</li>
	<li><strong>&#36;AJAXDefinitions.QuantityFieldName</strong> $AJAXDefinitions.QuantityFieldName</li>
</ul>
<h5>within context of buyable</h5>
<ul>
	<li><strong>&#36;AJAXDefinitions.UniqueIdentifier</strong> $AJAXDefinitions.UniqueIdentifier</li>
</ul>

<h4>Ajax Shopping Cart</h4>
<p>
	To view an ajax response for a <a href="$SimpleCartLinkAjax">shopping cart - </a>
	This link can be used for
</p>

</div>
