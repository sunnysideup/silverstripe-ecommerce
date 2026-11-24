<% if $HasVariations %>
<ul class="$AJAXDefinitions.ProductListItemClassName <% if VariationIsInCart %>$AjaxDefinitons.ProductListItemInCartClassName<% else %>$AjaxDefinitons.ProductListItemNotInCartClassName<% end_if %>" id="$AJAXDefinitions.UniqueIdentifier">
    <li class="variationsLink">
        <a class="selectVariation btn action ajaxAddToCartLink" href="{$AddVariationsLink}" rel="VariationsTable{$ID}" title="<%t Product.UPDATECART 'update cart for' %> $Title.ATT">
            <span class="removeLink"><%t Product.INCART 'In Cart' %></span>
            <span class="addLink"><%t Product.ADDLINK 'Add to cart' %></span>
        </a>
    </li>
</ul>
<% else %>
<ul class="$AJAXDefinitions.ProductListItemClassName <% if IsInCart %>$AJAXDefinitions.ProductListItemInCartClassName<% else %>$AJAXDefinitions.ProductListItemNotInCartClassName<% end_if %>" id="$AJAXDefinitions.UniqueIdentifier">
    <li class="removeLink">
        <a class="goToCartLink btn action" href="$EcomConfig.CheckoutLink" title="<%t Product.GOTOCHECKOUTLINK 'Go to the checkout' %>" rel="nofollow">
            <span class="removeLink goToCartLink"><%t Product.GOTOCHECKOUTLINK 'Go to the checkout' %></span>
        </a>
        <form class="button-form enable-after-adding-security-id" action="$RemoveAllLink" method="post"<% if not $AddSecurityIdToLinks %> disabled="disabled"<% end_if %>>
            <input type="hidden" name="SecurityID" value="<% if $AddSecurityIdToLinks %>$SecurityIDToken<% end_if %>"/>
            <button type="submit" class="ajaxBuyableRemove ajaxRemoveFromCartLink button" title="<%t Product.REMOVELINK 'Remove from Cart' %>">
                <span class="removeLink"><span class="icon icon-cart-remove"></span><span class="text"><%t Product.INCART 'In Cart' %></span></span>
            </button>
        </form>
    </li>
    <li class="addLink">
        <form class="button-form enable-after-adding-security-id" action="$AddLink" method="post"<% if not $AddSecurityIdToLinks %> disabled="disabled"<% end_if %>>
            <input type="hidden" name="SecurityID" value="<% if $AddSecurityIdToLinks %>$SecurityIDToken<% end_if %>"/>
            <button type="submit" class="ajaxBuyableAdd btn ajaxAddToCartLink button" title="<%t Product.ADDLINK 'Add to Cart' %>">
                <span class="addLink"><span class="icon icon-cart-add"></span><span class="text"><%t Product.ADDLINK 'Add to Cart' %></span></span>
            </button>
        </form>
    </li>
</ul>
<% end_if %>
