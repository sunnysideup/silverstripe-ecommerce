<table id="OutstandingTable" class="infotable">
    <tbody>
        <tr class="gap summary" id="Outstanding">
            <th scope="row"><strong><% _t("Order.TOTALOUTSTANDING","Total outstanding") %></strong></th>
            <td class="right"><strong>$TotalOutstandingAsMoney.NiceDefaultFormat</strong></td>
        </tr>
    </tbody>
</table>
<% if $IsPaid %>
<% else_if $IsCancelled %>
<% else_if $PaymentIsPending %>
    <p class="paidNote"><% _t("Order.OUTSTANDINGNOTE","This order has a pending payment.") %>
        <a href="$Link#PaymentForm">
        <% _t("Order.PAYADDTIONALPAYMENTNOW","Make additional payment now") %>
    </a>.
    </p>
<% else %>
    <p class="paidNote">
    <% _t("Order.OUTSTANDINGNOTE","This order has an outstanding balance.") %>
    <a href="$Link#OrderFormPayment_PaymentForm">
        <% _t("Order.PAYNOW","Pay now") %>
    </a>.
    </p>
<% end_if %>

