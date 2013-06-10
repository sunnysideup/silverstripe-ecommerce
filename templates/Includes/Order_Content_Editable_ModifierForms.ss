<% if ModifierForms %>
	<% loop ModifierForms %>
		<% if ShowFormOutsideEditableOrderTable %>
			<div class="modifierFormInner">$Me</div>
		<% end_if %>
	<% end_loop %>
<% end_if %>
