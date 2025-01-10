/**
 * All Block Components Dependencies.
 *
 * @package wordpress-simple-paypal-shopping-cart
 */

/* global wp*/

const wpsc_element           = wp.element.createElement,
    wpsc_register_block_type = wp.blocks.registerBlockType,
    wpsc_serverSideRender    = wp.serverSideRender,
    wpsc_text_control        = wp.components.TextControl,
    wpsc_select_control      = wp.components.SelectControl,
    wpsc_checkbox_control    = wp.components.CheckboxControl,
    wpsc_panel               = wp.components.Panel,
    wpsc_panel_body          = wp.components.PanelBody,
    wpsc_panel_row           = wp.components.PanelRow,
    wpsc_inspector_controls  = wp.blockEditor.InspectorControls;