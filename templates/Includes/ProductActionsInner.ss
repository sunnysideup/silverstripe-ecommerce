<ul class="productActions <% if IsInCart %>inCart<% else %>notInCart<% end_if %>" id="$AJAXDefinitions.UniqueIdentifier">
<% if HasVariations %>
	<li class="variationsLink">
		<a href="{$AddVariationsLink}" class="selectVariation" rel="VariationsTable{$ID}">
			<% if VariationIsInCart %>
				<% _t("Product.REMOVELINK","Remove from cart") %>
			<% else %>
				<% _t("Product.ADDLINK","Add to cart") %>
			<% end_if %>
		</a>
	</li>
<% else %>
	<li class="removeLink">
		<a class="ajaxBuyableRemove action" href="$RemoveAllLink"><% _t("Product.REMOVELINK","Remove from cart") %></a>
	</li>
	<li class="addLink">
		<a class="ajaxBuyableAdd action" href="$AddLink"><% _t("Product.ADDLINK","Add to cart") %></a>
	</li>
<% end_if %>
</ul>

