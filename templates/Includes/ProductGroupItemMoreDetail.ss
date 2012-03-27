<li class="productItem $FirstLast item$Pos<% if FeaturedProduct %> featured<% end_if %>">
	<% include ProductGroupItemImage %>
	<h3 class="productTitle"><a href="$Link">$Title</a></h3>
	<div class="limtedContentHolder">$Content.Summary</div>
	<p class="moreInformation"><a href="$Link">more info ...</a></p>
	<% include ProductActionsForGroup %>
</li>
