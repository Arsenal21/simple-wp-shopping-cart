<?php

namespace TTHQ\WPSC\Lib\PayPal;

class PayPal_Subsc_Billing_Plan {

    public function __construct() {
        
    }

    /*
     * Takes all the arguments and creates the params array that can be used in the create billing plan API request.
     */    
    public function construct_create_billing_plan_api_params( $plan_args ) {
        /*
        //Example $args values
        $args = array(
            'plan_name' => 'Billing plan for <product name>',
            'paypal_product_id' => PP-XYZ1234, //Created by the create_paypal_product_for_plan()
            'billing_cycles' => $billing_cycles, //Created by the construct_billing_cycles_param()
            'sub_recur_reattemp' => 1,
        );
        */
        $plan_req_params = array(
            'name'                => $plan_args['plan_name'],
            'product_id'          => $plan_args['paypal_product_id'],
            'billing_cycles'      => $plan_args['billing_cycles'],
            'payment_preferences' => array(
                    'auto_bill_outstanding'     => true,
                    'setup_fee_failure_action'  => 'CANCEL',
                    'payment_failure_threshold' => $plan_args['sub_recur_reattemp'] ? 3 : 0,
            ),
            'quantity_supported'  => true,
        );
        return $plan_req_params;
    }
    
    /*
     * Takes all the subscription arguments and creates the billing_cycles param to be used in the API request.
     */
    public function construct_billing_cycles_param( $subsc_args ) {
        /*
        //Example $subsc_args values
        $subsc_args = array(
            'currency' => 'USD',
            'sub_trial_period' => 7,
            'sub_trial_period_type' => 'D',
            'sub_trial_price' => 5.00,
            'sub_recur_period' => 1,
            'sub_recur_period_type' => 'M',
            'sub_recur_price' => 19.95,
            'sub_recur_count' => 0,
            'sub_recur_reattemp' => 3,
        );
        */

        //Setup default billing cycles, it will be overridden at the time of the subscription checkout/request.
        $billing_cycles = array();

        if (!empty($subsc_args['sub_trial_period'])) {
            $trial_period = array(
                'tenure_type' => 'TRIAL',
                'frequency' => $this->get_period_type($subsc_args['sub_trial_period_type'], $subsc_args['sub_trial_period']),
                'sequence' => 1,
                'total_cycles' => 1,
            );

            if (!empty($subsc_args['sub_trial_price'])) {
                $trial_period['pricing_scheme'] = array(
                    'fixed_price' => array(
                        'value' => $subsc_args['sub_trial_price'],
                        'currency_code' => $subsc_args['currency'],
                    ),
                );
            }

            $billing_cycles[] = json_decode(wp_json_encode($trial_period), false);
        }

        $regular_period = array(
            'tenure_type' => 'REGULAR',
            'frequency' => $this->get_period_type($subsc_args['sub_recur_period_type'], $subsc_args['sub_recur_period']),
            'sequence' => count($billing_cycles) + 1,
            'total_cycles' => absint($subsc_args['sub_recur_count']),
            'pricing_scheme' => array(
                'fixed_price' => array(
                    'value' => $subsc_args['sub_recur_price'],
                    'currency_code' => $subsc_args['currency'],
                ),
            ),
        );

        $billing_cycles[] = json_decode(wp_json_encode($regular_period), false);

        return $billing_cycles;
    }

    /**
     * Retrieves the subscription period type and interval for using in PayPal
     * request.
     *
     * @param string $type  The period type.
     * @param in     $count The period interval.
     *
     * @return array
     */
    private function get_period_type($type, $count) {

        switch ($type) {
            case 'W':
                $period = array(
                    'interval_unit' => 'WEEK',
                    'interval_count' => min(52, absint($count)),
                );
                break;
            case 'M':
                $period = array(
                    'interval_unit' => 'MONTH',
                    'interval_count' => min(12, absint($count)),
                );
                break;
            case 'Y':
                $period = array(
                    'interval_unit' => 'YEAR',
                    'interval_count' => min(1, absint($count)),
                );
                break;
            default:
                $period = array(
                    'interval_unit' => 'DAY',
                    'interval_count' => min(365, absint($count)),
                );
                break;
        }

        return $period;
    }

}
