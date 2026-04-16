<?php

it('home route returns 200', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);
});

it('calendar route returns 200', function () {
    $response = $this->get(route('calendar'));
    $response->assertStatus(200);
});

it('treatments route returns 200', function () {
    $response = $this->get(route('treatments'));
    $response->assertStatus(200);
});
