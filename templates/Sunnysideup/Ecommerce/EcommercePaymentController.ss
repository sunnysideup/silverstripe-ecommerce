<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageHeader Title='Payment' %>


<% if ErrorMessage %><div id="Error" class="typography">$ErrorMessage</div><% end_if %>
<% if GoodMessage %><div id="Error" class="typography">$GoodMessage</div><% end_if %>
<% if PaymentForm %><div id="Outer" class="typography">$PaymentForm</div><% end_if %>


<% include Sunnysideup\Ecommerce\Includes\HeaderAndFooter\BasicPageFooter %>
