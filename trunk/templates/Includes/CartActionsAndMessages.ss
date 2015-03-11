<div id="CartActionsAndMessages">
	<% if Message %><div id="CartPageMessage" class="message">$Message</div><% end_if %>
	<% if ActionLinks %>
		<div id="ActionLinksOuter">
		<h3><% _t("CartPage.NEXT", "Next ...") %></h3>
		<ul id="ActionLinks">
			<% if Title %><% loop ActionLinks %><li><a href="$Link" class="$EvenOdd action">$Title</a></li><% end_loop %><% end_if %>
		</ul>
	</div>
	<% end_if %>
</div>
