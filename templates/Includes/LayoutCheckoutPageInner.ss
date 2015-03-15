<h1 class="pagetitle">$Title</h1>

<% if ShowOnlyCurrentStep %><% if CanCheckout %><% include CheckoutStepsList %><% end_if %><% end_if %>

<% include CartActionsAndMessages %>

<% if CanCheckout %>

<!-- add your own steps here... -->

<!-- step 1 OrderItems -->
<% if CanShowStep(orderitems) %>
<div id="OrderItemsOuter" class="checkoutStep">

	<% if StepsContentHeading(1) %><h2 class="orderStepHeading">$StepsContentHeading(1)</h2><% end_if %>
	<% if StepsContentAbove(1) %><p class="above headerFooterDescription">$StepsContentAbove(1)</p><% end_if %>
	<% with Order %><% include Order_Content_Editable %><% end_with %>
	<% include Order_Content_Editable_ModifierForms %>
	<% if StepsContentBelow(1) %><p class="below headerFooterDescription">$StepsContentBelow(1)</p><% end_if %>

	<% if HasCheckoutSteps %>
	<div class="checkoutStepPrevNextHolder next">
		<a href="{$Link}checkoutstep/orderformaddress/#OrderFormAddressOuter" class="action btn"><% _t("NEXT","next") %></a>
	</div>
	<% end_if %>

</div>
<% end_if %>


<!-- add your own steps here... -->

<!-- step 2 OrderFormAddress -->
<% if CanShowStep(orderformaddress) %>
<div id="OrderFormAddressOuter" class="checkoutStep">

	<% if HasCheckoutSteps %>
	<div class="checkoutStepPrevNextHolder prev">
		<a href="{$Link}checkoutstep/orderitems/#OrderItemsOuter" class="action btn"><% _t("GOBACK","go back") %></a>
	</div>
	<% end_if %>

	<% if StepsContentHeading(2) %><h2 class="orderStepHeading">$StepsContentHeading(2)</h2><% end_if %>
	<% if StepsContentAbove(2) %><p class="above headerFooterDescription">$StepsContentAbove(2)</p><% end_if %>
	<div id="OrderFormAddressHolder">$OrderFormAddress</div>
	<% if StepsContentBelow(2) %><p class="below headerFooterDescription">$StepsContentBelow(2)</p><% end_if %>

<!-- there is no next link here, because the member will have to submit the form -->
</div>
<% end_if %>



<!-- add your own steps here... -->


<!-- step 3 Order confirmation and payment - ALWAYS the final step -->

<% if IsFinalStep %>
<div id="OrderConfirmationAndPayment" class="checkoutStep">

	<% if HasCheckoutSteps %>
	<div class="checkoutStepPrevNextHolder prev">
		<a href="{$Link}checkoutstep/orderformaddress/#OrderFormAddressOuter" class="action btn"><% _t("GOBACK","go back") %></a>
	</div>
	<% else %>
	<div class="checkoutStepPrevNextHolder prev">
		<a href="{$Link}" class="action btn"><% _t("GOBACK","go back") %></a>
	</div>
	<% end_if %>


	<% if StepsContentHeading(3) %><h2 class="orderStepHeading">$StepsContentHeading(3)</h2><% end_if %>
	<% if StepsContentAbove(3) %><p class="above headerFooterDescription">$StepsContentAbove(3)</p><% end_if %>
	<% with Order %>
		<% include Order_Addresses %>
		<% include Order_Content %>
	<% end_with %>
	<div id="OrderFormHolder">$OrderForm</div>
	<% if StepsContentBelow(3) %><p class="below headerFooterDescription">$StepsContentBelow(3)</p><% end_if %>

</div>

<% end_if %>


<% end_if %>
<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

