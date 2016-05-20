<h1 class="pagetitle">$Title</h1>
<div class="paymentMessage $PaymentMessageType">
    <h2 class="paymentHeader">$PaymentHeader</h2>
    <div class="paymentMessageInner">$PaymentMessage</div>
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
