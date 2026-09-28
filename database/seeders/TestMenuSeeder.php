<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate all related tables first (order matters due to FKs)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('product_addon')->truncate();
        DB::table('product_variants')->truncate();
        DB::table('products')->truncate();
        DB::table('addons')->truncate();
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Kategorie
        $categories = [
            ['name' => 'Pizze',    'slug' => 'pizze',    'icon' => '🍕', 'sort_order' => 1],
            ['name' => 'Makarony', 'slug' => 'makarony', 'icon' => '🍝', 'sort_order' => 2],
            ['name' => 'Sałatki', 'slug' => 'salatki',  'icon' => '🥗', 'sort_order' => 3],
            ['name' => 'Zupy',     'slug' => 'zupy',     'icon' => '🍲', 'sort_order' => 4],
            ['name' => 'Desery',   'slug' => 'desery',   'icon' => '🍰', 'sort_order' => 5],
            ['name' => 'Napoje',   'slug' => 'napoje',   'icon' => '🥤', 'sort_order' => 6],
        ];

        foreach ($categories as &$cat) {
            $cat['is_active'] = true;
            $cat['created_at'] = now();
            $cat['updated_at'] = now();
        }
        DB::table('categories')->insert($categories);

        $catId = fn (string $slug) => DB::table('categories')->where('slug', $slug)->value('id');

        // Ingredienty dla pizz (JSON)
        $pizzaIngredients = [
            'margherita' => json_encode(['sos pomidorowy', 'mozzarella', 'świeża bazylia']),
            'pepperoni' => json_encode(['sos pomidorowy', 'mozzarella', 'pepperoni']),
            'quattro-formaggi' => json_encode(['sos pomidorowy', 'mozzarella', 'gorgonzola', 'parmezan', 'ricotta']),
            'hawajska' => json_encode(['sos pomidorowy', 'mozzarella', 'szynka', 'ananas']),
            'capricciosa' => json_encode(['sos pomidorowy', 'mozzarella', 'szynka', 'pieczarki', 'oliwki', 'karczochy']),
            'diavola' => json_encode(['sos pomidorowy', 'mozzarella', 'salami pikantne', 'chili']),
            'prosciutto' => json_encode(['sos pomidorowy', 'mozzarella', 'prosciutto crudo', 'rukola', 'parmezan']),
            'vegetariana' => json_encode(['sos pomidorowy', 'mozzarella', 'papryka', 'cukinia', 'cebula', 'oliwki', 'pomidory cherry']),
        ];

        // Ingredienty dla makaronów
        $pastaIngredients = [
            'spaghetti-bolognese' => json_encode(['spaghetti', 'mięso mielone', 'sos pomidorowy', 'parmezan']),
            'carbonara' => json_encode(['spaghetti', 'boczek', 'jajko', 'parmezan', 'czarny pieprz']),
            'penne-arrabiata' => json_encode(['penne', 'pikantny sos pomidorowy', 'czosnek', 'chili']),
            'tagliatelle-losos' => json_encode(['tagliatelle', 'łosoś', 'śmietana', 'koperek', 'kapary']),
        ];

        // Produkty: [category_slug, name, slug, description, is_available, is_featured, sort_order, ingredients_key]
        $products = [
            // Pizze
            ['pizze', 'Margherita',       'margherita',       'Sos pomidorowy, mozzarella, świeża bazylia',                                         true, true,  1, 'margherita'],
            ['pizze', 'Pepperoni',         'pepperoni',        'Sos pomidorowy, mozzarella, pepperoni',                                              true, true,  2, 'pepperoni'],
            ['pizze', 'Quattro Formaggi',  'quattro-formaggi', 'Sos pomidorowy, mozzarella, gorgonzola, parmezan, ricotta',                          true, true,  3, 'quattro-formaggi'],
            ['pizze', 'Hawajska',          'hawajska',         'Sos pomidorowy, mozzarella, szynka, ananas',                                         true, false, 4, 'hawajska'],
            ['pizze', 'Capricciosa',       'capricciosa',      'Sos pomidorowy, mozzarella, szynka, pieczarki, oliwki, karczochy',                   true, false, 5, 'capricciosa'],
            ['pizze', 'Diavola',           'diavola',          'Sos pomidorowy, mozzarella, salami pikantne, chili',                                  true, false, 6, 'diavola'],
            ['pizze', 'Prosciutto',        'prosciutto',       'Sos pomidorowy, mozzarella, prosciutto crudo, rukola, parmezan',                     true, true,  7, 'prosciutto'],
            ['pizze', 'Vegetariana',       'vegetariana',      'Sos pomidorowy, mozzarella, papryka, cukinia, cebula, oliwki, pomidory cherry',      true, false, 8, 'vegetariana'],
            // Makarony
            ['makarony', 'Spaghetti Bolognese',    'spaghetti-bolognese',  'Spaghetti z sosem mięsno-pomidorowym, parmezan',         true, true,  1, 'spaghetti-bolognese'],
            ['makarony', 'Carbonara',              'carbonara',            'Spaghetti, boczek, jajko, parmezan, czarny pieprz',      true, true,  2, 'carbonara'],
            ['makarony', 'Penne Arrabiata',        'penne-arrabiata',      'Penne, pikantny sos pomidorowy, czosnek, chili',          true, false, 3, 'penne-arrabiata'],
            ['makarony', 'Tagliatelle z łososiem', 'tagliatelle-losos',    'Tagliatelle, łosoś, śmietana, koperek, kapary',           true, false, 4, 'tagliatelle-losos'],
            // Sałatki
            ['salatki', 'Sałatka Caesar', 'salatka-caesar', 'Sałata rzymska, croutons, parmezan, sos Caesar',           true, false, 1, null],
            ['salatki', 'Sałatka grecka', 'salatka-grecka', 'Pomidory, ogórek, cebula, oliwki, feta, oliwa',             true, false, 2, null],
            ['salatki', 'Caprese',         'caprese',        'Mozzarella buffalo, pomidory, bazylia, oliwa z oliwek',    true, false, 3, null],
            // Zupy
            ['zupy', 'Zupa pomidorowa', 'zupa-pomidorowa', 'Klasyczna zupa pomidorowa z ryżem',        true, false, 1, null],
            ['zupy', 'Żurek',            'zurek',           'Żurek z białą kiełbasą i jajkiem',         true, false, 2, null],
            ['zupy', 'Zupa dnia',        'zupa-dnia',       'Zapytaj kelnera o zupę dnia',              true, false, 3, null],
            // Desery
            ['desery', 'Tiramisu',     'tiramisu',     'Klasyczne włoskie tiramisu z mascarpone',         true, true,  1, null],
            ['desery', 'Panna Cotta',  'panna-cotta',  'Panna cotta z sosem malinowym',                  true, false, 2, null],
            ['desery', 'Lody kulkowe', 'lody-kulkowe', 'Trzy gałki lodów: wanilia, czekolada, truskawka', true, false, 3, null],
            // Napoje
            ['napoje', 'Coca-Cola',        'coca-cola',         'Napój gazowany 330ml',                    true, false, 1, null],
            ['napoje', 'Sprite',            'sprite',            'Napój gazowany 330ml',                    true, false, 2, null],
            ['napoje', 'Woda mineralna',    'woda-mineralna',    'Woda mineralna niegazowana',              true, false, 3, null],
            ['napoje', 'Sok pomarańczowy',  'sok-pomaranczowy',  'Świeżo wyciskany sok pomarańczowy 300ml', true, false, 4, null],
            ['napoje', 'Piwo Peroni',       'piwo-peroni',       'Piwo włoskie',                            true, false, 5, null],
            ['napoje', 'Wino czerwone',     'wino-czerwone',     'Chianti Classico',                        true, false, 6, null],
        ];

        $productIds = [];
        $allIngredients = array_merge($pizzaIngredients, $pastaIngredients);

        foreach ($products as [$catSlug, $name, $slug, $desc, $avail, $featured, $sort, $ingKey]) {
            $productIds[$slug] = DB::table('products')->insertGetId([
                'category_id' => $catId($catSlug),
                'name' => $name,
                'slug' => $slug,
                'description' => $desc,
                'is_available' => $avail,
                'is_featured' => $featured,
                'sort_order' => $sort,
                'ingredients' => $ingKey ? ($allIngredients[$ingKey] ?? null) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Warianty - pizze (3 rozmiary)
        $pizzaSlugs = ['margherita', 'pepperoni', 'quattro-formaggi', 'hawajska', 'capricciosa', 'diavola', 'prosciutto', 'vegetariana'];
        foreach ($pizzaSlugs as $slug) {
            DB::table('product_variants')->insert([
                ['product_id' => $productIds[$slug], 'name' => 'Mała (26 cm)',    'price' => 22.00, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['product_id' => $productIds[$slug], 'name' => 'Średnia (32 cm)', 'price' => 32.00, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['product_id' => $productIds[$slug], 'name' => 'Duża (40 cm)',    'price' => 42.00, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Warianty - makarony
        $makSlugs = ['spaghetti-bolognese' => 28.00, 'carbonara' => 30.00, 'penne-arrabiata' => 26.00, 'tagliatelle-losos' => 34.00];
        foreach ($makSlugs as $slug => $price) {
            DB::table('product_variants')->insert([
                'product_id' => $productIds[$slug], 'name' => 'Porcja', 'price' => $price,
                'sort_order' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Warianty - sałatki
        $salSlugs = ['salatka-caesar' => 24.00, 'salatka-grecka' => 22.00, 'caprese' => 26.00];
        foreach ($salSlugs as $slug => $price) {
            DB::table('product_variants')->insert([
                'product_id' => $productIds[$slug], 'name' => 'Porcja', 'price' => $price,
                'sort_order' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Warianty - zupy
        $zupySlugs = ['zupa-pomidorowa' => 12.00, 'zurek' => 14.00, 'zupa-dnia' => 13.00];
        foreach ($zupySlugs as $slug => $price) {
            DB::table('product_variants')->insert([
                'product_id' => $productIds[$slug], 'name' => 'Porcja', 'price' => $price,
                'sort_order' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Warianty - desery
        DB::table('product_variants')->insert([
            ['product_id' => $productIds['tiramisu'],     'name' => 'Porcja',   'price' => 18.00, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productIds['panna-cotta'],  'name' => 'Porcja',   'price' => 16.00, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productIds['lody-kulkowe'], 'name' => '1 gałka',  'price' => 6.00, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productIds['lody-kulkowe'], 'name' => '2 gałki',  'price' => 10.00, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productIds['lody-kulkowe'], 'name' => '3 gałki',  'price' => 13.00, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Warianty - napoje
        DB::table('product_variants')->insert([
            ['product_id' => $productIds['coca-cola'],        'name' => '330 ml',           'price' => 6.00, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productIds['sprite'],           'name' => '330 ml',           'price' => 6.00, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productIds['woda-mineralna'],   'name' => '500 ml',           'price' => 5.00, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productIds['woda-mineralna'],   'name' => '1,5 l',            'price' => 8.00, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productIds['sok-pomaranczowy'], 'name' => '300 ml',           'price' => 9.00, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productIds['piwo-peroni'],      'name' => '500 ml',           'price' => 12.00, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productIds['wino-czerwone'],    'name' => 'Kieliszek 150 ml', 'price' => 22.00, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productIds['wino-czerwone'],    'name' => 'Butelka 750 ml',   'price' => 89.00, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Dodatki
        DB::table('addons')->insert([
            ['name' => 'Dodatkowy ser',   'price' => 3.00,  'is_available' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sos czosnkowy',   'price' => 2.00,  'is_available' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sos BBQ',         'price' => 2.00,  'is_available' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sos ostry',       'price' => 2.00,  'is_available' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ostra papryczka', 'price' => 1.50,  'is_available' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Oliwki',          'price' => 2.50,  'is_available' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pieczarki',       'price' => 2.50,  'is_available' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Szynka',          'price' => 4.00,  'is_available' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pepperoni',       'price' => 4.00,  'is_available' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rukola',          'price' => 2.00,  'is_available' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Pivot product_addon — pizze i makarony i sałatki dostają wszystkie dodatki
        $addonIds = DB::table('addons')->pluck('id')->toArray();
        $productsWithAddons = array_merge(
            array_values(array_intersect_key($productIds, array_flip($pizzaSlugs))),
            array_values(array_intersect_key($productIds, array_flip(array_keys($makSlugs)))),
            array_values(array_intersect_key($productIds, array_flip(array_keys($salSlugs)))),
        );

        $pivotRows = [];
        foreach ($productsWithAddons as $pid) {
            foreach ($addonIds as $aid) {
                $pivotRows[] = ['product_id' => $pid, 'addon_id' => $aid];
            }
        }
        DB::table('product_addon')->insert($pivotRows);
    }
}
