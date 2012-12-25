<tr id="$AJAXDefinitions.TableID" class="$Classes hideOnZeroItems orderItemHolder">
	<td class="product title" scope="row">
		<% if Buyable %><% loop Buyable %><% include ProductGroupItemImageThumb %><% end_loop %><% end_if %>
		<div class="itemTitleAndSubTitle">
			<% if Link %>
				<a id="$AJAXDefinitions.TableTitleID" href="$Link" title="<% sprintf(_t("Order.READMORE","Click here to read more on &quot;%s&quot;"),$TableTitle) %>">$TableTitle</a>
			<% else %>
				<span id="$AJAXDefinitions.TableTitleID">$TableTitle</span>
			<% end_if %>
			<div class="tableSubTitle" id="$AJAXDefinitions.TableSubTitleID">$TableSubTitle</div>
		</div>
	</td>
	<td class="center quantity">
		$QuantityField
	</td>
	<td class="right unitprice">$UnitPrice.Nice</td>
	<td class="right total" id="$AJAXDefinitions.TableTotalID">$Total.Nice</td>
	<td class="right remove">
		<% if RemoveAllLink %>
		<strong>
			<a class="ajaxQuantityLink ajaxRemoveFromCart" href="$RemoveAllLink" title="<% sprintf(_t("Order.REMOVEALL","Remove &quot;%s&quot; from your cart"),$TableTitle) %>">
				<img src="ecommerce/images/remove.gif" alt="x"/>
			</a>
		</strong>
		<% end_if %>
	</td>
</tr>
