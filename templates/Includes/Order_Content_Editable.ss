<% include Order_ConfirmCountry %>

<table id="InformationTable" class="editable infotable">
    <thead>
        <tr>
            <th scope="col" class="left titleCol"><% _t("Order.PRODUCT","Product") %></th>
            <th scope="col" class="center quantityCol"><% _t("Order.QUANTITY", "Quantity") %></th>
            <th scope="col" class="right priceCol"><% _t("Order.PRICE","Price") %> ($CurrencyUsed.Code)</th>
            <th scope="col" class="right totalCol"><% _t("Order.TOTALPRICE","Total Price") %></th>
            <th scope="col" class="right emptyCell deleteCol"></th>
        </tr>
    </thead>
    <tbody>
<% if Items %>
    <% loop Items %>
        <% if ShowInTable %>
            <% include Order_Content_Editable_OrderItemRow %>
        <% end_if %>
    <% end_loop %>

        <tr class="gap summary hideOnZeroItems subTotal">
            <th colspan="3" scope="row" class="firstThreeCols"><% _t("Order.SUBTOTAL","Sub-total") %></th>
            <td class="right totalCol" id="$AJAXDefinitions.TableSubTotalID">$SubTotalAsMoney.NiceDefaultFormat</td>
            <td class="emptyCell deleteCol">&nbsp;</td>
        </tr>

    <% if Modifiers %>
        <% loop Modifiers %>
            <% if ShowInTable %>
            <% include Order_Content_Editable_ModifierRow %>
            <% end_if %>
        <% end_loop %>
    <% end_if %>
<% end_if %>
    </tbody>
    <tfoot>
<% if Items %>
        <tr class="gap total summary hideOnZeroItems">
            <th colspan="3" scope="row" class="firstThreeCols"><% _t("Order.TOTAL","Total") %> ($CurrencyUsed.Code)</th>
            <td class="right total totalCol" id="$AJAXDefinitions.TableTotalID">
                <span class="value">$TotalAsMoney.NiceDefaultFormat</span>
            </td>
            <td class="emptyCell deleteCol">&nbsp;</td>
        </tr>
<% end_if %>
<% if CustomerViewableOrderStatusLogs %>
    <% loop CustomerViewableOrderStatusLogs %>
        <tr>
            <th class="left titleCol" scope="row">$Title</th>
            <td class="left fourCols" colspan="4"><% if CustomerNote %>$CustomerNote<% else %>no further information<% end_if %></td>
        </tr>
    <% end_loop %>
<% end_if %>
        <tr class="cartMessage">
            <td colspan="5" class="center $CartStatusClass fiveCols" id="$AJAXDefinitions.TableMessageID">
                $CartStatusMessage
                <% if $SubmitErrors %>
                <div class="submitErrors">
                    <p class="warning message"><% _t('Order.AREAS_THAT_NEED_ATTENTION', 'areas that need attention') %></p>
                <ul>
                <% loop $SubmitErrors %>
                    <li>$Title</li>
                <% end_loop %>
                </ul>
                </div>
                <% end_if %>
            </td>
        </tr>
        <tr class="showOnZeroItems"<% if Items %> style="display: none"<% end_if %>>
            <td colspan="5" class="center fiveCols">
                $DisplayPage.NoItemsInOrderMessage
            </td>
        </tr>
    </tfoot>
</table>


<% include ShoppingCartRequirements %>
