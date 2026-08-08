<?php

use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use NewDebugBar\Collectors\AiCollector;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\LaravelAiEventRegistrar;
use NewDebugBar\Support\Redactor;
use NewDebugBar\Tests\Fixtures\LaravelAi\Agent;
use NewDebugBar\Tests\Fixtures\LaravelAi\AgentPrompted;
use NewDebugBar\Tests\Fixtures\LaravelAi\AgentStreamed;
use NewDebugBar\Tests\Fixtures\LaravelAi\InvokingTool;
use NewDebugBar\Tests\Fixtures\LaravelAi\Manager;
use NewDebugBar\Tests\Fixtures\LaravelAi\Meta;
use NewDebugBar\Tests\Fixtures\LaravelAi\Prompt;
use NewDebugBar\Tests\Fixtures\LaravelAi\PromptingAgent;
use NewDebugBar\Tests\Fixtures\LaravelAi\QueueJob;
use NewDebugBar\Tests\Fixtures\LaravelAi\Response;
use NewDebugBar\Tests\Fixtures\LaravelAi\StreamingAgent;
use NewDebugBar\Tests\Fixtures\LaravelAi\Tool;
use NewDebugBar\Tests\Fixtures\LaravelAi\ToolInvoked;
use NewDebugBar\Tests\Fixtures\LaravelAi\Usage;

require_once __DIR__.'/../Fixtures/LaravelAiFixtures.php';

function fakeLaravelAiEventClasses(): array
{
    return [
        'prompting' => PromptingAgent::class,
        'prompted' => AgentPrompted::class,
        'streaming' => StreamingAgent::class,
        'streamed' => AgentStreamed::class,
        'invoking_tool' => InvokingTool::class,
        'tool_invoked' => ToolInvoked::class,
    ];
}

function fakeLaravelAiManager(bool $captureContent = false): ProfileManager
{
    config()->set('newdebugbar.ai.enabled', true);
    config()->set('newdebugbar.ai.capture_content', $captureContent);
    $redactor = new Redactor;
    $manager = new ProfileManager([
        new AiCollector($redactor, maxItems: 10, captureContent: $captureContent),
    ], $redactor);
    app()->instance(ProfileManager::class, $manager);

    return $manager;
}

function registerFakeLaravelAiEvents(): LaravelAiEventRegistrar
{
    $registrar = new LaravelAiEventRegistrar(
        app('events'),
        app(),
        fakeLaravelAiEventClasses(),
        Manager::class,
    );

    expect($registrar->register())->toBeTrue();

    return $registrar;
}

it('only adds the AI section when Laravel AI is available', function () {
    $profileId = $this->get('/profiled', ['Accept' => 'text/html'])
        ->assertOk()
        ->headers->get('X-NewDebugBar-Profile');
    $profile = app(ProfileStore::class)->get($profileId);

    expect(isset($profile['sections']['ai']))->toBe(LaravelAiEventRegistrar::packageAvailable());
});

it('does not register AI listeners when the optional package guard fails', function () {
    $registrar = new LaravelAiEventRegistrar(
        app('events'),
        app(),
        fakeLaravelAiEventClasses(),
        'NewDebugBar\\Tests\\Fixtures\\MissingLaravelAiManager',
    );

    expect($registrar->register())->toBeFalse();
});

it('does not register AI listeners when collection is disabled', function () {
    config()->set('newdebugbar.ai.enabled', false);
    $registrar = new LaravelAiEventRegistrar(
        app('events'),
        app(),
        fakeLaravelAiEventClasses(),
        Manager::class,
    );

    expect($registrar->register())->toBeFalse();
});

it('captures synchronous and streamed Laravel AI event lifecycles', function () {
    $manager = fakeLaravelAiManager();
    registerFakeLaravelAiEvents();
    $request = Request::create('/ai-activity');
    $manager->begin($request);
    $agent = new Agent;
    $tool = new Tool;
    $prompt = new Prompt($agent, 'private prompt', ['private attachment'], 'model-start');
    $response = new Response('private response', new Usage(12, 8, 3), new Meta('openai', 'gpt-test'));

    Event::dispatch(new PromptingAgent('sync-run', $prompt));
    Event::dispatch(new InvokingTool('sync-run', 'tool-run', $agent, $tool, ['patient' => 'private patient']));
    Event::dispatch(new ToolInvoked('sync-run', 'tool-run', $agent, $tool, ['patient' => 'private patient'], 'private result'));
    Event::dispatch(new AgentPrompted('sync-run', $prompt, $response));
    Event::dispatch(new StreamingAgent('stream-run', $prompt));
    Event::dispatch(new AgentStreamed('stream-run', $prompt, $response));

    $profile = $manager->finish($request, response('ok'));
    $section = $profile['sections']['ai'];

    expect($section['summary'])
        ->count->toBe(2)
        ->completed_count->toBe(2)
        ->streamed_count->toBe(1)
        ->tool_count->toBe(1)
        ->token_count->toBe(40)
        ->content_captured->toBeFalse()
        ->and($section['payload'])
        ->capture_scope->toBe('current_profile_only')
        ->and($section['payload']['items'][0])
        ->agent->toBe(Agent::class)
        ->provider->toBe('openai')
        ->model->toBe('gpt-test')
        ->attachment_count->toBe(1)
        ->streamed->toBeFalse()
        ->not->toHaveKeys(['prompt', 'response'])
        ->and($section['payload']['items'][0]['tools'][0])
        ->tool_class->toBe(Tool::class)
        ->not->toHaveKeys(['arguments', 'result'])
        ->and($section['payload']['items'][1]['streamed'])->toBeTrue()
        ->and(json_encode($section))->not->toContain('private prompt', 'private attachment', 'private patient', 'private result', 'private response');
});

it('does not attach queued AI work to an active HTTP request', function () {
    $manager = fakeLaravelAiManager();
    registerFakeLaravelAiEvents();
    $request = Request::create('/dispatch-job');
    $manager->begin($request);
    $prompt = new Prompt(new Agent, 'queued private prompt', [], 'queued-model');
    $response = new Response('queued private response', new Usage(5, 4), new Meta('openai', 'queued-model'));
    $job = new QueueJob;

    Event::dispatch(new JobProcessing('sync', $job));
    Event::dispatch(new PromptingAgent('queued-run', $prompt));
    Event::dispatch(new AgentPrompted('queued-run', $prompt, $response));
    Event::dispatch(new JobProcessed('sync', $job));
    Event::dispatch(new PromptingAgent('request-run', $prompt));
    Event::dispatch(new AgentPrompted('request-run', $prompt, $response));

    $profile = $manager->finish($request, response('ok'));

    expect($profile['sections']['ai']['summary']['count'])->toBe(1)
        ->and($profile['sections']['ai']['payload']['items'][0]['invocation_id'])->toBe('request-run');
});
