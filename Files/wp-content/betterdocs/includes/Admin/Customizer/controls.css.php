<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<style>
    .betterdocs-customize-control-title {
        margin-top: 0;
        margin-bottom: 10px;
    }
    .betterdocs-customizer-toggle {
        display:flex;
        flex-direction: row;
        justify-content: flex-start;
    }
    .betterdocs-customizer-toggle .betterdocs-customizer-toggle-title {
        flex: 2 0 0;
        vertical-align: middle;
        margin: 0;
    }
    .customize-control-betterdocs-title .betterdocs-select,
    .customize-control-betterdocs-title .betterdocs-dimension{
        display: flex;
    }
    .betterdcos-range-slider {
        margin-bottom: 10px;
    }
    .customize-control-betterdocs-range-value .customize-control-title,
    .customize-control-betterdocs-number .customize-control-title {
        float: left;
    }
    .betterdocs-customize-control-separator {
        display: block;
        margin: 0 -12px;
        border: 1px solid #ddd;
        border-left: 0;
        border-right: 0;
        padding: 15px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 2px;
        line-height: 1.3;
        text-transform: uppercase;
        color: #555;
        background-color: #fff;
    }
    .betterdocs-customize-control-separator.bohemian-layout {
        background-color:#19ca9e;
        color:#fff;
    }
    .customize-control.customize-control-betterdocs-dimension,
    .customize-control-betterdocs-select {
        width: 25%;
        float: left !important;
        clear: none !important;
        margin-top: 0 !important;
        margin-bottom: 12px;
    }
    .customize-control.customize-control-betterdocs-dimension .customize-control-title,
    .customize-control-betterdocs-select .customize-control-title{
        font-size: 11px;
        font-weight: normal;
        color: #888b8c;
        margin-top: 0;
    }
    .betterdocs-customizer-reset {
        font-size: 22px;
        margin-left: 5px;
        transition: unset;
		display: inline-block;
    }
    .betterdocs-customizer-reset svg {
        width: 16px;
        fill: #FE1F4A;
    }
    .customize-control-title .customize-control-title {
        margin-bottom: 0;
    }
    .betterdocs-alpha-color-picker .customize-control-title {
        display: block;
    }
    .betterdocs-dimension-fields li {
        float: left;
    }
    .betterdocs-dimension-fields li:first-child span {
        height: 30px;
		width: auto;
        line-height: 30px;
        font-size: 15px;
        padding-left: 5px;
        padding-right: 5px;
        border-radius: 4px;
        border: 1px solid #7e8993;
        background-color: #fff;
        cursor: pointer;
    }
    .betterdocs-dimension-fields .betterdocs-dimension-connected {
        display: none;
    }
    .betterdocs-dimension-fields .betterdocs-dimension-disconnected {
        display: block;
    }
    .betterdocs-dimension-fields .betterdocs-dimension-link.connected .betterdocs-dimension-connected {
        display: block;
        color: #0073aa;
    }
    .betterdocs-dimension-fields .betterdocs-dimension-link.connected .betterdocs-dimension-disconnected {
        display: none;
    }

    .betterdocs-dimension-fields li:not(:first-child) {
        width: 20%;
        padding-left: 5px;
    }
    .betterdocs-dimension-fields li input {
        height: 30px;
    }
    .betterdocs-dimension-fields .dimension-title {
        text-align: center;
        font-size: 11px;
        display: block;
    }
</style>
