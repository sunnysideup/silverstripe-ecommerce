<div class="orderCustomerNotePaymentCheck">
	<% if PaymentConfirmed %>
		<p class="message good">
		<% _t("OrderStatusLog.PAYMENT_CONFIRMED_BY", "Payment Confirmed by: ") %> $Auther.Title
		</p>
	<% else %>
		<p class="message bad">
		<% _t("OrderStatusLog.PAYMENT_DECLINED_BY", "Payment DECLINED by: ") %> $Auther.Title
		</p>
	<% end_if %>
	<span>$Created.Nice</span>
</div>
