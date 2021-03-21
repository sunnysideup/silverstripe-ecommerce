<h1 class="pagetitle">$Title</h1>
<div class="paymentMessage $PaymentMessageType">
    <h3 class="paymentHeader">$PaymentHeader</h3>
    <div class="paymentMessageInner">$PaymentMessage</div>
</div>

<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

<% if Order %>
<% with Order %>
    <% include Sunnysideup\Ecommerce\Includes\Order %>
<% end_with %>
<% if $PaymentForm %><div id="PaymentForm">$PaymentForm</div><% end_if %>
<% if $CancelForm %><div id="CancelForm">$CancelForm</div><% end_if %>
<% if $CustomerOrderStepForm %><div id="CustomerOrderStepForm">$CustomerOrderStepForm</div><% end_if %>
<% if FeedbackForm %>
    <div id="FeedbackForm">
        <h3>$FeedbackHeader</h3>
        $FeedbackForm
    </div>
<% end_if %>
<% else %>
<p class="message bad"><% _t("OrderConfirmationPage.COULDNOTBEFOUND","Your order could not be found.") %></p>
<% end_if %>

<% include Sunnysideup\Ecommerce\Includes\CartActionsAndMessages %>
