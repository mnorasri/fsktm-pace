<?php

test('guests are redirected from the home page to the login screen', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('the login screen renders successfully', function () {
    $this->get('/login')->assertOk();
});
