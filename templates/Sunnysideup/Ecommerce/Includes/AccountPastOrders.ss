<% if PastOrders %>
    <table>
        <thead>
            <tr>
                <th scope="col" class="left"><% _t("Account.ORDER","Order") %></th>
                <th scope="col" class="left"><% _t("Account.STATUS","Status") %></th>
                <th scope="col" class="right"><% _t("Account.TOTAL","Total") %></th>
                <th scope="col" class="right"><% _t("Account.PAID","Paid") %></th>
                <th scope="col" class="right"><% _t("Account.OUTSTANDING","Outstanding") %></th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th scope="col" class="left"><% _t("Account.TOTAL","Total") %></th>
                <th scope="col" class="left"></th>
                <th scope="col" class="right">$RunningTotal.Nice</th>
                <th scope="col" class="right">$RunningPaid.Nice</th>
                <th scope="col" class="right">$RunningOutstanding.Nice</th>
            </tr>
        </tfoot>
        <tbody>
        <% loop PastOrders %>
            <tr>
                <td class="left">
                    <a href="$Link" class="view">$Title</a>
                    <% if CopyOrderLink %><a href="$CopyOrderLink" class="copy" title="<% _t("Account.COPY", "Copy") %>">copy</a><% end_if %>
                </td>
                <td class="left">$CustomerStatus
                    <% if DeleteLink %><br /><a href="$DeleteLink"><% _t("Account.REMOVE","remove") %></a><% end_if %>
                </td>
                <td class="right">$Total.Nice</td>
                <td class="right">$TotalPaid.Nice</td>
                <td class="right">$TotalOutstanding.Nice</td>
            </tr>
        <% end_loop %>
        </tbody>
    </table>
<% else %>
    <% if AccountMember %><p class="message info noPreviousOrders"><% _t("Account.NOHISTORY","You do not have any previous orders.") %></p><% end_if %>
<% end_if %>
