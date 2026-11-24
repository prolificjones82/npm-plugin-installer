<?php

namespace ProlificJones\NpmInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\ProcessExecutor;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;
    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'run',
            ScriptEvents::POST_UPDATE_CMD => 'run',
        ];
    }

    public function run(Event $event): void
    {
        $extra = $this->collectConfig();
        if (!$extra) {
            return;
        }

        $dir = getcwd();
        $file = $dir . '/package.json';
        $manager = $extra['manager'];
        $process = new ProcessExecutor($this->io);

        if (!file_exists($file)) {
            $this->io->write('<info>npm-installer:</info> creating package.json');
            file_put_contents($file, json_encode(
                ['name' => basename($dir), 'private' => true],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) . "\n");
        }

        $this->install($process, $manager, $extra['packages'], false);
        $this->install($process, $manager, $extra['dev-packages'], true);

        if ($extra['package-json']) {
            $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            $data = $this->merge($data, $extra['package-json']);
            file_put_contents(
                $file,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
            );
            $this->io->write('<info>npm-installer:</info> package.json updated');
        }
    }

    /** Merges extra.npm-installer from installed packages, then the root package (which wins). */
    private function collectConfig(): array
    {
        $configs = [];
        foreach ($this->composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            if ($config = $package->getExtra()['npm-installer'] ?? null) {
                $configs[] = $config;
            }
        }
        if ($config = $this->composer->getPackage()->getExtra()['npm-installer'] ?? null) {
            $configs[] = $config;
        }
        if (!$configs) {
            return [];
        }

        $result = ['manager' => 'npm', 'packages' => [], 'dev-packages' => [], 'package-json' => []];
        foreach ($configs as $config) {
            foreach (['packages', 'dev-packages'] as $key) {
                foreach ($config[$key] ?? [] as $name => $version) {
                    if (is_int($name)) {
                        // plain "name" or "name@version" string
                        $name = $version;
                        $version = null;
                    }
                    $result[$key][$name] = $version;
                }
            }
            $result['package-json'] = $this->merge($result['package-json'], $config['package-json'] ?? []);
            $result['manager'] = $config['manager'] ?? $result['manager'];
        }

        return $result;
    }

    /** @param array<string,?string> $packages name => version (null/empty for none, or name already includes @version) */
    private function install(ProcessExecutor $process, string $manager, array $packages, bool $dev): void
    {
        if (!$packages) {
            return;
        }

        $specs = [];
        foreach ($packages as $name => $version) {
            $specs[] = $version ? $name . '@' . $version : $name;
        }

        $cmd = $manager === 'yarn'
            ? ['yarn', 'add', ...($dev ? ['--dev'] : []), ...$specs]
            : [$manager, 'install', $dev ? '--save-dev' : '--save', ...$specs];

        $this->io->write('<info>npm-installer:</info> ' . implode(' ', $cmd));
        if ($process->execute($cmd, $out, getcwd()) !== 0) {
            throw new \RuntimeException("npm-installer: '" . implode(' ', $cmd) . "' failed");
        }
    }

    /** Recursively merges associative arrays; lists and scalars are replaced. */
    private function merge(array $base, array $changes): array
    {
        foreach ($changes as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && !array_is_list($value)) {
                $base[$key] = $this->merge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
}
