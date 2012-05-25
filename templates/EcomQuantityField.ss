<div class="ecomquantityfield">
	<a style="visibility: "<% if Quantity %>visible<% else %>hidden<% end_if %>" class="removeOneLink" href="$DecrementLink" title="<% sprintf(_t("Cart.REMOVEONE","Remove one of &quot;%s&quot; from your cart."),$Item.TableTitle) %>">
		-
	</a>
	$Field
	<a class="addOneLink" href="$IncrementLink" title="<% sprintf(_t("Cart.ADDONE","Add one more of &quot;%s&quot; to your cart."),$Item.TableTitle) %>">
		+
	</a>
	$AJAXLinkHiddenField
</div>
