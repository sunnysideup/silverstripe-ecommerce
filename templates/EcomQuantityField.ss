<div class="ecomquantityfield">
	<a style="visibility: <% if Quantity %>visible<% else %>hidden<% end_if %>;" class="removeOneLink" href="$DecrementLink" title="<%t Order.REMOVEONE 'Remove one &quot;{name}&quot; from your cart.' name=$Item.TableTitle.ATT %>">
		-
	</a>
	$Field
	<a class="addOneLink" href="$IncrementLink" title="<%t Order.ADDONE 'Add one &quot;{name}&quot; to your cart.' name=$Item.TableTitle.ATT %>">
		+
	</a>
	$AJAXLinkHiddenField
</div>
