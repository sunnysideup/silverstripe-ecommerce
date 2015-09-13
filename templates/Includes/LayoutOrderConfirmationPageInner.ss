<h1 class="pagetitle">$Title</h1>

<div class="paymentMessage">
<% if PaymentIsPending %>
	<h2 class="paymentHeader">$PaymentPendingHeader</h2>
	$PaymentPendingMessage
<% else %>
	<% if IsPaid %>
		<h2 class="paymentHeader">$PaymentSuccessfulHeader</h2>
		$PaymentSuccessfulMessage
	<% else %>
		<h2 class="paymentHeader">$PaymentNotSuccessfulHeader</h2>
		$PaymentNotSuccessfulMessage
	<% end_if %>
<% end_if %>
</div>

<% if Content %><div id="ContentHolder">$Content</div><% end_if %>


<% if Order %>
<% with Order %>
	<% include Order %>
<% end_with %>
<% if PaymentForm %><div id="PaymentForm">$PaymentForm</div><% end_if %>
<% if CancelForm %><div id="CancelForm">$CancelForm</div><% end_if %>
<% else %>
<p class="message bad"><% _t("OrderConfirmationPage.COULDNOTBEFOUND","Your order could not be found.") %></p>
<% end_if %>

<% include CartActionsAndMessages %>
