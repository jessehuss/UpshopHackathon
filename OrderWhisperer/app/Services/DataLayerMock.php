<?php

namespace App\Services;

/**
 * DataLayerMock provides a mocked response payload matching the expected
 * data layer shape for the hackathon. It can return either an array or
 * a JSON string.
 */
class DataLayerMock
{
    /**
     * Build the sample payload as an associative array.
     *
     * @param array<string,mixed> $overrides Optional recursive overrides for any keys
     * @return array<string,mixed>
     */
    public function buildSamplePayloadArray(array $overrides = []): array
    {
        $base = [
            'context' => [
                'store_id' => '1205881172',
                'department_id' => '2031',
                'timezone' => 'America/Toronto',
                'as_of_utc' => '2025-08-20T10:32:11Z',
                'lookback_days' => 28,
                'history_orders' => 4,
                'history_invoices' => 4,
                'data_version' => 'orders:2025-08-20T10:30Z;invoices:2025-08-20T10:25Z',
            ],
            'items' => [
                [
                    'keys' => [
                        'item_id' => 2141122709,
                        'vendor_item_id' => 2142343082,
                        'item_number' => '433',
                        'vendor_item_number' => 'Chicken Nuggets',
                        'barcodes' => ['0034546450000'],
                    ],
                    'meta' => [
                        'description' => 'Chicken Nuggets',
                        'department_id' => 2031,
                        'uom_base' => 'EA',
                        'is_weighted' => false,
                        'case_pack' => 1,
                        'pack_uom' => 'ea/cs',
                    ],
                    'state_now' => [
                        'on_hand_units' => 8,
                        'backroom_units' => 8,
                        'on_order_units' => 0,
                        'lead_time_hours' => 24,
                        'next_delivery_utc' => '2025-08-20T17:35:00Z',
                    ],
                    'current_order' => [
                        'order_id' => 1857242243,
                        'delivery_utc' => '2025-08-20T16:30:00Z',
                        'status_code' => 8,
                        'suggested_units' => 0,
                        'ordered_units' => 1,
                        'approved_units' => 1,
                        'received_units' => 1,
                        'reason_code' => 1,
                    ],
                    'history' => [
                        'orders' => [
                            [
                                'order_id' => 1857242243,
                                'delivery_utc' => '2025-08-20T16:30:00Z',
                                'approved_units' => 1,
                                'received_units' => 1,
                                'status_code' => 8,
                            ],
                            [
                                'order_id' => 1857240000,
                                'delivery_utc' => '2025-08-18T16:30:00Z',
                                'approved_units' => 2,
                                'received_units' => 2,
                                'status_code' => 8,
                            ],
                        ],
                        'invoices' => [
                            [
                                'supplier_invoice_id' => 145925515,
                                'delivery_utc' => '2025-02-21T10:00:00Z',
                                'final_qty_cases' => 1,
                                'final_cost_total' => 30,
                                'case_qty' => 12,
                                'order_code' => '3010002600',
                                'barcode' => '0003010002600',
                            ],
                        ],
                    ],
                    'analytics' => [
                        'sales_units_7d' => 0,
                        'sales_units_14d' => 0,
                        'velocity_units_per_hr' => 0,
                        'reorder_point_units' => 6,
                        'target_on_hand_units' => 24,
                        'forecast_units_48h' => 8,
                        'stockout_risk_pct_48h' => 10,
                    ],
                    'recommendation' => [
                        'suggested_order_units' => 0,
                        'constraints' => [
                            'case_pack' => 1,
                            'min_order_units' => 1,
                            'max_order_units' => 120,
                        ],
                        'confidence_pct' => 80,
                        'notes' => 'Low movement; sufficient on hand.',
                    ],
                ],
            ],
            'rollup' => [
                'item_count' => 5,
                'ordered_units_total' => 5,
                'received_units_total' => 5,
                'suggested_units_total' => 0,
            ],
            'paging' => [
                'next_cursor' => null,
            ],
        ];

        if ($overrides === []) {
            return $base;
        }

        return $this->mergeRecursiveDistinct($base, $overrides);
    }

    /**
     * Build the sample payload as a JSON string.
     *
     * @param array<string,mixed> $overrides Optional recursive overrides
     */
    public function buildSamplePayloadJson(array $overrides = []): string
    {
        $array = $this->buildSamplePayloadArray($overrides);
        return (string) json_encode($array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Recursively merge arrays, replacing scalar values from overrides.
     *
     * @param array<mixed> $base
     * @param array<mixed> $overrides
     * @return array<mixed>
     */
    private function mergeRecursiveDistinct(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeRecursiveDistinct($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}


