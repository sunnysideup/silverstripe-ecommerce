<% if Cart %><% control Cart %>
<div id="ShoppingCart">
	<h3 id="CartHeader"><% _t("CART","Cart") %></h3>
<% if Items %>
	<table id="InformationTable" class="editable" cellspacing="0" cellpadding="0" summary="<% _t("TABLESUMMARY","The contents of your cart are displayed in this table - go to the Checkout Page to make final adjustments and review additional charges and deductions.") %>">
		<thead></thead>
		<tfoot>
			<tr class="gap summary hideOnZeroItems">
				<td colspan="2" scope="row"><% _t("Cart.SUBTOTAL","Sub-total") %></td>
				<td class="right" id="$TableSubTotalID">$SubTotal.Nice</td>
			</tr>
			<tr class="showOnZeroItems"<% if Items %> style="display: none"<% end_if %>>
				<td colspan="3" scope="row" class="center"><% _t("Cart.NOITEMS","There are no items in your cart") %></td>
			</tr>
		</tfoot>
		<tbody>
	<% control Items %>
		<% if ShowInTable %>
			<tr id="$TableID" class="$Classes hideOnZeroItems orderItemHolder">
				<td class="product title" scope="row">
					<% if Link %>
						<a id="$TableTitleID" href="$Link">$TableTitle</a>
					<% else %>
						<span id="$TableTitleID">$TableTitle</span>
					<% end_if %>
					<% if TableSubTitle %><div class="tableSubTitle">$TableSubTitle</div ><% end_if %>
				</td>
				<td class="center quantity">
					$QuantityField
				</td>
				<td class="right total" id="$TableTotalID">$Total.Nice</td>
			</tr>
		<% end_if %>
	<% end_control %>
		</tbody>
	</table>
	<p class="goToCart"><a href="$CheckoutLink"><% _t("Cart.GOTOCHECKOUTLINK","&raquo; Go to the checkout") %></a></p>
<% else %>
		<p class="noItems"><% _t("Cart.NOITEMS","There are no items in your cart") %>.</p>
<% end_if %>
</div>
<% end_control %><% end_if %>



<% include ShoppingCartRequirements %>
