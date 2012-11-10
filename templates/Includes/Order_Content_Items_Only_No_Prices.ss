<table id="InformationTable" class="infotable" cellspacing="0" cellpadding="0" summary="<% _t("Order.TABLESUMMARYNOPRICES","The contents of your order are displayed in this list.") %>">
	<thead>
		<tr>
			<th scope="col" class="left"><% _t("Order.PRODUCT","Product") %></th>
			<th scope="col" class="center"><% _t("Order.QUANTITY", "Quantity") %></th>
		</tr>
	</thead>
	<tfoot>
		<tr class="cartMessage">
			<td colspan="2" scope="row" class="center $CartStatusClass" id="$AJAXDefinitions.TableMessageID">$CartStatusMessage</td>
		</tr>
		<tr class="showOnZeroItems"<% if Items %> style="display: none"<% end_if %>>
			<td colspan="2" scope="row" class="center">
				<% _t("Order.NOITEMS","There are <strong>no</strong> items in your cart.") %>
			</td>
		</tr>
	</tfoot>
	<tbody>
<% if Items %>
	<% control Items %>
		<% if ShowInTable %>
		<tr id="$AJAXDefinitions.TableID" class="$Classes hideOnZeroItems orderItemHolder">
			<td class="product title" scope="row">
				<div class="itemTitleAndSubTitle">
					<span id="$AJAXDefinitions.TableTitleID">$TableTitle</span>
					<div class="tableSubTitle" id="$AJAXDefinitions.TableSubTitleID">$TableSubTitle</div>
				</div>
			</td>
			<td class="center quantity">
				$Quantity
			</td>
		</tr>
		<% end_if %>
	<% end_control %>
<% end_if %>
	</tbody>
</table>

