/**
 * Product Box block.
 *
 * @package wordpress-simple-paypal-shopping-cart
 */

wspsc_register_block_type(
    wspsc_pb_block_block_meta.name,
    {
        title: wspsc_pb_block_block_meta.title,
        description: wspsc_pb_block_block_meta.description,
        icon: 'cart',
        category: 'common',

        edit: function (props) {

            return [
                wspsc_element(
                    wspsc_serverSideRender,
                    {
                        key: "wspsc-product-box-block-serverSideRender-key", // unique key.
                        block: wspsc_pb_block_block_meta.name,
                        attributes: props.attributes,
                    }
                ),

                wspsc_element(
                    wspsc_inspector_controls,
                    {
                        key: "wspsc-product-box-block-inspector-controls-key", // unique key.
                    },
                    wspsc_element(
                        wspsc_panel,
                        {
                            key: "wspsc-product-box-block-panel-key", // unique key.
                        },
                        [
                            // * PANELS goes here.
                            wspsc_element(
                                wspsc_panel_body,
                                {
                                    key: "wspsc-product-box-block-panel-general-key", // unique key.
                                    title: wspsc_pb_block_attrs_meta['general'].title,
                                    initialOpen: wspsc_pb_block_attrs_meta['general'].initialOpen,
                                    scrollAfterOpen: wspsc_pb_block_attrs_meta['general'].scrollAfterOpen,
                                },
                                [
                                    wspsc_element(
                                        'p',
                                        {
                                            key: "wspsc-product-box-block-p-general-key", // unique key.
                                            className: 'wspsc_block_description_text'
                                        },
                                        wspsc_pb_block_attrs_meta['general'].description
                                    ),
                                    wspsc_element(
                                        'div',
                                        {
                                            key: "wspsc-product-box-block-div-general-key", // unique key.
                                        },
                                        [
                                            // * Fields goes here.
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-general-name-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['general']['fields']['name'].label,
                                                    help: wspsc_pb_block_attrs_meta['general']['fields']['name'].description,
                                                    value: props.attributes['name'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['name'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-general-price-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['general']['fields']['price'].label,
                                                    help: wspsc_pb_block_attrs_meta['general']['fields']['price'].description,
                                                    value: props.attributes['price'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['price'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-general-description-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['general']['fields']['description'].label,
                                                    help: wspsc_pb_block_attrs_meta['general']['fields']['description'].description,
                                                    value: props.attributes['description'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['description'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-general-shipping-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['general']['fields']['shipping'].label,
                                                    help: wspsc_pb_block_attrs_meta['general']['fields']['shipping'].description,
                                                    value: props.attributes['shipping'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['shipping'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-general-file_url-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['general']['fields']['file_url'].label,
                                                    help: wspsc_pb_block_attrs_meta['general']['fields']['file_url'].description,
                                                    value: props.attributes['file_url'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['file_url'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-general-thumbnail-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['general']['fields']['thumbnail'].label,
                                                    help: wspsc_pb_block_attrs_meta['general']['fields']['thumbnail'].description,
                                                    value: props.attributes['thumbnail'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['thumbnail'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-general-thumb_alt-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['general']['fields']['thumb_alt'].label,
                                                    help: wspsc_pb_block_attrs_meta['general']['fields']['thumb_alt'].description,
                                                    value: props.attributes['thumb_alt'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['thumb_alt'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-general-thumb_target-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['general']['fields']['thumb_target'].label,
                                                    help: wspsc_pb_block_attrs_meta['general']['fields']['thumb_target'].description,
                                                    value: props.attributes['thumb_target'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['thumb_target'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wspsc_element(
                                                wspsc_checkbox_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-general-digital-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['general']['fields']['digital'].label,
                                                    help: wspsc_pb_block_attrs_meta['general']['fields']['digital'].description,
                                                    checked: props.attributes['digital'],
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
                            wspsc_element(
                                wspsc_panel_body,
                                {
                                    key: "wspsc-product-box-block-panel-cart-button-key", // unique key.
                                    title: wspsc_pb_block_attrs_meta['cart-button'].title,
                                    initialOpen: wspsc_pb_block_attrs_meta['cart-button'].initialOpen,
                                    scrollAfterOpen: wspsc_pb_block_attrs_meta['cart-button'].scrollAfterOpen,
                                },
                                [
                                    wspsc_element(
                                        'p',
                                        {
                                            key: "wspsc-product-box-block-p-cart-button-key", // unique key.
                                            className: 'wspsc_block_description_text'
                                        },
                                        wspsc_pb_block_attrs_meta['cart-button'].description
                                    ),
                                    wspsc_element(
                                        'div',
                                        {
                                            key: "wspsc-product-box-block-div-cart-button-key", // unique key.
                                        },
                                        [
                                            // * Fields goes here.
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-cart-button-button_text-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['cart-button']['fields']['button_text'].label,
                                                    help: wspsc_pb_block_attrs_meta['cart-button']['fields']['button_text'].description,
                                                    value: props.attributes['button_text'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['button_text'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-cart-button-button_image-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['cart-button']['fields']['button_image'].label,
                                                    help: wspsc_pb_block_attrs_meta['cart-button']['fields']['button_image'].description,
                                                    value: props.attributes['button_image'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['button_image'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            )
                                        ]
                                    )
                                ]
                            ),
                            wspsc_element(
                                wspsc_panel_body,
                                {
                                    key: "wspsc-product-box-block-panel-variation-key", // unique key.
                                    title: wspsc_pb_block_attrs_meta['variation'].title,
                                    initialOpen: wspsc_pb_block_attrs_meta['variation'].initialOpen,
                                    scrollAfterOpen: wspsc_pb_block_attrs_meta['variation'].scrollAfterOpen,
                                },
                                [
                                    wspsc_element(
                                        'p',
                                        {
                                            key: "wspsc-product-box-block-p-variation-key", // unique key.
                                            className: 'wspsc_block_description_text'
                                        },
                                        wspsc_pb_block_attrs_meta['variation'].description
                                    ),
                                    wspsc_element(
                                        'div',
                                        {
                                            key: "wspsc-product-box-block-div-variation-key", // unique key.
                                        },
                                        [
                                            // * Fields goes here.
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-variation-var1-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['variation']['fields']['var1'].label,
                                                    help: wspsc_pb_block_attrs_meta['variation']['fields']['var1'].description,
                                                    value: props.attributes['var1'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['var1'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-variation-var2-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['variation']['fields']['var2'].label,
                                                    help: wspsc_pb_block_attrs_meta['variation']['fields']['var2'].description,
                                                    value: props.attributes['var2'],
                                                    onChange: (value) => {
                                                        let prop_attrs = {};
                                                        prop_attrs['var2'] = value;
                                                        props.setAttributes(prop_attrs);
                                                    },
                                                }
                                            ),
                                            wspsc_element(
                                                wspsc_text_control,
                                                {
                                                    key: "wspsc-product-box-block-text-control-variation-var3-key", // unique key.
                                                    label: wspsc_pb_block_attrs_meta['variation']['fields']['var3'].label,
                                                    help: wspsc_pb_block_attrs_meta['variation']['fields']['var3'].description,
                                                    value: props.attributes['var3'],
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
