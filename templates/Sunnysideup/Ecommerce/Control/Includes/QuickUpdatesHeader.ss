<!DOCTYPE html>

<html lang="$ContentLocale">

<head>
    <% base_tag %>
    <meta name="robots" content="noindex, nofollow">
    <title>$Title &raquo; $SiteConfig.Title</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    $MetaTags(false)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Roboto', sans-serif;
        }
        body {
            padding: 0;
            margin: 0;
            color: #222;
        }
        .breadcrumbs {
            text-align: center;
        }
        header {
            background-color: #004e7f;
            padding-top: 20px;
            padding-bottom: 10px;
        }
        header * {
            margin: 0;
            padding: 0;
            color: #fff;
        }
        header, main, footer {
            clear: both;
        }
        header {
            border-bottom: 3px solid #222;
        }
        footer {
            border-top: 1px solid #999;
            background-color:  #ccc;
            padding: 20px;
            margin-top: 20px;
            min-height: 1400px;
        }
        @media only screen and (min-width: 800px) {
            footer .content {
                position: fixed;
                top: 120px;
                right: 10px;
                padding: 20px;
                border: 1px solid #999;
                background-color: #ccc;
                border-radius: 13px;
                opacity: 0.8;
            }
            footer .content:hover {
                opacity: 1;
            }
            footer .content h2 {
                margin-top: 0;
            }
            footer .content ul {
                margin-bottom: 0;
            }
            footer .content ul, footer .content li {
                list-style: none;
                padding-left: 0;
            }
        }
        li, p {
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .content
        {
            margin: 0 auto;
            max-width: 800px;

        }
        fieldset {
            border: none;
            outline: none;
            padding: 20px 0;
            margin: 0;
            background-color: #004e7f3b;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 3px;
            border: 1px solid #008a00aa;
        }
        .field {
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .level51-ajaxSelectFieldBase input[type=text] {
            width: calc(100% - 20px)!important;
            height: auto!important;
        }
        select, input, textarea {
            padding: 8px;
            border: 1px solid #ced5e1;
            width: calc(100% - 2px);
        }
        span.description {
            font-style: italic;
            font-family: serif;
            text-align: right;
            display: block;
        }

        input.action {
            border: 1px solid #000;
            color: #000;
            padding: 15px 50px;
            border-radius: 3px;
            width: auto!important;
            background-color: #ccc;
        }
        input.action:hover {
            color: #fff;
            background-color: #008a00;
        }
        .message {
            border: 1px solid #555;
            border-radius: 3px;
            padding: 10px;
            background-color: #eee;
        }
        .message.success {
            border-color: #008a00;
            color: #008a00;
        }
        .message.warning {
            border-color: goldenrod;
            color: goldenrod;
        }
        .message.error {
            border-color: red;
            color: red;
        }
        .message.required {
            color: red;
            margin-top: 0px;
            display: inline-block;
            border: none;
            border-radius: 0;
            background-color: transparent;
            font-weight: bold;
            background-color: yellow;
            margin-left: 20px;
        }
        a:link, a:visited {
            color: #222;
            text-decoration: underline;
            text-decoration-color: #ccc;
        }
        a:hover {
            color: #004e7f;
        }
        .pagination {
            text-align: center;
        }
        .pagination a, a.btn {
            background-color: #004e7f;
            display: inline-block;
            color: #fff;
            padding: 5px;
            text-decoration: none;
            margin-right: 3px;
            border-radius: 3px;

        }
        .pagination a:hover, .pagination a.current, a.btn:hover {
            background-color: #008a00;
        }

        @media print {

            .hide-in-print {
                display: none;
            }
            .content {
                width: 100%;

            }
            footer {
                display: none;
            }

        }
    </style>
</head>
<body>

    <div id="wrapper">
        <header>
            <div class="content">
                <p style="float: right; width: 4rem; font-size: 3rem;" class="hide-in-print">
                    <a href="#" onclick="window.print(); return false;" style="text-decoration: none;">🖨</a>
                </p>
                <h1>$Title</h1>
                <p>
                    $Now
                </p>
            </div>
        </header>
        <main>
            <div class="content">
