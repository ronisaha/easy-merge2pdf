<?php

namespace EasyMerge2pdf;

use LogicException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Merger
{
    const MIN_QUALITY = 0;
    const MAX_QUALITY = 100;
    protected array $options;
    private array $temporaryFiles = [];
    private array $inputs = [];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);

        if (is_callable([$this, 'removeTemporaryFiles'])) {
            register_shutdown_function([$this, 'removeTemporaryFiles']);
        }
    }

    public function addInput($file, ?string $page = null): self
    {
        $this->inputs[] = InputNormalizer::normalize($file, $page);

        return $this;
    }

    public function clearInputs(): void
    {
        $this->inputs = [];
    }

    public function getInputs(): array
    {
        return $this->inputs;
    }

    public function merge($output)
    {
        copy($this->execute(), $output);
    }

    public function getContent()
    {
        return file_get_contents($this->execute());
    }

    protected function buildCommand($output = 'output.pdf'): array
    {
        if (empty($this->options['binary'])) {
            throw new LogicException('You must define a binary prior to merge.');
        }

        if (empty($this->inputs)) {
            throw new LogicException('You must define inputs to merge.');
        }

        $command = [$this->options['binary']];

        $this->applySizeOption($command);
        $this->applyMarginOption($command);
        $this->applyScaleOption($command);
        $this->applyQualityOption($command);

        $command[] = $output;

        return array_merge($command, $this->inputs);
    }

    private function executeCommand(array $command): array
    {
        $process = new Process($command);
        $process->run();

        if (null !== $this->options['timeout']) {
            $process->setTimeout($this->options['timeout']);
        }

        return [
            $process->getExitCode(),
            $process->getOutput()
        ];
    }

    private function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'tmp' => null,
            'size' => null,
            'timeout' => null,
            'auto' => false,
            'scale_height' => false,
            'scale_width' => false,
            'jpeg_quality' => 0,
        ]);

        $resolver->setAllowedValues('size', [null, 'A4', 'A3', 'Legal', 'Letter']);
        $resolver->setAllowedTypes('timeout', ['null', 'numeric']);
        $resolver->setAllowedTypes('scale_height', 'boolean');
        $resolver->setAllowedTypes('scale_width', 'boolean');
        $resolver->setAllowedTypes('jpeg_quality', 'numeric');
        $resolver->setAllowedValues('jpeg_quality', function ($value) {
            return $value >= self::MIN_QUALITY && $value <= self::MAX_QUALITY;
        });

        $resolver->define('margin');

        $resolver->setDefault('margin', function (OptionsResolver $marginResolver) {
            $marginResolver->setDefaults([
                'left' => 0,
                'right' => 0,
                'top' => 0,
                'bottom' => 0,
            ]);
            $marginResolver->setAllowedTypes('left', 'numeric');
            $marginResolver->setAllowedTypes('right', 'numeric');
            $marginResolver->setAllowedTypes('top', 'numeric');
            $marginResolver->setAllowedTypes('bottom', 'numeric');
        });

        $resolver->setDefault('binary', function (Options $options) {
            if ($options['auto']) {
                $executableFinder = new ExecutableFinder();
                return $executableFinder->find('merge2pdf', null, ['/usr/local/bin/', '/bin/', '/usr/bin/']);
            }

            return null;
        });
    }

    /**
     * @param array $command
     */
    private function applySizeOption(array &$command): void
    {
        if ($this->options['size'] !== null) {
            $this->appendOption($command, '-s', $this->options['size']);
        }
    }

    private function applyMarginOption(array &$command)
    {
        $m = $this->options['margin'];

        if ($m['left'] === 0 && $m['right'] === 0 && $m['top'] === 0 && $m['bottom'] === 0) {
            return;
        }

        $marginStr = sprintf('%s,%s,%s,%s', $m['left'], $m['right'], $m['top'], $m['bottom']);
        $this->appendOption($command, '-m', $marginStr);
    }

    private function applyScaleOption(array &$command)
    {
        if ($this->options['scale_height']) {
            $this->appendOption($command, '--scale-height');
        }

        if ($this->options['scale_width']) {
            $this->appendOption($command, '--scale-width');
        }
    }

    private function applyQualityOption(array &$command)
    {
        $quality = $this->options['jpeg_quality'];
        if ($quality > 0) {
            $this->appendOption($command, sprintf('--jpeg-quality=%s', $this->options['jpeg_quality']));
        }
    }

    /**
     * @param array $command
     * @param string $option
     * @param $value
     */
    private function appendOption(array &$command, string $option, $value = null): void
    {
        $command[] = $option;

        if ($value !== null) {
            $command[] = $value;
        }
    }

    private function getTempDir(): string
    {
        if ($this->options['tmp'] === null) {
            $this->options['tmp'] = sys_get_temp_dir();
        }

        return $this->options['tmp'];
    }

    private function getTempFile(): string
    {
        $dir = rtrim($this->getTempDir(), DIRECTORY_SEPARATOR);

        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new FileSystemRuntimeException(sprintf("Unable to create directory: %s\n", $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new FileSystemRuntimeException(sprintf("Unable to write in directory: %s\n", $dir));
        }

        return $dir . DIRECTORY_SEPARATOR . uniqid('merge2pdf', true) . ".pdf";
    }

    /**
     * @return string
     */
    private function execute(): string
    {
        $tmpFile = $this->getTempFile();
        $result = $this->executeCommand($this->buildCommand($tmpFile));

        if ($result[0] !== 0) {
            $error = json_decode($result[1], true);
            $msg = $error['message'] ?? $result[1];
            throw new CommandRuntimeException(trim($msg));
        }

        $this->temporaryFiles[] = $tmpFile;

        return $tmpFile;
    }

    /**
     * Removes all temporary files.
     */
    public function removeTemporaryFiles(): void
    {
        foreach ($this->temporaryFiles as $file) {
            $this->unlink($file);
        }

        $this->temporaryFiles = [];
    }

    /**
     * Wrapper for the "unlink" function.
     *
     * @param string $filename
     *
     * @return bool
     */
    protected function unlink(string $filename): bool
    {
        return file_exists($filename) && unlink($filename);
    }
}
