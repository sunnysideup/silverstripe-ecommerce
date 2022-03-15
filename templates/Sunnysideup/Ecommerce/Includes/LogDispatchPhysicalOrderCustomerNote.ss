<% if Note %>$Note<br /><% end_if %>
<% if DispatchedOn %><strong>Dispatched On:</strong> $DispatchedOn<br /><% end_if %>
<% if DispatchTicket %><strong>Dispatch Ticket:</strong> $DispatchTicket<br /><% end_if %>
<% if DispatchLink %><strong>Track:</strong> <a href="$DispatchLink.URL"><% if DispatchTicket %>$DispatchTicket<% else %>[no ticket number]<% end_if %></a><% end_if %>
