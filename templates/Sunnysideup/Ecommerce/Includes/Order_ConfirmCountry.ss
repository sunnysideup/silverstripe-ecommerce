<% if FixedCountry %>
    <p class="confirmCountry">
        <%t Order.SALESRESTRICTEDTO 'Sales are restricted to:' %>
        $FixedCountry.
    </p>
<% else %>
    <p class="confirmCountry">
        <% if ExpectedCountryName %>
        <%t Order.BASEDONASALETOCOUNTRYX 'Information below is based on a sale to: ' %>
        <span class="$AJAXDefinitions.ExpectedCountryClassName">$ExpectedCountryName</span>
        <% end_if %>
    </p>
<% end_if %>
