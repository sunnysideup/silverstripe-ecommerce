<address>
    <span class="nameSpan">$Prefix $FirstName $Surname</span>
    <% if $Email %><span class="emailSpan">$Email</span><% end_if %>
    <% if $Phone %><span class="phoneSpan">$Phone</span><% end_if %>
    <% if $CompanyName %><span class="addressSpan">$CompanyName</span><% end_if %>
    <% if $Address %><span class="addressSpan">$Address</span><% end_if %>
    <% if $Address2 %><span class="address2Span">$Address2</span><% end_if %>
    <% if $City %><span class="citySpan">$City</span><% end_if %>
    <% if $PostalCode %><span class="postalCodeSpan">$PostalCode</span><% end_if %>
    <% if $RegionCode %><span class="regionCodeSpan">$RegionCode</span><% end_if %>
    <% if $Region %><span class="regionCodeSpan">$Region.Name</span><% end_if %>
    <% if $FullCountryName %><span class="countrySpan">$FullCountryName</span><% end_if %>
</address>
<form class="button-form enable-after-adding-security-id" action="$RemoveLink" method="post"<% if not $AddSecurityIdToLinks %> disabled="disabled"<% end_if %> rel="$ID">
    <input type="hidden" name="SecurityID" value="<% if $AddSecurityIdToLinks %>$SecurityIDToken<% else %><% end_if %>"/>
    <button
        type="submit"
        class="button"
        title="Remove address"
    >
        <% _t("Order.REMOVETHISADDRESS", "address no longer in use.") %>
    </button>
</form>
<div class="clearer"></div>
