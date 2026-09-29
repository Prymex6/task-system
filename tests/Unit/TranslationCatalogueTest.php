<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guards the translation catalogues.
 *
 * The expensive failure mode is not a missing file, it is a key that exists in
 * one language and not the other: the interface silently falls back to the raw
 * key, which reads as a bug to whoever hits it. These checks are what stop a
 * half-added translation from reaching a user.
 *
 * The scan is driven by call sites, not by language heuristics. Guessing which
 * literals are Polish by looking for diacritics misses "Status" and "Plan" and
 * and it flags prose sitting in a comment. Asking what __() is handed cannot.
 */
class TranslationCatalogueTest extends TestCase
{
    private const LOCALES = ['pl', 'en'];

    /** Every place a PHP string is handed to a person. */
    private const CALL_SITES = [
        'flash message' => "/->with\(\s*'(?:success|error|warning|info|status)'\s*,\s*(['\"])(.+?)\\1/",
        'validation error' => "/->withErrors\(\s*\[\s*'[^']+'\s*=>\s*(['\"])(.+?)\\1/",
        'abort' => "/abort\(\s*\d+\s*,\s*(['\"])(.+?)\\1/",
        'validation rule closure' => "/\\\$fail\(\s*(['\"])(.+?)\\1/",
        'mail subject' => "/->subject\(\s*(['\"])(.+?)\\1/",
    ];

    private function base(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function phpCatalogues(): array
    {
        return [
            'messages' => ['messages'],
            'client' => ['client'],
            'auth' => ['auth'],
            'validation' => ['validation'],
            'passwords' => ['passwords'],
            'pagination' => ['pagination'],
        ];
    }

    #[DataProvider('phpCatalogues')]
    public function test_a_php_catalogue_exists_in_both_languages(string $file): void
    {
        foreach (self::LOCALES as $locale) {
            $this->assertFileExists(
                $this->base() . "/lang/{$locale}/{$file}.php",
                "lang/{$locale}/{$file}.php is missing",
            );
        }
    }

    #[DataProvider('phpCatalogues')]
    public function test_a_php_catalogue_has_the_same_keys_in_both_languages(string $file): void
    {
        $keys = [];
        foreach (self::LOCALES as $locale) {
            $keys[$locale] = $this->flatten(require $this->base() . "/lang/{$locale}/{$file}.php");
        }

        $onlyPolish = array_diff($keys['pl'], $keys['en']);
        $onlyEnglish = array_diff($keys['en'], $keys['pl']);

        $this->assertSame([], array_values($onlyPolish), "Only in Polish {$file}: " . implode(', ', $onlyPolish));
        $this->assertSame([], array_values($onlyEnglish), "Only in English {$file}: " . implode(', ', $onlyEnglish));
    }

    public function test_placeholders_match_between_languages(): void
    {
        $pl = require $this->base() . '/lang/pl/messages.php';
        $en = require $this->base() . '/lang/en/messages.php';

        foreach ($pl as $key => $polish) {
            preg_match_all('/:(\w+)/', $polish, $inPolish);
            preg_match_all('/:(\w+)/', $en[$key], $inEnglish);

            sort($inPolish[1]);
            sort($inEnglish[1]);

            $this->assertSame(
                $inPolish[1],
                $inEnglish[1],
                "messages.{$key} uses different placeholders in each language",
            );
        }
    }

    public function test_no_message_is_left_hard_coded_in_php(): void
    {
        $offenders = [];

        foreach ($this->phpSources() as $path) {
            $source = file_get_contents($path);

            foreach (self::CALL_SITES as $what => $pattern) {
                preg_match_all($pattern, $source, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $offenders[] = sprintf(
                        '%s [%s] %s',
                        str_replace($this->base() . DIRECTORY_SEPARATOR, '', $path),
                        $what,
                        $match[2],
                    );
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These go straight to a user without passing through __():\n  " . implode("\n  ", $offenders),
        );
    }

    public function test_the_vue_catalogues_carry_the_same_keys(): void
    {
        $dictionaries = [];
        foreach (self::LOCALES as $locale) {
            $path = $this->base() . "/resources/js/locales/{$locale}.json";
            $this->assertFileExists($path, "resources/js/locales/{$locale}.json is missing");

            $dictionaries[$locale] = $this->flatten(
                json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)
            );
        }

        $onlyPolish = array_diff($dictionaries['pl'], $dictionaries['en']);
        $onlyEnglish = array_diff($dictionaries['en'], $dictionaries['pl']);

        $this->assertSame([], array_values($onlyPolish), 'Only in pl.json: ' . implode(', ', $onlyPolish));
        $this->assertSame([], array_values($onlyEnglish), 'Only in en.json: ' . implode(', ', $onlyEnglish));
    }

    public function test_no_vue_translation_is_left_empty(): void
    {
        foreach (self::LOCALES as $locale) {
            $tree = json_decode(
                file_get_contents($this->base() . "/resources/js/locales/{$locale}.json"),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            foreach ($tree as $namespace => $entries) {
                foreach ($entries as $key => $value) {
                    $this->assertNotSame(
                        '',
                        trim((string) $value),
                        "{$locale}.json: {$namespace}.{$key} is empty",
                    );
                }
            }
        }
    }

    /**
     * Every message has to survive vue-i18n's compiler.
     *
     * Its message syntax reserves two characters, and neither fails loudly. An
     * unescaped @ opens a linked message, and the compiler throws while the
     * page is mounting — the whole screen goes blank, with nothing but a
     * SyntaxError in the browser console to say why. A | splits plural forms,
     * so a message that starts with one renders as the empty first form.
     *
     * This is not hypothetical: 'jan@example.com' sat in the catalogue as the
     * placeholder on the sign-in field, and it blanked the staff panel outright.
     * PHP tests never caught it because they stub the front end out, which is
     * exactly why the rule is checked here rather than left to a browser.
     */
    public function test_every_message_survives_the_vue_i18n_compiler(): void
    {
        $offenders = [];

        foreach (self::LOCALES as $locale) {
            $tree = json_decode(
                file_get_contents($this->base() . "/resources/js/locales/{$locale}.json"),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            foreach ($tree as $namespace => $entries) {
                foreach ($entries as $key => $value) {
                    // {'@'} is the documented escape, so take those out first.
                    $bare = str_replace("{'@'}", '', (string) $value);

                    if (str_contains($bare, '@')) {
                        $offenders[] = "{$locale}.json: {$namespace}.{$key} has an unescaped @ — write it as {'@'}";
                    }

                    if (str_contains($bare, '|')) {
                        $offenders[] = "{$locale}.json: {$namespace}.{$key} contains |, which vue-i18n reads as a plural separator";
                    }
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n  ", $offenders));
    }

    /**
     * Every key a component asks for has to exist, or the interface shows the
     * key itself.
     */
    public function test_every_key_used_in_a_component_exists(): void
    {
        $known = $this->flatten(json_decode(
            file_get_contents($this->base() . '/resources/js/locales/pl.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        ));

        $missing = [];
        $directory = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->base() . '/resources/js')
        );

        foreach ($directory as $file) {
            if (!$file->isFile() || !in_array($file->getExtension(), ['vue', 'js'], true)) {
                continue;
            }

            preg_match_all("/\\\$?\bt\(\s*'([a-z_]+\.[a-z0-9_]+)'/i", file_get_contents($file->getPathname()), $used);

            foreach (array_unique($used[1]) as $key) {
                if (!in_array($key, $known, true)) {
                    $missing[] = $file->getBasename() . ': ' . $key;
                }
            }
        }

        $this->assertSame([], $missing, "Keys used but never defined:\n  " . implode("\n  ", $missing));
    }

    /**
     * The same rule on the front end: no Polish left sitting in a component.
     *
     * Diacritics are a reliable signal here in a way they are not in PHP,
     * because a Vue file has no Polish comments to trip over — the house style
     * keeps those in English.
     */
    public function test_no_polish_is_left_in_a_component(): void
    {
        $offenders = [];

        $directory = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->base() . '/resources/js')
        );

        foreach ($directory as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'vue') {
                continue;
            }

            $source = file_get_contents($file->getPathname());

            // Quoted literals: where a ternary branch or a label map hides
            // text the template itself never shows as a plain node.
            preg_match_all('/\'([^\'\\\\]*)\'/u', $source, $literals);

            foreach (array_unique($literals[1]) as $text) {
                if ($this->looksPolish($text)) {
                    $offenders[] = $file->getBasename() . ': ' . $text;
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Polish left hard-coded in a component:\n  " . implode("\n  ", $offenders),
        );
    }

    /**
     * Diacritics alone are not enough.
     *
     * Plenty of Polish carries no diacritic at all, and 79 strings sat in the
     * components untranslated because the first version of this check looked
     * for accented characters and nothing else. The word list below is
     * narrower than a language detector and that is the point: every word on
     * it is Polish and is not also English, so a match is never a false
     * positive.
     */
    private function looksPolish(string $text): bool
    {
        if (preg_match('/[ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]/u', $text)) {
            return true;
        }

        return (bool) preg_match(
            '/\b(Nowy|Nowa|Nowe|Edytuj|Usun|Dodaj|Zapisz|Anuluj|Wybierz|Pracownik\w*|'
            . 'Zadani\w+|Projekt\w*|Klient\w*|Faktur\w+|Wszystk\w+|Nazwa|Opis|Akcje|'
            . 'Szukaj|Zamknij|Konto|Haslo|Rola|Kwota|Razem|Wyslij|Pobierz|Drukuj|'
            . 'Zatwierdz|Odrzuc|Ustawienia|Uprawnienia)\b/u',
            $text
        );
    }

    /**
     * @param array<string, mixed> $tree
     * @return list<string>
     */
    private function flatten(array $tree, string $prefix = ''): array
    {
        $keys = [];

        foreach ($tree as $key => $value) {
            $dotted = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flatten($value, $dotted));

                continue;
            }

            $keys[] = $dotted;
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    private function phpSources(): array
    {
        $paths = [];

        foreach (['app', 'routes', 'database/seeders'] as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->base() . '/' . $directory)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $paths[] = $file->getPathname();
                }
            }
        }

        return $paths;
    }
}
