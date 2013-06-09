<tr id="$AJAXDefinitions.TableID" class="$Classes hideOnZeroItems orderItemHolder">
	<td class="product title">
		<% if Buyable %><% loop Buyable %><% include ProductGroupItemImageThumb %><% end_loop %><% end_if %>
		<div class="itemTitleAndSubTitle">
			<% if Link %>
				<a id="$AJAXDefinitions.TableTitleID" href="$Link" title="<%t Order.READMORE 'Click here to read more on {name}' name=$TableTitle %>">$TableTitle</a>
			<% else %>
				<span id="$AJAXDefinitions.TableTitleID">$TableTitle</span>
			<% end_if %>
			<div class="tableSubTitle" id="$AJAXDefinitions.TableSubTitleID">$TableSubTitle</div>
		</div>
	</td>
	<td class="center quantity">
		$QuantityField
	</td>
	<td class="right unitprice">$UnitPriceAsMoney.NiceDefaultFormat</td>
	<td class="right total" id="$AJAXDefinitions.TableTotalID">$TotalAsMoney.NiceDefaultFormat</td>
	<td class="right remove">
		<% if RemoveAllLink %>
		<strong>
			<a class="ajaxQuantityLink ajaxRemoveFromCart" href="$RemoveAllLink" title="<%t Order.REMOVEALL 'Remove &quot;{name}&quot; from your cart' name=$TableTitle %>">
				<img src="ecommerce/images/remove.gif" alt="x"/>
			</a>
		</strong>
		<% end_if %>
	</td>
</tr>
