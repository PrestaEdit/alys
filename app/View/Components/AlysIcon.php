<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AlysIcon extends Component
{
    /** @var array<string,string> Cache pour la requête courante */
    protected static array $svgCache = [];

    /** Clés médicales connues (fichiers présents dans public/icons/medical/). */
    protected const MEDICAL_KEYS = [
        'pill', 'syringe', 'stethoscope', 'test-tube', 'blood-drop',
        'hospital', 'dna', 'microscope', 'bandage',
    ];

    public function __construct(
        public string $value,
        public string $kind = 'auto',
        public string $class = 'w-6 h-6',
    ) {}

    public function render(): View
    {
        return view('components.alys-icon');
    }

    public function svg(): string
    {
        $resolvedKind = $this->kind === 'auto'
            ? (in_array($this->value, self::MEDICAL_KEYS, true) ? 'medical' : 'twemoji')
            : $this->kind;

        $path = $resolvedKind === 'medical'
            ? public_path('icons/medical/' . $this->value . '.svg')
            : public_path('icons/twemoji/' . $this->emojiToCodepoint($this->value) . '.svg');

        if (isset(self::$svgCache[$path])) {
            return self::$svgCache[$path];
        }

        if (! is_file($path)) {
            return self::$svgCache[$path] = $this->fallbackSvg();
        }

        $content = (string) file_get_contents($path);
        // Injecter la classe CSS sur la balise <svg> racine si elle n'en a pas déjà
        $content = preg_replace(
            '/<svg\b(?![^>]*\bclass=)/',
            '<svg class="' . e($this->class) . '"',
            $content,
            1,
        );

        return self::$svgCache[$path] = $content;
    }

    protected function emojiToCodepoint(string $char): string
    {
        $codepoints = [];
        $len = mb_strlen($char, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $codepoints[] = strtolower(dechex(mb_ord(mb_substr($char, $i, 1, 'UTF-8'), 'UTF-8')));
        }
        // Retirer VS-16 (fe0f) qui n'est jamais présent dans les noms de fichier Twemoji
        $codepoints = array_values(array_filter($codepoints, fn($cp) => $cp !== 'fe0f'));
        return implode('-', $codepoints);
    }

    protected function fallbackSvg(): string
    {
        return '<svg class="alys-icon-fallback ' . e($this->class) . '" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    }
}
