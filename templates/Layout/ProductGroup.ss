<div id="ProductGroup" class="mainSection content-container withSidebar">

	<h1 id="PageTitle">$Title</h1>

	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

<% if Products %>
	<div id="Products" class="category">
		<div class="resultsBar">
			<small>
				<% if TotalCount %><span class="totalCout">$TotalCount <% _t('ProductGroup.PRODUCTSFOUND','products found.') %></span><% end_if %>
				<% if SortLinks %><span class="sortOptions"><% _t('ProductGroup.SORTBY','Sort by') %> <% control SortLinks %><a href="$Link" class="sortlink $Current">$Name</a> <% end_control %></span><% end_if %>
			</small>
		</div>
		<ul class="productList displayStyle$MyDefaultDisplayStyle">
		<% if MyDefaultDisplayStyle = Short %><% control Products %><% include ProductGroupItemShort %><% end_control %>
		<% else %><% if MyDefaultDisplayStyle = MoreDetail %><% control Products %><% include ProductGroupItemMoreDetail %><% end_control %>
		<% else %><% control Products %><% include ProductGroupItem %><% end_control %>
		<% end_if %><% end_if %>
		</ul>
	</div>
<% include ProductGroupPagination %>
<% else %>
	<p class="noProductsFound"><% _t("Product.NOPRODUCTSFOUND", "Sorry, no products could be found.") %></p>
	<% include ProductGroupChildGroups %>
<% end_if %>
	<% if Form %><div id="FormHolder">$Form</div><% end_if %>
	<% if PageComments %><div id="PageCommentsHolder">$PageComments</div><% end_if %>

</div>

<aside>
	<div id="Sidebar">
		<div class="sidebarTop"></div>
		<% include Sidebar_Cart %>
		<% include Sidebar %>
		<% include Sidebar_UserAccount %>
		<div class="sidebarBottom"></div>
	</div>
</aside>


