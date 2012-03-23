<ul class="productActions <% if IsInCart %>inCart<% else %>notInCart<% end_if %>">
	<li class="removeLink">
		<a class="ajaxBuyableRemove" href="$RemoveAllLink"><% _t("Product.REMOVELINK","Remove from cart") %></a>
	</li>
	<li class="addLink">
		<a class="ajaxBuyableAdd" href="$AddLink"><% _t("Product.ADDLINK","Add to cart") %></a>
	</li>
	<li class="goToCheckoutLink">
		<a class="goToCheckoutLink" href="$CheckoutLink"><% _t("Product.GOTOCHECKOUTLINK","Go to the checkout") %></a>
	</li>
</ul>
