<div class="ecomquantityfield">
    <form class="button-form enable-after-adding-security-id" action="$DecrementLink" method="post"<% if not $AddSecurityIdToLinks %> disabled="disabled"<% end_if %> style="visibility: <% if Quantity %>visible<% else %>hidden<% end_if %>;">
        <input type="hidden" name="SecurityID" value="<% if $AddSecurityIdToLinks %>$SecurityIDToken<% else %><% end_if %>"/>
        <button
            type="submit"
            class="removeOneLink"
            title="Remove one from Cart"
        >-</button>
    </form>

    <form class="button-form enable-after-adding-security-id" action="$QuantityLink" method="post" <% if not $AddSecurityIdToLinks %> disabled="disabled"<% end_if %>>
        <input type="hidden" name="SecurityID" value="<% if $AddSecurityIdToLinks %>$SecurityIDToken<% else %><% end_if %>"/>
        $Field
    </form>

    <form class="button-form enable-after-adding-security-id" action="$IncrementLink" method="post"<% if not $AddSecurityIdToLinks %> disabled="disabled"<% end_if %>>
        <input type="hidden" name="SecurityID" value="<% if $AddSecurityIdToLinks %>$SecurityIDToken<% else %><% end_if %>"/>
        <button
            type="submit"
            class="addOneLink"
            title="Add one to Cart"
        >+</button>
    </form>
</div>
