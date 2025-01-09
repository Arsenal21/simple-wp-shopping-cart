/**
 * Shopping Cart block.
 *
 * @package wordpress-simple-paypal-shopping-cart
 */

/* global wpsc_sc_block_block_meta, wpsc_sc_block_display_option_meta */

wpsc_register_block_type(
    wpsc_sc_block_block_meta.name,
    {
        title: wpsc_sc_block_block_meta.title,
        description: wpsc_sc_block_block_meta.description,
        icon: 'cart',
        category: 'common',

        edit: function (props) {

            return [
                wpsc_element(
                    wpsc_serverSideRender,
                    {
                        key: 'wpsc-shopping-cart-block-serverSideRenderer_key', // unique key.
                        block: wpsc_sc_block_block_meta.name,
                        attributes: props.attributes,
                    }
                ),

                wpsc_element(
                    wpsc_inspector_controls,
                    {
                        key: "wpsc-shopping-cart-block-inspectorControl-key", // unique key.
                    },
                    wpsc_element(
                        'div',
                        {
                            key: "wpsc-shopping-cart-block-div-key", // unique key.
                            style: {padding: "16px",}
                        },
                        wpsc_element(
                            wpsc_select_control,
                            {
                                key: "wpsc-shopping-cart-block-selectControl-key", // unique key.
                                label: wpsc_sc_block_display_option_meta.label,
                                value: props.attributes.display_option,
                                help: wpsc_sc_block_display_option_meta.description,
                                options: wpsc_sc_block_display_option_meta.options,
                                __nextHasNoMarginBottom: true,
                                onChange: (value) => {
                                    props.setAttributes({display_option: value});
                                    },
                                }
                            ),
                        ),
                ),
            ];
        },

        save: function () {
            return null;
        },
    }
);
