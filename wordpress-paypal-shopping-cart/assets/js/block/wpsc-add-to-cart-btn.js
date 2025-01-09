/**
 * Add to Cart Button block.
 *
 * @package wordpress-simple-paypal-shopping-cart
 */

/* global wpsc_cb_block_block_meta, wpsc_cb_block_attrs_meta */

wpsc_register_block_type(
    wpsc_cb_block_block_meta.name,
    {
        title: wpsc_cb_block_block_meta.title,
        description: wpsc_cb_block_block_meta.description,
        icon: 'cart',
        category: 'common',

        edit: function (props) {

            return [
                wpsc_element(
                    wpsc_serverSideRender,
                    {
                        key: "wpsc-add-to-cart-block-serverSideRender-key", // unique key.
                        block: wpsc_cb_block_block_meta.name,
                        attributes: props.attributes,
                    }
                ),

                wpsc_element(
                    wpsc_inspector_controls,
                    {
                        key: "wpsc-add-to-cart-block-inspector-controls-key", // unique key.
                    },
                    wpsc_element(
                        wpsc_panel,
                        {
                            key: "wpsc-add-to-cart-block-panel-key", // unique key.
                        },
                        [
                            // * PANELS goes here.
                            wpsc_element(
                                wpsc_panel_body,
                                {
                                    key: "wpsc-add-to-cart-block-panel-body-general-key", // unique key.
                                    title: wpsc_cb_block_attrs_meta['general'].title,
                                    initialOpen: wpsc_cb_block_attrs_meta['general'].initialOpen,
                                    scrollAfterOpen: wpsc_cb_block_attrs_meta['general'].scrollAfterOpen,
                                },
                                [
                                    wpsc_element(
                                        'p',
                                        {
                                            key: "wpsc-add-to-cart-block-p-general-key", // unique key.
                                            className: 'wspsc_block_description_text'
                                        },
                                        wpsc_cb_block_attrs_meta['general'].description
                                    ),
                                    wpsc_element(
                                        'div',
                                        {
                                            key: "wpsc-add-to-cart-block-div-general-key", // unique key.
                                        },
                                        [
                                            // * Fields goes here.
                                            wpsc_element(
                                                wpsc_text_control,
                                                {
                                                    key: "wpsc-add-to-cart-block-text-control-general-name-key", // unique key.
                                                    label: wpsc_cb_block_attrs_meta['general']['fields']['name'].label,
                                                    help: wpsc_cb_block_attrs_meta['general']['fields']['name'].description,
                                                    value: props.attributes['name'],
                                                    __nextHasNoMarginBottom: true,
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['name'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wpsc_element(
                                                wpsc_text_control,
                                                {
                                                    key: "wpsc-add-to-cart-block-text-control-general-price-key", // unique key.
                                                    label: wpsc_cb_block_attrs_meta['general']['fields']['price'].label,
                                                    help: wpsc_cb_block_attrs_meta['general']['fields']['price'].description,
                                                    value: props.attributes['price'],
                                                    __nextHasNoMarginBottom: true,
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['price'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wpsc_element(
                                                wpsc_text_control,
                                                {
                                                    key: "wpsc-add-to-cart-block-text-control-general-shipping-key", // unique key.
                                                    label: wpsc_cb_block_attrs_meta['general']['fields']['shipping'].label,
                                                    help: wpsc_cb_block_attrs_meta['general']['fields']['shipping'].description,
                                                    value: props.attributes['shipping'],
                                                    __nextHasNoMarginBottom: true,
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['shipping'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wpsc_element(
                                                wpsc_text_control,
                                                {
                                                    key: "wpsc-add-to-cart-block-text-control-general-button_text-key", // unique key.
                                                    label: wpsc_cb_block_attrs_meta['general']['fields']['button_text'].label,
                                                    help: wpsc_cb_block_attrs_meta['general']['fields']['button_text'].description,
                                                    value: props.attributes['button_text'],
                                                    __nextHasNoMarginBottom: true,
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['button_text'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wpsc_element(
                                                wpsc_text_control,
                                                {
                                                    key: "wpsc-add-to-cart-block-text-control-general-button_image-key", // unique key.
                                                    label: wpsc_cb_block_attrs_meta['general']['fields']['button_image'].label,
                                                    help: wpsc_cb_block_attrs_meta['general']['fields']['button_image'].description,
                                                    value: props.attributes['button_image'],
                                                    __nextHasNoMarginBottom: true,
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['button_image'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wpsc_element(
                                                wpsc_text_control,
                                                {
                                                    key: "wpsc-add-to-cart-block-text-control-general-file_url-key", // unique key.
                                                    label: wpsc_cb_block_attrs_meta['general']['fields']['file_url'].label,
                                                    help: wpsc_cb_block_attrs_meta['general']['fields']['file_url'].description,
                                                    value: props.attributes['file_url'],
                                                    __nextHasNoMarginBottom: true,
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['file_url'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wpsc_element(
                                                wpsc_checkbox_control,
                                                {
                                                    key: "wpsc-add-to-cart-block-text-control-general-digital-key", // unique key.
                                                    label: wpsc_cb_block_attrs_meta['general']['fields']['digital'].label,
                                                    help: wpsc_cb_block_attrs_meta['general']['fields']['digital'].description,
                                                    checked: props.attributes['digital'],
                                                    __nextHasNoMarginBottom: true,
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['digital'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                        ]
                                    )
                                ]
                            ),
                            wpsc_element(
                                wpsc_panel_body,
                                {
                                    key: "wpsc-add-to-cart-block-panel-body-variation-key", // unique key.
                                    title: wpsc_cb_block_attrs_meta['variation'].title,
                                    initialOpen: wpsc_cb_block_attrs_meta['variation'].initialOpen,
                                    scrollAfterOpen: wpsc_cb_block_attrs_meta['variation'].scrollAfterOpen,
                                },
                                [
                                    wpsc_element(
                                        'p',
                                        {
                                            key: "wpsc-add-to-cart-block-p-variation-key", // unique key.
                                            className: 'wspsc_block_description_text'
                                        },
                                        wpsc_cb_block_attrs_meta['variation'].description
                                    ),
                                    wpsc_element(
                                        'div',
                                        {
                                            key: "wpsc-add-to-cart-block-div-variation-key", // unique key.
                                        },
                                        [
                                            // * Fields goes here.
                                            wpsc_element(
                                                wpsc_text_control,
                                                {
                                                    key: "wpsc-add-to-cart-block-text-control-variation-var1-key", // unique key.
                                                    label: wpsc_cb_block_attrs_meta['variation']['fields']['var1'].label,
                                                    help: wpsc_cb_block_attrs_meta['variation']['fields']['var1'].description,
                                                    value: props.attributes['var1'],
                                                    __nextHasNoMarginBottom: true,
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['var1'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wpsc_element(
                                                wpsc_text_control,
                                                {
                                                    key: "wpsc-add-to-cart-block-text-control-variation-var2-key", // unique key.
                                                    label: wpsc_cb_block_attrs_meta['variation']['fields']['var2'].label,
                                                    help: wpsc_cb_block_attrs_meta['variation']['fields']['var2'].description,
                                                    value: props.attributes['var2'],
                                                    __nextHasNoMarginBottom: true,
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['var2'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wpsc_element(
                                                wpsc_text_control,
                                                {
                                                    key: "wpsc-add-to-cart-block-text-control-variation-var3-key", // unique key.
                                                    label: wpsc_cb_block_attrs_meta['variation']['fields']['var3'].label,
                                                    help: wpsc_cb_block_attrs_meta['variation']['fields']['var3'].description,
                                                    value: props.attributes['var3'],
                                                    __nextHasNoMarginBottom: true,
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['var3'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            )
                                        ]
                                    )
                                ]
                            )
                        ]
                    ),
                ),
            ];
        },

        save: function () {
            return null;
        },
    }
);

/*
* The 'ReadForm' function is called when the cart button is interacted.
* So defining an empty function prevents javascript actions from being failed in the editor screen.
*/
function ReadForm(){
    // do nothing.
}
