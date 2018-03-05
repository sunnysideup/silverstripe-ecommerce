<% include BasicPageHeader Title='Invoice' %>


<div style="page-break-after: always;" >
    <% include Order_ShopInfo_Invoice %>
    <% with Order %>
        <% include Order %>
        <% include Order_FeedbackFormLink %>
    <% end_with %>
</div>
<script type="text/javascript">if (window ==window.top) {window.setTimeout(function(){window.print();}, 1000);}</script>


<% include BasicPageFooter %>
