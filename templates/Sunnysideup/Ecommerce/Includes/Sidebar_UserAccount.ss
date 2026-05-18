<div class="sidebarBox userAccount">
    <h2><%t SideBar.YOUR_ACCOUNT 'Your Account' %></h2>
    <p>
<% if $EcomConfig.Customer.Exists %>
    <%t SideBar.LOGGED_IN_AS 'You are logged in as' %> <% if $EcomConfig.AccountPageLink %><a href="$EcomConfig.AccountPageLink"><% end_if %>$EcomConfig.Customer.Title<% if $EcomConfig.AccountPageLink %></a><% end_if %>.
    <%t SideBar.YOU_CAN 'You can' %>
    <a href="Security/logout/"><%t SideBar.LOG_OUT 'log out' %></a>
    <%t SideBar.AT_ANY_TIME_YOUR_ORDER_IS_SAVE 'at any time; your order information will be retained for when you next log in.' %>
<% else %>
    <%t SideBar.YOU_ARE_NOT 'You are not' %> <a href="$EcommerceLogInLink"><%t SideBar.LOGGED_IN 'logged in' %></a>.
    <% if $EcomConfig.AccountPageLink %>
    <%t SideBar.YOU_CAN 'You can' %>
    <a href="{$EcomConfig.AccountPageLink}"><%t SideBar.CREATE_AN_ACCOUNT 'create an account' %></a>
    <%t SideBar.SAVE_YOUR_ORDER_DETAILS 'to save your order details.' %>
    <% end_if %>
<% end_if %>
    </p>
</div>
