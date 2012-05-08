<table id="InformationTable" class="infotable readonly">
	<thead>
		<tr>
			<th scope="col" class="left"><% _t("Order.PRODUCT","Product") %></th>
			<th scope="col" class="center"><% _t("Order.QUANTITY", "Quantity") %></th>
			<th scope="col" class="right"><% _t("Order.PRICE","Price") %></th>
			<th scope="col" class="right"><% _t("Order.TOTALPRICE","Total Price") %></th>
		</tr>
	</thead>
	<tfoot>
	<% if Items %>
		<tr class="gap total summary">
			<th colspan="3" scope="row"><% _t("Order.TOTAL","Total") %></th>
			<td class="right total" id="$AJAXDefinitions.TableTotalID"><span class="value">$Total.Nice</span> <span class="currency">$Currency</span></td>
		</tr>
	<% end_if %>
	</tfoot>
	<tbody>
	<% if Items %>
		<% control Items %>
		<tr  class="itemRow $EvenOdd $FirstLast">
			<td class="product title" scope="row">
				<% if Link %>
					<a href="$Link" target="_blank">$TableTitle</a> $TableSubTitle
				<% else %>
					$TableTitle $TableSubTitle
				<% end_if %>
			</td>
			<td class="center quantity">$Quantity</td>
			<td class="right unitprice">$UnitPrice.Nice</td>
			<td class="right total">$Total.Nice</td>
		</tr>
		<% end_control %>

		<tr class="gap summary" id="SubTotal">
			<th colspan="3" scope="row" class="threeColHeader subtotal"><% _t("Order.SUBTOTAL","Sub-total") %></th>
			<td class="right">$SubTotal.Nice</td>
		</tr>

		<% control Modifiers %>
			<% if ShowInTable %>
		<tr class="modifierRow $EvenOdd $FirstLast $Classes <% if HideInAjaxUpdate %> hideForNow<% end_if %>">
			<td colspan="3" scope="row">$TableTitle</td>
			<td class="right total">$TableValue.Nice</td>
		</tr>
			<% end_if %>
		<% end_control %>
	<% else %>
		<tr class="showOnZeroItems">
			<td colspan="4" scope="row" class="center">
				<% _t("Order.NOITEMS","There are <strong>no</strong> items in your cart.") %>
			</td>
		</tr>
	<% end_if %>
	</tbody>
</table>
