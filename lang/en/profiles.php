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
];
