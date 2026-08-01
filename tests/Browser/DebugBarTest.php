<?php

it('opens the inspector with live request details', function () {
    $page = visit('/profiled');

    $page
        ->assertSee('Ready')
        ->click('button[aria-label="Expand inspector"]')
        ->waitForText('Runtime')
        ->assertVisible('[role="dialog"][aria-label="Request inspector"]')
        ->assertNoJavaScriptErrors();
});
