<table id="InformationTable" class="editable infotable">
    <thead>
        <tr>
            <th scope="col" class="left"><% _t("Order.PRODUCT","Product") %></th>
            <th scope="col" class="center"><% _t("Order.QUANTITY", "Quantity") %></th>
            <th scope="col" class="right"><% _t("Order.PRICE","Price") %> ($EcomConfig.Currency)</th>
            <th scope="col" class="right"><% _t("Order.TOTALPRICE","Total Price") %> ($EcomConfig.Currency)</th>
            <th scope="col" class="right"></th>
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
            <th colspan="3"><% _t("Order.SUBTOTAL","Sub-total") %></th>
            <td class="right" id="$AJAXDefinitions.TableSubTotalID">$SubTotalAsMoney.NiceDefaultFormat</td>
            <td>&nbsp;</td>
        </tr>
<% end_if %>
    </tbody>
    <tfoot>
        <tr class="cartMessage">
            <td colspan="5" class="center $CartStatusClass" id="$AJAXDefinitions.TableMessageID">$CartStatusMessage</td>
        </tr>
        <tr class="showOnZeroItems"<% if Items %> style="display: none"<% end_if %>>
            <td colspan="5" class="center">
                <% _t("Order.NOITEMS","There are <strong>no</strong> items in your cart.") %>
            </td>
        </tr>
    </tfoot>
</table>
