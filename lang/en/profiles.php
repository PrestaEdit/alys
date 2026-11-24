<?php

return [
    // List screen
    'title'              => 'Profiles',
    'add'                => '+ Add',
    'active_badge'       => '● active',
    'no_period'          => 'No period set',
    'archived_section'   => 'Archived profiles (:count)',
    'unarchive'          => 'Unarchive',

    // Edit form (inline)
    'start_date'         => 'Start date',
    'end_date'           => 'End date',

    // Medical fiche
    'medical_section'    => 'Medical record',
    'weight'             => 'Weight (kg)',
    'weight_placeholder' => 'e.g. 70',
    'height'             => 'Height (cm)',
    'height_placeholder' => 'e.g. 175',
    'blood_group'        => 'Blood type',
    'medical_fill'       => 'Add weight, height and blood type',
    'weight_history'     => 'Latest weigh-ins',
    'height_history'     => 'Latest measurements',
    'weight_delta'       => ':delta kg since :date',

    // Create screen
    'title_create'       => 'New profile',
    'first_name'         => 'First name',
    'first_name_placeholder' => 'First name',
    'color'              => 'Color',
    'create_profile'     => 'Create profile',

    // Switcher
    'close'              => 'Close',
    'add_profile'        => 'Add a profile',
    'manage_profiles'    => 'Manage profiles',

    // Validation messages
    'validation_name_required'  => 'First name is required.',
    'validation_name_max'       => 'First name cannot exceed 100 characters.',
    'validation_name_unique'    => 'A profile with this first name already exists.',
    'validation_color_required' => 'Please choose a color.',
    'validation_color_in'       => 'This color is not allowed.',
    'validation_start_date'     => 'The start date must be a valid date.',
    'validation_end_date'       => 'The end date must be a valid date.',
    'validation_end_after'      => 'The end date must be after the start date.',
    'validation_weight_range'   => 'Weight must be between 1 and 500 kg.',
    'validation_height_range'   => 'Height must be between 30 and 250 cm.',
    'validation_blood_group_in' => 'This blood type is not valid.',
];
