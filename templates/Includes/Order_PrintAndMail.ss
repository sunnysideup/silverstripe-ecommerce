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
		<a href="$PrintLink" onclick="javascript: window.open(this.href, \'print_order\', \'toolbar=0,scrollbars=1,location=1,statusbar=0,menubar=0,resizable=1,width=800,height=600,left = 50,top = 50\'); return false;">
			<% _t("Order.PRINTINVOICE","print invoice") %>
		</a>
	</li>
	<% end_if %>
</ul>
