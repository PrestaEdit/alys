<?php

return [
    // List screen
    'title'              => 'Profils',
    'add'                => '+ Ajouter',
    'active_badge'       => '● actif',
    'no_period'          => 'Pas de période renseignée',
    'archived_section'   => 'Profils archivés (:count)',
    'unarchive'          => 'Désarchiver',

    // Edit form (inline)
    'start_date'         => 'Date de début',
    'end_date'           => 'Date de fin',

    // Medical fiche
    'medical_section'    => 'Fiche médicale',
    'weight'             => 'Poids (kg)',
    'weight_placeholder' => 'ex. 70',
    'height'             => 'Taille (cm)',
    'height_placeholder' => 'ex. 175',
    'blood_group'        => 'Groupe sanguin',

    // Create screen
    'title_create'       => 'Nouveau profil',
    'first_name'         => 'Prénom',
    'first_name_placeholder' => 'Prénom',
    'color'              => 'Couleur',
    'create_profile'     => 'Créer le profil',

    // Switcher
    'close'              => 'Fermer',
    'add_profile'        => 'Ajouter un profil',
    'manage_profiles'    => 'Gérer les profils',

    // Validation messages
    'validation_name_required'  => 'Le prénom est requis.',
    'validation_name_max'       => 'Le prénom ne peut pas dépasser 100 caractères.',
    'validation_name_unique'    => 'Un profil avec ce prénom existe déjà.',
    'validation_color_required' => 'Veuillez choisir une couleur.',
    'validation_color_in'       => "Cette couleur n'est pas autorisée.",
    'validation_start_date'     => 'La date de début doit être une date valide.',
    'validation_end_date'       => 'La date de fin doit être une date valide.',
    'validation_end_after'      => 'La date de fin doit être postérieure à la date de début.',
    'validation_weight_range'   => 'Le poids doit être compris entre 1 et 500 kg.',
    'validation_height_range'   => 'La taille doit être comprise entre 30 et 250 cm.',
    'validation_blood_group_in' => "Ce groupe sanguin n'est pas valide.",
];
