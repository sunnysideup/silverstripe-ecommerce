<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
    <% base_tag %>
    $MetaTags
    <link rel="shortcut icon" href="/favicon.ico" />
    <title><% _t('PRINT_ALL_INVOICES', 'Print Invoices for Selected Orders') %></title>
</head>
<body>
    <% loop $Orders %>
        <div style="page-break-after: always;">
            <% with EcomConfig %>
                <div id="ShopInfo">
                    <% if EmailLogo %>
                        <img src="$EmailLogo.getAbsoluteURL" alt="Logo - $EmailLogo.Title" />
                    <% end_if %>
                    <h1 class="title">$InvoiceTitle</h1>
                    <% if ShopPhysicalAddress %>
                        <div id="ShopPhysicalAddress">$ShopPhysicalAddress</div>
                    <% end_if %>
                </div>
            <% end_with %>
            <% include Order %>
        </div>
        <hr class="multi-print-separator"/>
    <% end_loop %>
    <script type="text/javascript">if (window ==window.top) {window.setTimeout(function(){window.print();}, 1000);}</script>
</body>
</html>
