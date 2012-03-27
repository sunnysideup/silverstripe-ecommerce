<ul class="productActions <% if IsInCart %>inCart<% else %>notInCart<% end_if %>" id="$UniqueIdentifier">
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
		<a class="ajaxBuyableRemove" href="$RemoveAllLink"><% _t("Product.REMOVELINK","Remove from cart") %></a>
	</li>
	<li class="addLink">
		<a class="ajaxBuyableAdd" href="$AddLink"><% _t("Product.ADDLINK","Add to cart") %></a>
	</li>
	<li class="goToCheckoutLink">
		<a class="goToCheckoutLink" href="$CheckoutLink"><% _t("Product.GOTOCHECKOUTLINK","Go to the checkout") %></a>
	</li>
<% end_if %>
</ul>

