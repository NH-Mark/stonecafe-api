<?php

return [
    'kitchen_system_name' => env(
        'PRINT_KITCHEN_SYSTEM_NAME',
        'Kitchen-Printer'
    ),

    // Comma-separated source printer system names to also mirror to kitchen.
    'mirror_to_kitchen_for' => array_values(
        array_filter(
            array_map(
                'trim',
                explode(
                    ',',
                    (string) env(
                        'PRINT_MIRROR_TO_KITCHEN_FOR',
                        ''
                    )
                )
            )
        )
    ),
];
