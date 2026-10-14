<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Starters & Appetizers
            [
                'name' => 'Crispy Truffle Fries',
                'category' => 'Starters',
                'description' => 'Golden potato shoestrings tossed in white truffle oil, parmesan, and fresh parsley.',
                'price' => 8.50,
                'image_url' => 'https://images.unsplash.com/photo-1576107232684-1279f3908594?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Bruschetta al Pomodoro',
                'category' => 'Starters',
                'description' => 'Toasted sourdough topped with vine-ripened heirlooms, garlic, basil, and aged balsamic glaze.',
                'price' => 9.00,
                'image_url' => 'https://images.unsplash.com/photo-1572695157366-5e585ab2b69f?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Wood-Fired Garlic Bread',
                'category' => 'Starters',
                'description' => 'Artisanal baguette slathered with roasted garlic herb butter and melted mozzarella.',
                'price' => 7.00,
                'image_url' => 'https://images.unsplash.com/photo-1619535860434-ba1d8fa12536?auto=format&fit=crop&w=600&q=80',
            ],

            // Mains / Pizzas & Pastas
            [
                'name' => 'Classic Margherita Pizza',
                'category' => 'Mains',
                'description' => 'San Marzano tomato base, fresh buffalo mozzarella, extra virgin olive oil, and sweet basil leaves.',
                'price' => 14.50,
                'image_url' => 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Wild Mushroom Truffle Tagliatelle',
                'category' => 'Mains',
                'description' => 'Handmade ribbon pasta with sautéed forest mushrooms, garlic cream reduction, and grated pecorino.',
                'price' => 18.00,
                'image_url' => 'https://images.unsplash.com/photo-1621996346565-e3d5d62816ef?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Smoky Angus Cheeseburger',
                'category' => 'Mains',
                'description' => '200g prime Angus beef patty, smoked applewood cheddar, caramelized onions, and house remoulade on brioche.',
                'price' => 16.50,
                'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Pan-Seared Atlantic Salmon',
                'category' => 'Mains',
                'description' => 'Crispy skin salmon fillet served over lemon herb risotto with grilled asparagus spears.',
                'price' => 22.00,
                'image_url' => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=600&q=80',
            ],

            // Desserts
            [
                'name' => 'Traditional Venetian Tiramisu',
                'category' => 'Desserts',
                'description' => 'Espresso-soaked savoiardi layered with whipped mascarpone cream and dusted with Valrhona cocoa.',
                'price' => 8.50,
                'image_url' => 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Molten Dark Chocolate Lava Cake',
                'category' => 'Desserts',
                'description' => 'Warm Belgian chocolate cake with a molten center, paired with Madagascar vanilla bean gelato.',
                'price' => 9.50,
                'image_url' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=600&q=80',
            ],

            // Beverages
            [
                'name' => 'Signature Berry Mint Mojito',
                'category' => 'Beverages',
                'description' => 'Fresh crushed blackberries, lime juice, mint leaves, cane sugar, and sparkling soda.',
                'price' => 6.50,
                'image_url' => 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'name' => 'Artisanal Cold Brew Coffee',
                'category' => 'Beverages',
                'description' => 'Slow-steeped Ethiopian single-origin coffee served over ice with an orange peel twist.',
                'price' => 5.00,
                'image_url' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=600&q=80',
            ],
        ];

        foreach ($items as $item) {
            MenuItem::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}

