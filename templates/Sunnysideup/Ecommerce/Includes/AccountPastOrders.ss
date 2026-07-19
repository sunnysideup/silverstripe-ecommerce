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
                    <% if $CopyOrderLink %>
                    <form class="button-form enable-after-adding-security-id" action="$CopyOrderLink" method="post"<% if not $AddSecurityIdToLinks %> disabled="disabled"<% end_if %>>
                        <input type="hidden" name="SecurityID" value="<% if $AddSecurityIdToLinks %>$SecurityIDToken<% else %><% end_if %>"/>
                        <button
                            type="submit"
                            class="button copy"
                            title="<% _t("Account.COPY", "Copy") %>"
                        >
                            <% _t("Account.COPY", "Copy") %>
                        </button>
                    </form>
                    <% end_if %>
                </td>
                <td class="left">
                    $CustomerStatus
                    <% if $DeleteLink %>
                    <form class="button-form enable-after-adding-security-id" action="$DeleteLink" method="post"<% if not $AddSecurityIdToLinks %> disabled="disabled"<% end_if %>>
                        <input type="hidden" name="SecurityID" value="<% if $AddSecurityIdToLinks %>$SecurityIDToken<% else %><% end_if %>"/>
                        <button
                            type="submit"
                            class="button"
                            title="<% _t("Account.REMOVE","remove") %>"
                        >
                            <% _t("Account.REMOVE","remove") %>
                        </button>
                    </form>
                    <% end_if %>
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
