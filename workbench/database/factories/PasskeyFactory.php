<?php

$factory->define(\MustafaAwami\Lara2fa\Models\Passkey::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->word,
    ];
});