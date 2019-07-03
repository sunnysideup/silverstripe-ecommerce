<ol class="steps youHave{$PercentageDone}PercentageDone">
<% loop CheckoutSteps %>
	<% if Link %>
		<li class="$LinkingMode $Code step$ID"><a href="$Link">$Title</a></li>
	<% else %>
		<li class="$LinkingMode $Code step$ID">$Title</li>
	<% end_if %>
<% end_loop %>
</ol>
