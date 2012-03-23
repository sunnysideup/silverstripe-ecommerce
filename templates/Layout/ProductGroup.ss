<div id="Sidebar">
	<div class="sidebarTop"></div>
	<% include Sidebar_Cart %>
	<% include Sidebar %>
	<div class="sidebarBottom"></div>
</div>
<div id="ProductGroup" class="mainSection">
	<h1 id="PageTitle">$Title</h1>
	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

<% if Products %>
	<div id="Products" class="category">
		<div class="resultsBar">
			<% if SortLinks %><span class="sortOptions"><% _t('ProductGroup.SORTBY','Sort by') %> <% control SortLinks %><a href="$Link" class="sortlink $Current">$Name</a> <% end_control %></span><% end_if %>
		</div>
		<ul class="productList displayStyle$MyDefaultDisplayStyle">
		<% if MyDefaultDisplayStyle = Short %><% control Products %><% include ProductGroupItemShort %><% end_control %>
		<% else %><% if MyDefaultDisplayStyle = MoreDetail %><% control Products %><% include ProductGroupItemMoreDetail %><% end_control %>
		<% else %><% control Products %><% include ProductGroupItem %><% end_control %>
		<% end_if %><% end_if %>
		</ul>
		<div class="clear"><!-- --></div>
	</div>
<% include ProductGroupPagination %>
<% end_if %>
	<% if Form %><div id="FormHolder">$Form</div><% end_if %>
	<% if PageComments %><div id="PageCommentsHolder">$PageComments</div><% end_if %>

</div>




