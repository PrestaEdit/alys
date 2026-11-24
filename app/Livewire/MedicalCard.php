<?php

namespace App\Livewire;

use App\Models\Profile;
use App\Services\ActiveProfile;
use Livewire\Component;

/**
 * Carte « Fiche médicale » de l'accueil : résumé poids / taille / groupe
 * sanguin du profil actif, et feuille d'édition rapide.
 */
class MedicalCard extends Component
{
    public bool $open = false;
    public string $weight = '';
    public string $height = '';
    public ?string $bloodGroup = null;

    public function openSheet(): void
    {
        $profile = $this->profile();
        if (! $profile) {
            return;
        }

        $this->weight = $profile->weight_kg !== null ? self::inputKg((float) $profile->weight_kg) : '';
        $this->height = $profile->height_cm !== null ? (string) $profile->height_cm : '';
        $this->bloodGroup = $profile->blood_group;
        $this->resetValidation();
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetValidation();
    }

    public function stepWeight(float $delta): void
    {
        $current = str_replace(',', '.', $this->weight);
        if (! is_numeric($current)) {
            return;
        }
        $next = min(500, max(1, round((float) $current + $delta, 1)));
        $this->weight = self::inputKg($next);
    }

    public function stepHeight(int $delta): void
    {
        if (! ctype_digit(trim($this->height))) {
            return;
        }
        $this->height = (string) min(250, max(30, (int) $this->height + $delta));
    }

    /** Valeur du champ poids, avec le séparateur décimal de la langue. */
    private static function inputKg(float $kg): string
    {
        $value = (string) round($kg, 1);
        return app()->getLocale() === 'fr' ? str_replace('.', ',', $value) : $value;
    }

    public function toggleBloodGroup(string $group): void
    {
        $this->bloodGroup = $this->bloodGroup === $group ? null : $group;
    }

    public function save(): void
    {
        $profile = $this->profile();
        if (! $profile) {
            return;
        }

        // Virgule décimale acceptée (clavier FR).
        $this->weight = str_replace(',', '.', trim($this->weight));
        $this->height = trim($this->height);

        [$rules, $messages] = Profile::medicalValidation('weight', 'height', 'bloodGroup');
        $this->validate($rules, $messages);

        $profile->updateMedical(
            $this->weight !== '' ? (float) $this->weight : null,
            $this->height !== '' ? (int) $this->height : null,
            $this->bloodGroup,
        );

        $this->open = false;
    }

    private function profile(): ?Profile
    {
        $id = app(ActiveProfile::class)->id();
        return $id !== null ? Profile::find($id) : null;
    }

    public static function formatKg(float|string $kg): string
    {
        $kg = round((float) $kg, 1);
        $decimals = fmod($kg, 1.0) === 0.0 ? 0 : 1;
        $separator = app()->getLocale() === 'fr' ? ',' : '.';
        return number_format($kg, $decimals, $separator, ' ');
    }

    public function render(): \Illuminate\View\View
    {
        $profile = $this->profile();

        $entries = $profile
            ? $profile->weightEntries()->orderByDesc('measured_on')->limit(6)->get()
            : collect();

        // Écart entre les deux dernières pesées.
        $delta = null;
        if ($entries->count() >= 2) {
            $delta = [
                'kg'    => round((float) $entries[0]->weight_kg - (float) $entries[1]->weight_kg, 1),
                'since' => $entries[1]->measured_on->isoFormat('D MMM'),
            ];
        }

        return view('livewire.medical-card', [
            'profile'     => $profile,
            'entries'     => $entries,
            'delta'       => $delta,
            'bloodGroups' => Profile::BLOOD_GROUPS,
        ]);
    }
}
