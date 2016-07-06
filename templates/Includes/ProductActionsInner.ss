<% if HasVariations %>
<ul class="$AJAXDefinitions.ProductListItemClassName <% if VariationIsInCart %>$AjaxDefinitons.ProductListItemInCartClassName<% else %>$AjaxDefinitons.ProductListItemNotInCartClassName<% end_if %>" id="$AJAXDefinitions.UniqueIdentifier">
    <li class="variationsLink">
        <a class="selectVariation btn action ajaxAddToCartLink" href="{$AddVariationsLink}" rel="VariationsTable{$ID}" title="<% _t("Product.UPDATECART","update cart for") %> $Title.ATT">
            <span class="removeLink"><% _t("Product.INCART","In Cart") %></span>
            <span class="addLink"><% _t("Product.ADDLINK","Add to cart") %></span>
        </a>
    </li>
</ul>
<% else %>
<ul class="$AJAXDefinitions.ProductListItemClassName <% if IsInCart %>$AJAXDefinitions.ProductListItemInCartClassName<% else %>$AJAXDefinitions.ProductListItemNotInCartClassName<% end_if %>" id="$AJAXDefinitions.UniqueIdentifier">
    <li class="removeLink">
        <a class="goToCartLink btn action" href="$EcomConfig.CheckoutLink" title="<% _t("Product.GOTOCHECKOUTLINK","Go to the checkout") %>">
            <span class="removeLink goToCartLink"><% _t("Product.GOTOCHECKOUTLINK","Go to the checkout") %></span>
        </a>
        <a class="ajaxBuyableRemove ajaxRemoveFromCartLink" href="$RemoveAllLink" title="<% _t("Product.REMOVELINK","Remove from Cart") %>">
            <span class="removeLink"><% _t("Product.REMOVELINK","Remove from Cart") %></span>
        </a>
    </li>
    <li class="addLink">
        <a class="ajaxBuyableAdd btn action ajaxAddToCartLink" href="$AddLink" title="<% _t("Product.ADDLINK","Add to Cart") %>">
            <span class="addLink"><% _t("Product.ADDLINK","Add to Cart") %></span>
        </a>
    </li>
</ul>
<% end_if %>
