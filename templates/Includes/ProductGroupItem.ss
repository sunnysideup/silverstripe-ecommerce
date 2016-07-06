<li class="productItem $FirstLast item$Pos<% if FeaturedProduct %> featured<% end_if %>">
	<% include ProductGroupItemImage %>
	<h3 class="productTitle"><a href="$Link">$Title</a></h3>
	<% if ShortDescription %><p>$ShortDescription</p><% end_if %>
	<% include ProductActionsForGroup %>
</li>
