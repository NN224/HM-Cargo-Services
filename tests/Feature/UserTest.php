<?php

use App\Models\User;

test('user model can be created', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class);
    expect($user->exists)->toBeTrue();
});
