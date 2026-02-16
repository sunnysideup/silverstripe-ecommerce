<table id="AddressesTable" class="information-table">
    <tr>
        <th scope="col"><% _t("Order.CUSTOMER","Customer") %></th>
        <% if CanHaveShippingAddress %><th scope="col"><% _t("Order.DELIVERTO","Deliver To") %></th><% end_if %>
    </tr>
    <tr>
        <td><% include Sunnysideup\Ecommerce\Includes\Order_AddressBilling %></td>
        <% if CanHaveShippingAddress %><td><% include Sunnysideup\Ecommerce\Includes\Order_AddressShipping %></td><% end_if %>
    </tr>
</table>
