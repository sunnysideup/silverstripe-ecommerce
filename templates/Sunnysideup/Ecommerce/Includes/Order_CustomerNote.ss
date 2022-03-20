<% if CustomerOrderNote %>
<table id="NotesTable" class="infotable">
    <thead>
        <tr class="gap mainHeader">
            <th class="left" scope="col"><h3><% _t("Order.CUSTOMER_ORDER_NOTE","Customer Note") %></h3></th>
        </tr>
    </thead>
    <tbody>
        <tr class="summary odd first">
            <td class="left">$CustomerOrderNote.XML</td>
        </tr>
    </tbody>
</table>
<% end_if %>
