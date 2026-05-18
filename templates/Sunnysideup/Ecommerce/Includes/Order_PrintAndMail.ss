<% if HasPrintOrEmailLink %>
<div id="OrderPrintAndMailOuter">
<h2><%t Order.KEEPARECORD 'Keep a Record' %></h2>
<ul id="OrderPrintAndMail">
    <% if EmailLink %>
    <li id="SendCopyOfReceipt">
        <a href="$EmailLink" data-popup="true">
            <%t Order.SENDCOPYRECEIPT 'send a copy of receipt to {name}' name=$OrderEmail %>
        </a>
    </li>
    <% end_if %>

    <% if PrintLink %>
    <li id="PrintCopyOfReceipt" >
        <a href="$PrintLink" data-popup="true">
            <%t Order.PRINTINVOICE 'print invoice' %>
        </a>
    </li>
    <% end_if %>

    <% if $canViewAdminStuff %>
    <li id="PrintPackingSlip" >
        <a href="$PackingSlipLink" data-popup="true">
            <%t Order.PRINTPACKINGSLIP 'print packing slip' %>
        </a>
    </li>
    <% end_if %>
</ul>
</div>
<% end_if %>
