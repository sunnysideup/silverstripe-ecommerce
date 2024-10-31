<% if DisplayLinks %>
<div id="DisplayOptionsForList" class="close openCloseSection $AjaxDefinitions.ProductListAjaxifiedLinkClassName">
    <ul class="displayOptions">
        <% loop DisplayLinks %><li class="$FirstLast displayStyles"><a href="$Link" class="$LinkingMode"<% if $IsResetFor %> data-reset-for="$ResetFor"<% end_if %>>$Title</a></li><% end_loop %>
    </ul>
</div>
<% end_if %>
