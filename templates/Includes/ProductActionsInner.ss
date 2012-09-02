<ul class="productActions <% if IsInCart %>inCart<% else %>notInCart<% end_if %>" id="$AJAXDefinitions.UniqueIdentifier">
<% if HasVariations %>
	<li class="variationsLink">
		<a href="{$AddVariationsLink}" class="selectVariation action" rel="VariationsTable{$ID}" title="<% _t("Product.UPDATECART","update cart") %> for $Title.ATT">
			<span class="removeLink"><% _t("Product.INCART","In Cart") %></span>
			<span class="addLink"><% _t("Product.ADDLINK","Add to cart") %></span>
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

