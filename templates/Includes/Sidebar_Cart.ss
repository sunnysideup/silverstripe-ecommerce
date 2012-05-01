<div class="sidebarBox cart">
<% if Cart %><% control Cart %>
		<div id="ShoppingCart">
			<h3 id="CartHeader"><% _t("CART","Cart") %></h3>
			<p>Below are three examples of side bar carts. In your project, you can select the one that is best for you.  You can choose one per page type. </p>
			<hr /><h3>option tiny (with pop-up)</h3><hr />
			<div class="$AJAXDefinitions.TinyCartClassName"><% include CartTinyInner %></div>
			<hr /><h3>option short</h3><hr />
			<div id="$AJAXDefinitions.SmallCartID"><% include CartShortInner %></div>
			<hr /><h3>option full</h3><hr />
			<div id="$AJAXDefinitions.SideBarCartID"><% include Sidebar_Cart_Inner %></div>
		</div>
<% end_control %><% end_if %>
</div>
<% include ShoppingCartRequirements %>


