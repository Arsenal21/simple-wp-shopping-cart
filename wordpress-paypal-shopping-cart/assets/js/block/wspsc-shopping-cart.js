/**
 * Shopping Cart block.
 *
 * @package wordpress-simple-paypal-shopping-cart
 */

wspsc_register_block_type(
    wspsc_sc_block_block_meta.name,
    {
        title: wspsc_sc_block_block_meta.title,
        description: wspsc_sc_block_block_meta.description,
        icon: 'cart',
        category: 'common',

        edit: function (props) {

            return [
                wspsc_element(
                    wspsc_serverSideRender,
                    {
                        key: 'wspsc-shopping-cart-block-serverSideRenderer_key', // unique key.
                        block: wspsc_sc_block_block_meta.name,
                        attributes: props.attributes,
                    }
                ),

                wspsc_element(
                    wspsc_inspector_controls,
                    {
                        key: "wspsc-shopping-cart-block-inspectorControl-key", // unique key.
                    },
                    wspsc_element(
                        'div',
                        {
                            key: "wspsc-shopping-cart-block-div-key", // unique key.
                            style: {padding: "16px",}
                        },
                        wspsc_element(
                            wspsc_select_control,
                            {
                                key: "wspsc-shopping-cart-block-selectControl-key", // unique key.
                                label: wspsc_sc_block_display_option_meta.label,
                                value: props.attributes.display_option,
                                help: wspsc_sc_block_display_option_meta.description,
                                options: wspsc_sc_block_display_option_meta.options,
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
