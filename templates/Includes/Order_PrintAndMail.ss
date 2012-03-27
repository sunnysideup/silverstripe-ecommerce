<ul id="OrderPrintAndMail">
	<% if EmailLink %>
	<li id="SendCopyOfReceipt">
		<a href="$EmailLink">
			<% sprintf(_t("Order.SENDCOPYRECEIPT","send a copy of receipt to %s"),$OrderEmail) %>
		</a>
	</li>
	<% end_if %>

	<% if PrintLink %>
	<li id="PrintCopyOfReceipt" >
		<a href="$PrintLink">
			<% _t("Order.PRINTINVOICE","print invoice") %>
		</a>
	</li>
	<% end_if %>
</ul>
<% require javascript(ecommerce/javascript/EcomPrintAndMail.js) %>
