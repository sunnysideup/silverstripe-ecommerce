<tr id="$AJAXDefinitions.TableID" class="$Classes hideOnZeroItems orderItemHolder">
    <td class="product title">
        <% with Buyable %><% include Sunnysideup\Ecommerce\Includes\ProductGroupItemImageThumb %><% end_with %>
        <% include Sunnysideup\Ecommerce\Includes\Order_Content_Editable_BuyableTitle %>
    </td>
    <td class="center quantity">
        $QuantityField
    </td>
    <td class="right unitprice">$UnitPriceAsMoney.NiceDefaultFormat</td>
    <td class="right total" id="$AJAXDefinitions.TableTotalID">$TotalAsMoney.NiceDefaultFormat</td>
    <td class="right remove">
        <% if RemoveAllLink %>
        <form class="button-form enable-after-adding-security-id" action="$RemoveLink" method="post"<% if not $AddSecurityIdToLinks %> disabled="disabled"<% end_if %> rel="$ID">
            <input type="hidden" name="SecurityID" value="<% if $AddSecurityIdToLinks %>$SecurityIDToken<% else %><% end_if %>"/>
            <button
                type="submit"
                class="ajaxQuantityLink ajaxRemoveFromCart"
                title="Remove from Cart"
            >
                <img loading="lazy" src="$resourceURL('sunnysideup/ecommerce:client/images/remove.gif')" alt="remove icon" />
            </button>
        </form>
        <% end_if %>
    </td>
</tr>
