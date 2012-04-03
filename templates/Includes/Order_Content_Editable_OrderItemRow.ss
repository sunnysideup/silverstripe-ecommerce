<tr id="$AJAXDefinitions.TableID" class="$Classes hideOnZeroItems orderItemHolder">
	<td class="product title" scope="row">
		<% if Buyable %><% control Buyable %><% include ProductGroupItemImage %><% end_control %><% end_if %>
		<% if Link %>
			<a id="$AJAXDefinitions.TableTitleID" href="$Link" title="<% sprintf(_t("Order.READMORE","Click here to read more on &quot;%s&quot;"),$TableTitle) %>">$TableTitle</a>
		<% else %>
			<span id="$AJAXDefinitions.TableTitleID">$TableTitle</span>
		<% end_if %>
		<div class="tableSubTitle" id="$AJAXDefinitions.TableSubTitleID">$TableSubTitle</div >
	</td>
	<td class="center quantity">
		$QuantityField
	</td>
	<td class="right unitprice">$UnitPrice.Nice</td>
	<td class="right total" id="$AJAXDefinitions.TableTotalID">$Total.Nice</td>
	<td class="right remove">
		<strong>
			<a class="ajaxQuantityLink" href="$removeallLink" title="<% sprintf(_t("Order.REMOVEALL","Remove all of &quot;%s&quot; from your cart"),$TableTitle) %>">
				<img src="ecommerce/images/remove.gif" alt="x"/>
			</a>
		</strong>
	</td>
</tr>
