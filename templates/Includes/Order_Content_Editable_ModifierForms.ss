<% if $ModifierForms %>
    <% loop $ModifierForms %>
        <% if $ShowFormOutsideEditableOrderTable %>
            <div class="modifierFormInner">$Form</div>
        <% end_if %>
    <% end_loop %>
<% end_if %>
