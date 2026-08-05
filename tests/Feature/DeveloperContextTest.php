<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Presentation\ProfilePresenter;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\RequestContext;
use NewDebugBar\Tests\ProfiledApplicationEvent;
use NewDebugBar\Tests\ProfiledApplicationListener;
use NewDebugBar\Tests\ProfiledModel;

it('captures Laravel decisions lifecycle sources transactions and redacted messages', function () {
    $response = $this->get('/profiled-context', ['Accept' => 'text/html'])->assertOk();
    $stored = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));
    $profile = app(ProfilePresenter::class)->present($stored);

    expect($profile['sections']['authorization']['payload']['items'][0])
        ->ability->toBe('inspect-profile')
        ->result->toBe('allowed')
        ->handler->toBe('callback')
        ->argument_types->toBe([ProfiledModel::class])
        ->and($profile['sections']['queries']['summary'])
        ->transaction_count->toBe(2)
        ->rollback_count->toBe(1)
        ->and(array_column($profile['sections']['queries']['payload']['transactions'], 'kind'))
        ->toBe(['begin', 'rollback'])
        ->and($profile['sections']['messages']['payload']['items'][0])
        ->label->toBe('Checkout checkpoint')
        ->context->step->toBe(2)
        ->context->token->toBe('[redacted]')
        ->and($profile['sections']['views']['payload']['items'][0])
        ->data_keys->toContain('label', 'private_value')
        ->render_order->toBe(1)
        ->source->file->toBe('tests/views/context.blade.php')
        ->source->editor_url->toStartWith('vscode://file/')
        ->and($profile['sections']['lifecycle']['summary']['count'])->toBeGreaterThanOrEqual(2)
        ->and(array_column($profile['sections']['timeline']['payload']['items'], 'label'))
        ->toContain(
            'Route matching',
            'Route middleware, binding, controller and rendering',
            'Route response preparation',
            'Final response preparation',
        )
        ->not->toContain('Response preparation')
        ->and(json_encode($profile))->not->toContain('private-developer-token', 'not-collected');

    $event = collect($profile['sections']['events']['payload']['items'])
        ->firstWhere('name', ProfiledApplicationEvent::class);

    expect($event)->not->toBeNull()
        ->and($event['broadcast'])->toBeFalse()
        ->and($event['listeners'][0]['name'])->toBe(ProfiledApplicationListener::class.'@handle')
        ->and($event['listeners'][0]['source']['file'])->toBe('tests/TestCase.php')
        ->and($event['listeners'][0]['source']['editor_url'])->toStartWith('vscode://file/');
});

it('captures validation field and rule names with the rendered redirect status', function () {
    $response = $this->from('/form')->post('/profiled-validation');

    $response->assertRedirect('/form')->assertHeader('X-New-Debug-Bar-Profile');
    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));
    $validation = $profile['sections']['validation']['payload']['items'][0];

    expect($validation)
        ->fields->toBe(['email', 'name'])
        ->rules->email->toContain('Email')
        ->rules->name->toContain('Required')
        ->error_bag->toBe('signup')
        ->response_status->toBe(302)
        ->and($profile['sections']['exceptions']['summary']['count'])->toBe(0);

    Livewire::test(DebugBar::class, ['profileId' => $response->headers->get('X-New-Debug-Bar-Profile')])
        ->call('loadDetails')
        ->assertSee('2 invalid fields')
        ->assertSee('signup bag · HTTP 302')
        ->assertSee('Required');
});

it('shows authentication and session shape without identity or values', function () {
    $request = Request::create('/account');
    $user = new GenericUser(['id' => 42, 'email' => 'private@example.test']);
    $request->setUserResolver(fn () => $user);
    $session = new Store('context-test', new ArraySessionHandler(120));
    $session->start();
    $session->put('clinic_id', 99);
    $session->flash('notice', 'private flash value');
    $errors = new ViewErrorBag;
    $errors->put('signup', new MessageBag(['email' => ['Private error message']]));
    $session->put('errors', $errors);
    $request->setLaravelSession($session);

    $context = app(RequestContext::class);
    $authentication = $context->authentication($request, ['auth:web']);
    $shape = $context->session($request);

    expect($authentication)
        ->guard->toBe('web')
        ->authenticated->toBeTrue()
        ->model->toBe(GenericUser::class)
        ->identifier->toStartWith('hmac:')
        ->identifier->not->toContain('42', 'private@example.test')
        ->and($shape)
        ->present->toBeTrue()
        ->keys->toContain('clinic_id', 'notice', 'errors')
        ->flash_keys->toContain('notice')
        ->error_bag_present->toBeTrue()
        ->error_bags->toBe(['signup'])
        ->and(json_encode([$authentication, $shape]))
        ->not->toContain('private@example.test', 'private flash value', 'Private error message', '99');
});
