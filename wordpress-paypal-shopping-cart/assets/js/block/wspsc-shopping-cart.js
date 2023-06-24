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
                        block: wspsc_sc_block_block_meta.name,
                        attributes: props.attributes,
                    }
                ),


                wspsc_element(
                    wspsc_inspector_controls,
                    null,
                    [
                        wspsc_element(
                            'div',
                            {
                                style: {padding: "16px",}
                            },
                            [
                                wspsc_element(
                                    wspsc_toggle_control,
                                    {
                                        label: wspsc_sc_block_compact_view_meta.label,
                                        help: wspsc_sc_block_compact_view_meta.description,
                                        checked: props.attributes.compact_mode,
                                        onChange: (value) => {
                                            props.setAttributes({compact_mode: value});
                                        },
                                    }
                                ),
                            ]
                        ),
                    ]
                ),
            ];
        },

        save: function () {
            return null;
        },
    }
);
