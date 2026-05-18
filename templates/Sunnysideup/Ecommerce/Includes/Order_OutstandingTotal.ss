<table id="OutstandingTable" class="information-table">
    <tbody>
        <tr class="gap summary" id="Outstanding">
            <th scope="row"><strong><%t Order.TOTALOUTSTANDING 'Total outstanding' %></strong></th>
            <td class="right"><strong>$TotalOutstandingAsMoney.NiceDefaultFormat</strong></td>
        </tr>
    </tbody>
</table>
<% if $IsPaid %>
<% else_if $IsCancelled %>
<% else_if $PaymentIsPending %>
    <p class="paidNote">
        <%t Order.OUTSTANDINGNOTE 'This order has a pending payment.' %>
    </p>
<% else %>
    <p class="paidNote">
        <%t Order.OUTSTANDINGNOTE 'This order has an outstanding balance.' %>
        <a href="$Link#OrderFormPayment_PaymentForm"><%t Order.PAYNOW 'Pay now' %></a>.
    </p>
<% end_if %>
