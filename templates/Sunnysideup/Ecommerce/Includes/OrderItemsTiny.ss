<% with Order %>
    <% loop $Items %>
    <ul>
        <li style="list-style: none; margin-left: 0; padding-left: 0;">
        <span style="font-size: 120%;">☐</span> $Quantity× $Product.InternalItemID - $Title
        </li>
    </ul>
    <% end_loop %>
<% end_with %>
