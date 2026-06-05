<?php

namespace Tests\Feature;

use Tests\TestCase;

class TranslationParityTest extends TestCase
{
    public function test_fr_and_en_have_identical_keys(): void
    {
        $frDir = base_path('lang/fr');
        $enDir = base_path('lang/en');

        $files = collect(glob($frDir.'/*.php'))->map(fn ($p) => basename($p));

        $this->assertNotEmpty($files, 'Aucun fichier de langue trouvé dans lang/fr');

        foreach ($files as $file) {
            $this->assertFileExists($enDir.'/'.$file, "Manque lang/en/$file");

            $fr = $this->flatten(require $frDir.'/'.$file);
            $en = $this->flatten(require $enDir.'/'.$file);

            ksort($fr);
            ksort($en);

            $this->assertSame(
                array_keys($fr),
                array_keys($en),
                "Clés divergentes dans $file"
            );
        }

        // Réciproque : pas de fichier EN orphelin
        foreach (glob($enDir.'/*.php') as $p) {
            $this->assertFileExists($frDir.'/'.basename($p), 'Fichier EN orphelin : '.basename($p));
        }
    }

    private function flatten(array $arr, string $prefix = ''): array
    {
        $out = [];
        foreach ($arr as $k => $v) {
            $key = $prefix === '' ? (string) $k : "$prefix.$k";
            if (is_array($v)) {
                $out += $this->flatten($v, $key);
            } else {
                $out[$key] = $v;
            }
        }

        return $out;
    }
}
