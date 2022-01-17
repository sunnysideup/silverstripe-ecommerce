<!DOCTYPE html>

<html lang="$ContentLocale">

<head>
    <% base_tag %>
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
        li {
            padding-bottom: 10px;
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
            border: 1px solid #008a00;
            color: #008a00;
            padding: 15px 50px;
            border-radius: 3px;
            background-color: transparent;
            width: auto!important;
        }
        input.action:hover {
            background-color: #008a00;
            color: #fff;
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
            color: #fff;
        }
        .message.error * {
            color: #fff!important;
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
