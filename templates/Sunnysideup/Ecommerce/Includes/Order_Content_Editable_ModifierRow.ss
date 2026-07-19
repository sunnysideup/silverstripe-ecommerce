<tr  class="$Classes hideOnZeroItems<% if HideInAjaxUpdate %> hideForNow<% end_if %>"  id="$AJAXDefinitions.TableID">
    <td colspan="3" class="firstThreeCols">
            <% if ShowFormInEditableOrderTable %>
                <div class="modifierForm">$ModifierForm</div>
            <% else %>
                <span class="tableTitle" id="$AJAXDefinitions.TableTitleID">$TableTitle</span>
                <div class="tableSubTitle" id="$AJAXDefinitions.TableSubTitleID">$TableSubTitle</div >
            <% end_if %>
        <% if MoreInfoPage %>
            <div class="moreInfoLink"><a href="$MoreInfoPage.Link" ><% _t("Order.FIND_OUT_MORE","Find out more") %></a></div>
        <% end_if %>
    </td>
    <td class="right total" id="$AJAXDefinitions.TableTotalID">$TableValueAsMoney.NiceDefaultFormat</td>
    <td class="right remove">
        <% if $CanBeRemoved %>
        <form class="button-form enable-after-adding-security-id" action="$RemoveLink" method="post"<% if not $AddSecurityIdToLinks %> disabled="disabled"<% end_if %> rel="$ID">
            <input type="hidden" name="SecurityID" value="<% if $AddSecurityIdToLinks %>$SecurityIDToken<% else %><% end_if %>"/>
            <button
                type="submit"
                class="ajaxRemoveFromCart"
                title="Remove"
            >
                <img loading="lazy" src="$resourceURL('sunnysideup/ecommerce:client/images/remove.gif')" alt="remove icon" />
            </button>
        </form>
        <% end_if %>
    </td>
</tr>
