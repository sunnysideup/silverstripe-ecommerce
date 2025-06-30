<h2>Payment History</h2>
<% if $RelevantPayments %>
<table id="OrderPayment" class="infotable $Status.LowerCase">
    <thead>
        <tr class="gap mainHeader">
            <th colspan="5" class="left"><h3><% if $RelevantPayments.Count > 1 %><% _t("Order.PAYMENTS","Payments") %><% else %><% _t("Order.PAYMENT","Payment") %><% end_if %></h3></th>
        </tr>
        <tr>
            <th scope="col" class="left"><% _t("Order.DATE","Date") %></th>
            <th scope="col" class="left"><% _t("Order.PAYMENTSTATUS","Status") %></th>
            <th scope="col" class="left"><% _t("Order.PAYMENTMETHOD","Method") %></th>
            <th scope="col" class="left"><% _t("Order.PAYMENTNOTE","Note") %></th>
            <th scope="col" class="right"><% _t("Order.AMOUNT","Amount") %></th>
        </tr>
    </thead>
    <tbody>
    <% loop $RelevantPayments %>
        <tr>
            <td class="left" title="$LastEdited.Format('dd-MM-y HH:mm')">$LastEdited.Format('dd-MM-y')</td>
            <td class="left">$Status</td>
            <td class="left">$PaymentMethod.XML</td>
            <td class="left"><% if $Message.Plain %>$Message.Plain.RAW<% else %>---<% end_if %></td>
            <td class="right">$Amount.NiceDefaultFormat</td>
        </tr>
    <% end_loop %>
    </tbody>
</table>
<% else %>
<p id="NoPaymentsNote" class="message warning"><% _t("Order.NOPAYMENTS","There are no payments for this order.") %></p>
<% end_if %>
