<table id="OutstandingTable" class="information-table">
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
    <p class="paidNote">
        $EcomConfig.OrderPaymentPendingMessageForTemplate
    </p>
<% else %>
    <p class="paidNote">
        <a href="$Link#OrderFormPayment_PaymentForm" class="button"><% _t("Order.PAYNOW","Pay now") %></a>
        <% _t("Order.OUTSTANDINGNOTE","This order has an outstanding balance.") %>
    </p>
<% end_if %>
