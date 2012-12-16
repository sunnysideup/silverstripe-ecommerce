<div id="CartActionsAndMessages">
	<% if Message %><div id="CartPageMessage" class="message">$Message</div><% end_if %>
	<% if ActionLinks %>
		<div id="ActionLinksOuter">
		<h3><% _t("CartPage.NEXT", "Next ...") %></h3>
		<ul id="ActionLinks">
			<% if Title %><% control ActionLinks %><li><a href="$Link" class="action $EvenOdd">$Title</a></li><% end_control %><% end_if %>
		</ul>
	</div>
	<% end_if %>
</div>
