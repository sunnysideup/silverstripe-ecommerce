<% if HasVariations %>
<ul class="productActions <% if VariationIsInCart %>inCart<% else %>notInCart<% end_if %>" id="$AJAXDefinitions.UniqueIdentifier">
	<li class="variationsLink">
		<span class="removeLink goToCartLink">
			<a href="$EcomConfig.CheckoutLink" title="<% _t("Product.GOTOCHECKOUTLINK","Go to the checkout") %>"><% _t("Product.GOTOCHECKOUTLINK","Go to the checkout") %>
			</a>
		</span>
		<a href="{$AddVariationsLink}" class="selectVariation btn action" rel="VariationsTable{$ID}" title="<% _t("Product.UPDATECART","update cart") %> for $Title.ATT">
			<span class="removeLink"><% _t("Product.INCART","In Cart") %></span>
			<span class="addLink"><% _t("Product.ADDLINK","Add to cart") %></span>
		</a>
	</li>
</ul>
<% else %>
<ul class="productActions <% if IsInCart %>inCart<% else %>notInCart<% end_if %>" id="$AJAXDefinitions.UniqueIdentifier">
	<li class="removeLink">
		<a class="goToCartLink btn action" href="$EcomConfig.CheckoutLink" title="<% _t("Product.GOTOCHECKOUTLINK","Go to the checkout") %>"><% _t("Product.GOTOCHECKOUTLINK","Checkout") %></a>
		<a class="ajaxBuyableRemove ajaxAddToCartLink" href="$RemoveAllLink" title="<% _t("Product.REMOVELINK","Remove from Cart") %>"><% _t("Product.REMOVELINK","Remove from Cart") %></a>
	</li>
	<li class="addLink">
		<a class="ajaxBuyableAdd btn action ajaxAddToCartLink" href="$AddLink" title="<% _t("Product.ADDLINK","Add to Cart") %>"><% _t("Product.ADDLINK","Add to Cart") %></a>
	</li>
</ul>
<% end_if %>
