<!--
NOTE:
Any element with the following classname: $AJAXDefinitions.TinyCartClassName
will be set to the contents of this file when the cart is updated using AJAX
If you are not using this snippet then theme it and remove its content to speed up your AJAX cart.
-->
<div class="cartShortInner">
<% if Items %>
	<table id="InformationTable" class="editable" cellspacing="0" cellpadding="0" summary="<% _t("TABLESUMMARY","The contents of your cart are displayed in this table - go to the Checkout Page to make final adjustments and review additional charges and deductions.") %>">
		<tbody>
	<% loop Items %>
		<% if ShowInTable %>
			<tr id="$AJAXDefinitions.TableID" class="$Classes hideOnZeroItems orderItemHolder">
				<td class="product title" scope="row">
					<% if Link %>
					<a id="$AJAXDefinitions.TableTitleID" href="$Link">$TableTitle</a>
					<% else %>
					<span id="$AJAXDefinitions.TableTitleID">$TableTitle</span>
					<% end_if %>
					<div id="$AJAXDefinitions.TableSubTitleID">$TableSubTitle</div >
				</td>
			</tr>
		<% end_if %>
	<% end_loop %>
		</tbody>
	</table>
	<p class="goToCheckout"><a href="$EcomConfig.CheckoutLink" class="action goToCheckoutLink"><% _t("Order.GOTOCHECKOUTLINK","Go to the checkout") %></a></p>
<% else %>
		<p class="noItems"><% _t("Order.NOITEMS","There are <strong>no</strong> items in your cart.") %></p>
<% end_if %>
</div>
