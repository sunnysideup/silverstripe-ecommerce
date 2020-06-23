<li class="productItem $FirstLast item$Pos<% if FeaturedProduct %> featured<% end_if %>">
	<% include Sunnysideup\Ecommerce\Includes\ProductGroupItemImage %>
	<h3 class="productTitle"><a href="$Link">$Title</a></h3>
	<% if ShortDescription %><p>$ShortDescription</p><% end_if %>
	<% include Sunnysideup\Ecommerce\Includes\ProductActionsForGroup %>
</li>
