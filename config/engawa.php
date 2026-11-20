<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 2006 Mixi Reenactment Simulation
    |--------------------------------------------------------------------------
    |
    | engawa replays the year 2006 as a living mixi-style SNS. The simulation
    | clock compresses the real campaign window [first tick .. real_end] onto
    | [sim_start .. sim_end], so every generated post carries a 2006 timestamp.
    |
    */
    'simulation' => [
        // Master switch checked by the engawa:tick command.
        'enabled' => env('ENGAWA_SIM_ENABLED', true),

        // How many persona accounts the bootstrap builds.
        'persona_target' => env('ENGAWA_SIM_PERSONAS', 1000),

        // Simulated year window (JST) every generated activity is stamped into.
        'sim_start' => '2006-01-01 00:00:00',
        'sim_end' => '2006-12-31 23:59:59',

        // Real-time campaign end (JST): ticks after this do nothing.
        'real_end' => '2027-11-11 00:00:00',

        // Persona login identities (they never actually sign in).
        'persona_email_domain' => 'persona.engawa.invalid',
    ],
];
