<% if Note %>$Note<br /><% end_if %>
<% if DispatchedOn %><br /><strong>Dispatched On:</strong> $DispatchedOn<% end_if %>
<% if DispatchTicket %><br /><strong>Dispatch Ticket:</strong> $DispatchTicket<% end_if %>
<% if DispatchLink %><br /><strong>Track:</strong> <a href="$DispatchLink.URL"><% if DispatchTicket %>$DispatchTicket<% else %>[no ticket number]<% end_if %></a><% end_if %>
