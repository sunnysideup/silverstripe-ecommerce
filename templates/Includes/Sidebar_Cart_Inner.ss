<!--
NOTE:
Any element with the following classname: $AJAXDefinitions.TinyCartClassName
will be set to the contents of this file when the cart is updated using AJAX
If you are not using this snippet then theme it and remove its content to speed up your AJAX cart.
-->
<div class="sidebarCartInner">
<% if Items %>
	<table id="InformationTable" class="editable" cellspacing="0" cellpadding="0" summary="<% _t("TABLESUMMARY","The contents of your cart are displayed in this table - go to the Checkout Page to make final adjustments and review additional charges and deductions.") %>">
		<thead></thead>
		<tfoot>
			<tr class="gap summary hideOnZeroItems">
				<td colspan="2" scope="row"><% _t("Cart.SUBTOTAL","Sub-total") %></td>
				<td class="right" id="$AJAXDefinitions.TableSubTotalID">$SubTotal.Nice</td>
			</tr>
			<tr class="showOnZeroItems"<% if Items %> style="display: none"<% end_if %>>
				<td colspan="3" scope="row" class="center"><% _t("Order.NOITEMS","There are <strong>no</strong> items in your cart.") %></td>
			</tr>
		</tfoot>
		<tbody>
	<% control Items %>
		<% if ShowInTable %>
			<tr id="$AJAXDefinitions.TableID" class="$Classes hideOnZeroItems orderItemHolder">
				<td class="product title" scope="row">
					<% if Link %>
						<a id="$AJAXDefinitions.CartTitleID" href="$Link">$CartTitle</a>
					<% else %>
						<span id="$AJAXDefinitions.CartTitleID">$CartTitle</span>
					<% end_if %>
					<div class="tableSubTitle" id="$AJAXDefinitions.CartSubTitleID">$CartSubTitle</div>
				</td>
				<td class="center quantity">
					$QuantityField
				</td>
				<td class="right total" id="$AJAXDefinitions.TableTotalID">$Total.Nice</td>
			</tr>
		<% end_if %>
	<% end_control %>
		</tbody>
	</table>
	<p class="goToCart"><a href="$EcomConfig.CheckoutLink" class="action goToCheckoutLink"><% _t("Cart.GOTOCHECKOUTLINK","Go to the checkout") %></a></p>
<% else %>
	<p class="noItems"><% _t("Order.NOITEMS","There are <strong>no</strong> items in your cart.") %></p>
<% end_if %>
</div>
