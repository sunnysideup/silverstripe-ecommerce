<% if RelevantPayments %>
<table id="OrderPayment" class="infotable $Status.LowerCase">
    <thead>
        <tr class="gap mainHeader">
            <th colspan="5" class="left"><% _t("Order.PAYMENTS","Payment(s)") %></th>
        </tr>
        <tr>
            <th scope="col" class="center"><% _t("Order.DATE","Date") %></th>
            <th scope="col" class="center"><% _t("Order.PAYMENTSTATUS","Payment Status") %></th>
            <th scope="col" class="center"><% _t("Order.PAYMENTMETHOD","Method") %></th>
            <th scope="col" class="left"><% _t("Order.PAYMENTNOTE","Note") %></th>
            <th scope="col" class="right"><% _t("Order.AMOUNT","Amount") %></th>
        </tr>
    </thead>
    <tbody>
    <% loop RelevantPayments %>
        <tr>
            <td class="center">$LastEdited.Nice24</td>
            <td class="center">$Status</td>
            <td class="center">$PaymentMethod.XML</td>
            <td class="left">$Message.NoHTML.XML</td>
            <td class="right">$Amount.NiceDefaultFormat</td>
        </tr>
    <% end_loop %>
    </tbody>
</table>
<% else %>
<p id="NoPaymentsNote"><% _t("Order.NOPAYMENTS","There are no payments for this order.") %></p>
<% end_if %>
