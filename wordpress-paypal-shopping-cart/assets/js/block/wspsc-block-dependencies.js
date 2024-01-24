/**
 * All Block Components Dependencies.
 *
 * @package wordpress-simple-paypal-shopping-cart
 */

const wspsc_element           = wp.element.createElement,
    wspsc_register_block_type = wp.blocks.registerBlockType,
    wspsc_serverSideRender    = wp.serverSideRender,
    wspsc_text_control        = wp.components.TextControl,
    wspsc_select_control      = wp.components.SelectControl,
    wspsc_checkbox_control    = wp.components.CheckboxControl;
    wspsc_panel               = wp.components.Panel,
    wspsc_panel_body          = wp.components.PanelBody,
    wspsc_panel_row           = wp.components.PanelRow;
    wspsc_inspector_controls  = wp.blockEditor.InspectorControls;