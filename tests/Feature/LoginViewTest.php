<?php

//EDB 10/09/26: the login page only opens for the pages the client application sends the user to

test('login_view_opens_for_email_verification', function () {
    $this->get('/email/verify')->assertRedirect(route('login'));

    $this->get(route('login'))->assertOk();
});

test('login_view_opens_for_the_email_verification_link', function () {
    $this->get('/email/verify/01M35F5RX4ADGC3CSDXXYB08DA/abc')->assertRedirect(route('login'));

    $this->get(route('login'))->assertOk();
});

test('login_view_rejects_a_direct_visit', function () {
    $this->get(route('login'))->assertForbidden();
});
