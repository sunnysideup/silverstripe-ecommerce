<ul class="productActions <% if IsInCart %>inCart<% else %>notInCart<% end_if %>" id="$AJAXDefinitions.UniqueIdentifier">
<% if HasVariations %>
	<li class="variationsLink">
		<a href="{$AddVariationsLink}" class="selectVariation action" rel="VariationsTable{$ID}" title="<% if VariationIsInCart %><% _t("Product.REMOVELINK","Remove from Cart") %><% else %><% _t("Product.ADDLINK","Add to Cart") %><% end_if %>">
			<% if VariationIsInCart %>
				<% _t("Product.REMOVELINK","Remove from cart") %>
			<% else %>
				<% _t("Product.ADDLINK","Add to cart") %>
			<% end_if %>
		</a>
	</li>
<% else %>
	<li class="removeLink">
		<a class="ajaxBuyableRemove action ajaxAddToCartLink" href="$RemoveAllLink" title="<% _t("Product.REMOVELINK","Remove from Cart") %>"><% _t("Product.REMOVELINK","Remove from Cart") %></a>
	</li>
	<li class="addLink">
		<a class="ajaxBuyableAdd action ajaxAddToCartLink" href="$AddLink" title="<% _t("Product.ADDLINK","Add to Cart") %>"><% _t("Product.ADDLINK","Add to Cart") %></a>
	</li>
<% end_if %>
</ul>

