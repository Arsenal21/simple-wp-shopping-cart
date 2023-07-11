/**
 * Add to Cart Button block.
 *
 * @package wordpress-simple-paypal-shopping-cart
 */

/**
 * Renders block attribute configuration fields from an array received from server side.
 */
function wspsc_add_to_cart_btn_atts_field_sets(props) {
    let panels = [];

    for (let field_set_key in wspsc_cb_block_attrs_meta) {

        let fields = [];

        let field_sets = wspsc_cb_block_attrs_meta[field_set_key];
        let field_sets_fields = field_sets['fields'];

        for (let field_key in field_sets_fields) {
            fields.push(
                wspsc_element(
                    wspsc_test_control,
                    {
                        key: "wspsc-add-to-cart-block-text-control-"+field_set_key+"-"+field_key+"-key", // unique key.
                        label: field_sets_fields[field_key].label,
                        help: field_sets_fields[field_key].description,
                        value: props.attributes[field_key],
                        onChange: (value) => {
                            let prop_attrs = {};
                            prop_attrs[field_key] = value;
                            props.setAttributes(prop_attrs);
                        },
                    }
                )
            )
        }

        panels.push(
            wspsc_element(
                wspsc_panel_body,
                {
                    key: "wspsc-add-to-cart-block-panel-body-"+field_set_key+"-key", // unique key.
                    title: field_sets.title,
                    initialOpen: field_sets.initialOpen,
                    scrollAfterOpen: field_sets.scrollAfterOpen,
                },
                [
                    wspsc_element(
                        'p',
                        {
                            key: "wspsc-add-to-cart-block-p-"+field_set_key+"-key", // unique key.
                            className: 'wspsc_block_description_text'
                        },
                        field_sets.description
                    ),
                    wspsc_element(
                        'div',
                        {
                            key: "wspsc-add-to-cart-block-div-"+field_set_key+"-key", // unique key.
                        },
                        fields,
                    )
                ]
            )
        )

    }

    return panels;
}

wspsc_register_block_type(
    wspsc_cb_block_block_meta.name,
    {
        title: wspsc_cb_block_block_meta.title,
        description: wspsc_cb_block_block_meta.description,
        icon: 'cart',
        category: 'common',

        edit: function (props) {

            return [
                wspsc_element(
                    wspsc_serverSideRender,
                    {
                        key: "wspsc-add-to-cart-block-serverSideRender-key", // unique key.
                        block: wspsc_cb_block_block_meta.name,
                        attributes: props.attributes,
                    }
                ),

                wspsc_element(
                    wspsc_inspector_controls,
                    {
                        key: "wspsc-add-to-cart-block-inspector-controls-key", // unique key.
                    },
                    wspsc_element(
                        wspsc_panel,
                        {
                            key: "wspsc-add-to-cart-block-panel-key", // unique key.
                        },
                        wspsc_add_to_cart_btn_atts_field_sets(props),
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
