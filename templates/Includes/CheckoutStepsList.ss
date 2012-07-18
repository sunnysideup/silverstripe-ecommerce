<ol class="steps youHave{$PercentageDone}PercentageDone">
<% control CheckoutSteps %>
	<% if Link %>
		<li class="$LinkingMode"><a href="$Link">$Title</a></li>
	<% else %>
		<li class="$LinkingMode">$Title</li>
	<% end_if %>
<% end_control %>
</ol>
