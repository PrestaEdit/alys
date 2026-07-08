<?php

namespace App\Support;

/**
 * Source unique pour la liste des clés d'icônes médicales et le mapping
 * emoji → clé. Utilisé par :
 * - le composant <x-alys-icon> pour l'auto-detect
 * - TreatmentCreate/Edit pour la palette de choix d'icône
 * - La migration widget_icon (emoji → clé) en Task 8
 */
final class MedicalIcons
{
    /** Clés d'icônes disponibles (fichiers présents dans public/icons/medical/). */
    public const KEYS = [
        'pill', 'syringe', 'stethoscope', 'test-tube', 'blood-drop',
        'hospital', 'dna', 'microscope', 'bandage',
    ];

    /** Mapping conservateur emoji → clé médicale. Utilisé par la migration DB. */
    public const EMOJI_TO_KEY = [
        '💊' => 'pill',
        '💉' => 'syringe',
        '🩺' => 'stethoscope',
        '🧪' => 'test-tube',
        '🩸' => 'blood-drop',
        '🏥' => 'hospital',
        '🧬' => 'dna',
        '🔬' => 'microscope',
        '🩹' => 'bandage',
    ];
}
