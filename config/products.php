<?php
// config/products.php

function get_products_db($lang) {
    return [
        1 => [
            'id' => 1,
            'title' => $lang['p1_title'],
            'title_ar' => 'بلوزة خريفية ناعمة',
            'title_en' => 'Soft Autumn Blouse',
            'price' => '25.00',
            'old_price' => null,
            'category' => $lang['p1_cat'],
            'desc' => $lang['p1_desc'],
            'img_class' => 'bg-p1',
            'badge' => $lang['new_badge'],
            'rating' => '5.0',
            'sizes' => ['S', 'M', 'L', 'XL']
        ],
        2 => [
            'id' => 2,
            'title' => $lang['p2_title'],
            'title_ar' => 'عباية سوداء بلمسة عصرية',
            'title_en' => 'Modern Black Abaya',
            'price' => '45.00',
            'old_price' => '56.00',
            'category' => $lang['p2_cat'],
            'desc' => $lang['p2_desc'],
            'img_class' => 'bg-p2',
            'badge' => '-20%',
            'rating' => '4.8',
            'sizes' => ['52', '54', '56', '58']
        ],
        3 => [
            'id' => 3,
            'title' => $lang['p3_title'],
            'title_ar' => 'فستان سهرة مخملي أنيق',
            'title_en' => 'Elegant Velvet Evening Dress',
            'price' => '80.00',
            'old_price' => null,
            'category' => $lang['p3_cat'],
            'desc' => $lang['p3_desc'],
            'img_class' => 'bg-p3',
            'badge' => null,
            'rating' => '4.9',
            'sizes' => ['S', 'M', 'L']
        ]
    ];
}