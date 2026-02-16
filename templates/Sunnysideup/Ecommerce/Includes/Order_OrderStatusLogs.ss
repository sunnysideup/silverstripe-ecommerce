<% if CustomerViewableOrderStatusLogs %>
<h2>Order Updates</h2>
<table id="StatusLogs" class="information-table">
    <tbody>
    <% loop CustomerViewableOrderStatusLogs %>
        <tr>
            <th class="left" scope="row">$Title.XML</th>
            <td class="left"><% if CustomerNote %>$CustomerNote<% else %><% _t("Order.NO_FURTHER_INFORMATION", "") %><% end_if %></td>
        </tr>
    <% end_loop %>
    </tbody>
</table>
<% end_if %>
