<?php

namespace Aleksanm\Excel2txt;

use Aleksanm\Excel2txt\Exceptions\CouldNotExtractText;
use Aleksanm\Excel2txt\Exceptions\ExcelNotFound;
use Symfony\Component\Process\Process;

class Excel
{
    protected ?string $excel = null;

    protected ?string $file = null;

    protected string $binPath;

    protected array $options = [];

    /**
     * Exporter used by text() when the options do not name one.
     * "Text (configurable)": writes every sheet, see `ssconvert --list-exporters`.
     */
    public const DEFAULT_TEXT_EXPORTER = 'Gnumeric_stf:stf_assistant';

    public function __construct(?string $binPath = null)
    {
        $this->binPath = $binPath ?? '/usr/bin/ssconvert';
    }

    public static function getDoc(string $excel, string $file, ?string $binPath = null, array $options = []): string
    {
        return (new static($binPath))
            ->setOptions($options)
            ->setExcel($excel)
            ->setFile($file)
            ->doc();
    }

    public function setExcel(string $excel): self
    {
        if (!is_readable($excel)) {
            throw new ExcelNotFound("Excel {$excel} is not readable");
        }
        $this->excel = $excel;

        return $this;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = $this->parseOptions($options);

        return $this;
    }

    public function addOptions(array $options): self
    {
        $this->options = array_merge(
            $this->options,
            $this->parseOptions($options)
        );

        return $this;
    }

    public function parseOptions(array $options): array
    {
        $mapper = function (string $content): array {
            $content = trim($content);
            if (empty($content)) {
                return [];
            }
            if ($content[0] !== '-') {
                $content = '-'.$content;
            }
            if ($content === '-') {
                return ['-'];
            }

            return explode(' ', $content, 2);
        };

        $reducer = function (array $carry, array $option): array {
            return array_merge($carry, $option);
        };

        return array_reduce(array_map($mapper, $options), $reducer, []);
    }

    public function text(): string
    {
        $this->ensureExcelIsSet();

        // ssconvert refuses to run without an output target: send the export to stdout.
        return $this->runProcess(array_merge(
            [$this->binPath],
            $this->withExportType($this->options),
            [$this->excel, 'fd://1']
        ));
    }

    public function doc(): string
    {
        $this->ensureExcelIsSet();
        $this->ensureFileIsSet();

        $this->runProcess(array_merge([$this->binPath], $this->options, [$this->excel, $this->file]));

        return trim(file_get_contents($this->file), " \t\n\r\0\x0B\x0C");
    }

    protected function withExportType(array $options): array
    {
        foreach ($options as $option) {
            if (str_starts_with($option, '--export-type') || str_starts_with($option, '-T')) {
                return $options;
            }
        }

        return array_merge(['--export-type='.self::DEFAULT_TEXT_EXPORTER], $options);
    }

    protected function ensureExcelIsSet(): void
    {
        if ($this->excel === null) {
            throw new ExcelNotFound('No excel file has been set. Call setExcel() first.');
        }
    }

    protected function ensureFileIsSet(): void
    {
        if ($this->file === null) {
            throw new ExcelNotFound('No output file has been set. Call setFile() first.');
        }
    }

    protected function runProcess(array $command): string
    {
        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new CouldNotExtractText($process);
        }

        return trim($process->getOutput(), " \t\n\r\0\x0B\x0C");
    }
}
