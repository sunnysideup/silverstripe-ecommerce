<div id="CartActionsAndMessages">
	<% if Message %><div id="CartPageMessage" class="message">$Message</div><% end_if %>
	<% if ActionLinks %>
		<div id="ActionLinksOuter">
		<h2><% _t("CartPage.NEXT", "Next ...") %></h2>
		<ul id="ActionLinks">
			<% if Title %><% control ActionLinks %><li><a href="$Link">$Title</a></li><% end_control %><% end_if %>
		</ul>
	</div>
	<% end_if %>
</div>
