<?php

use Illuminate\Container\Container;
use Livewire\Component;
use NewDebugBar\Livewire\LivewireGateway;

function gatewayComponent(string $name): Component
{
    $component = new class extends Component {};
    $component->setId('component-source-fixture');
    $component->setName($name);

    return $component;
}

it('resolves class single-file and multi-file component sources through one fallback gateway', function () {
    $directory = sys_get_temp_dir().'/newdebugbar-livewire-source-'.bin2hex(random_bytes(6));
    $multi = $directory.'/profile-card';
    $single = $directory.'/search-box.blade.php';
    mkdir($multi, 0700, true);
    file_put_contents($multi.'/profile-card.php', '<?php');
    file_put_contents($multi.'/profile-card.blade.php', '<div></div>');
    file_put_contents($single, '<?php // Livewire single-file component');

    try {
        $container = new Container;
        $container->instance('livewire.finder', new class($multi, $single)
        {
            public function __construct(
                private readonly string $multi,
                private readonly string $single,
            ) {}

            public function resolveMultiFileComponentPath(string $name): ?string
            {
                return $name === 'profile-card' ? $this->multi : null;
            }

            public function resolveSingleFileComponentPath(string $name): ?string
            {
                return $name === 'search-box' ? $this->single : null;
            }
        });
        $gateway = new LivewireGateway($container);

        expect($gateway->componentSource(gatewayComponent('profile-card')))
            ->toBe(['path' => $multi.'/profile-card.php', 'line' => 1, 'kind' => 'multi_file'])
            ->and($gateway->componentSource(gatewayComponent('search-box')))
            ->toBe(['path' => $single, 'line' => 1, 'kind' => 'single_file']);

        $fallback = (new LivewireGateway(new Container))->componentSource(gatewayComponent('class-fixture'));

        expect($fallback)
            ->kind->toBe('class')
            ->path->toBe(__FILE__)
            ->line->toBeInt();
    } finally {
        @unlink($multi.'/profile-card.php');
        @unlink($multi.'/profile-card.blade.php');
        @unlink($single);
        @rmdir($multi);
        @rmdir($directory);
    }
});

it('falls back to class source when the internal finder contract changes', function () {
    $container = new Container;
    $container->instance('livewire.finder', new class
    {
        public function resolveMultiFileComponentPath(): never
        {
            throw new RuntimeException('Changed internal contract');
        }
    });

    $source = (new LivewireGateway($container))->componentSource(gatewayComponent('changed-contract'));

    expect($source)
        ->kind->toBe('class')
        ->path->toBe(__FILE__);
});
