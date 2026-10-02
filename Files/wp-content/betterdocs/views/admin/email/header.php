<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- HTML email body cannot use wp_enqueue_style().
// phpcs:disable PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Email clients require absolute URLs for fonts/images.
?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>BetterDocs Email Template</title>
        <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
        <style type="text/css">
            .betterdocs-email-body, .betterdocs-wrapper-body {
                font-size: 14px;
                font-family: 'Roboto', sans-serif;
            }
            .betterdocs-box-analytics-parent {
                padding: 0px 25px;
            }
            .betterdocs-table-heading {
                background-color: rgb(229, 236, 242);
            }
            .betterdocs-table-heading th:first-child {
                width: 60%;
            }
        </style>
    </head>
    <body class="betterdocs-wrapper-body" style="background-color: #fff; margin: 0; padding: 0; font-family: 'Roboto';">
        <table class="betterdocs-email-wrapper" cellpadding="50" cellspacing="0" border="0" width="100%" align="center" bgcolor="#fff">
            <tbody>
                <tr>
                    <td align="center" style="padding-top:0px;padding-bottom:20px;">
                        <table class="betterdocs-email-body" cellpadding="35" cellspacing="0" border="0" width="700" align="center" bgcolor="#FFF">
                            <tbody>