<?php

namespace LeeJones\NpmInstaller;

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
        $extra = $this->composer->getPackage()->getExtra()['npm-installer'] ?? null;
        if (!$extra) {
            return;
        }

        $dir = getcwd();
        $file = $dir . '/package.json';
        $manager = $extra['manager'] ?? 'npm';
        $process = new ProcessExecutor($this->io);

        if (!file_exists($file)) {
            $this->io->write('<info>npm-installer:</info> creating package.json');
            file_put_contents($file, json_encode(
                ['name' => basename($dir), 'private' => true],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) . "\n");
        }

        $this->install($process, $manager, $extra['packages'] ?? [], false);
        $this->install($process, $manager, $extra['dev-packages'] ?? [], true);

        if (!empty($extra['package-json'])) {
            $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            $data = $this->merge($data, $extra['package-json']);
            file_put_contents(
                $file,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
            );
            $this->io->write('<info>npm-installer:</info> package.json updated');
        }
    }

    /** @param array<string,string>|list<string> $packages name => version, or plain names */
    private function install(ProcessExecutor $process, string $manager, array $packages, bool $dev): void
    {
        if (!$packages) {
            return;
        }

        $specs = [];
        foreach ($packages as $name => $version) {
            $specs[] = is_int($name) ? $version : $name . '@' . $version;
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
