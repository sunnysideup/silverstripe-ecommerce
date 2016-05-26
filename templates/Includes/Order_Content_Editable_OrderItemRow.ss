<tr id="$AJAXDefinitions.TableID" class="$Classes hideOnZeroItems orderItemHolder">
	<td class="product title">
		<% with Buyable %><% include ProductGroupItemImageThumb %><% end_with %>
		<% include Order_Content_Editable_BuyableTitle %>
	</td>
	<td class="center quantity">
		$QuantityField
	</td>
	<td class="right unitprice">$UnitPriceAsMoney.NiceDefaultFormat</td>
	<td class="right total" id="$AJAXDefinitions.TableTotalID">$TotalAsMoney.NiceDefaultFormat</td>
	<td class="right remove">
		<% if RemoveAllLink %>
		<strong>
			<a class="ajaxQuantityLink ajaxRemoveFromCart" href="$RemoveAllLink">
				<img src="ecommerce/images/remove.gif" alt="x"/>
			</a>
		</strong>
		<% end_if %>
	</td>
</tr>
