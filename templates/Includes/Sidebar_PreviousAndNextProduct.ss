<% if HasPreviousOrNextProduct %>
<div class="sidebarBox previousNext">
    <h3><% _t("SideBar.BROWSEPRODUCTS","Browse Products") %></h3>
    <ul>
    <% if PreviousProduct %>
        <li class="previous"><span>Previous:</span> <a href="$PreviousProduct.Link">$PreviousProduct.MenuTitle</a></li>
    <% end_if %>
        <li class="up">
            <a href="$Parent.Link">All Products</a>
        </li>
    <% if NextProduct %>
        <li class="next"><span>Next:</span> <a href="$NextProduct.Link">$NextProduct.MenuTitle</a></li>
    <% end_if %>

    </ul>
</div>
<% end_if %>
