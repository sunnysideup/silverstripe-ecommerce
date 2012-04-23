	<h3 id="CartHeader"><% _t("Cart.CART","Cart") %></h3>
<% if Items %>
	<table id="InformationTable" class="editable" cellspacing="0" cellpadding="0" summary="<% _t("TABLESUMMARY","The contents of your cart are displayed in this table - go to the Checkout Page to make final adjustments and review additional charges and deductions.") %>">
		<tbody>
	<% control Items %>
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
	<% end_control %>
		</tbody>
	</table>
	<p class="goToCheckout"><a href="$EcomConfig.CheckoutLink" class="action goToCheckoutLink"><% _t("Cart.GOTOCHECKOUTLINK","Go to the checkout") %></a></p>
<% else %>
		<p class="noItems"><% _t("Cart.NOITEMS","There are no items in your cart.") %>.</p>
<% end_if %>
