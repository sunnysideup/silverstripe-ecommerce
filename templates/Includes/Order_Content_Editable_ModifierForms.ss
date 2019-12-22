<% if $ModifierForms %>
    <% loop $ModifierForms %>
        <% if $Modifier.ShowFormOutsideEditableOrderTable %>
            <div class="products u-scf">$Form</div>
        <% end_if %>
    <% end_loop %>
<% end_if %>
