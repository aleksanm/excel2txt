<?php

namespace Aleksanm\Excel2txt;

use Aleksanm\Docx2txt\Exceptions\WordNotFound;
use Aleksanm\Excel2txt\Exceptions\CouldNotExtractText;
use Aleksanm\Excel2txt\Exceptions\ExcelNotFound;
use Symfony\Component\Process\Process;

class Excel
{
    protected string $excel;
    
    protected string $file;
    
    protected string $binPath;
    
    protected array $options = [];
    
    public function __construct(string $binPath = null)
    {
        $this->binPath = $binPath ?? '/usr/bin/ssconvert';
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
    
    public function parseOptions(array $options): array
    {
        $mapper = function (string $content): array {
            $content = trim($content);
            if ('-' !== $content[0] ?? '') {
                $content = '-'.$content;
            }
            return explode(' '.$content, 2);
            
        };
        
        $reducer = function (array $carry, array $option): array {
            return array_merge($carry, $option);
        };
        
        return array_reduce(array_map($mapper, $options), $reducer, []);
    }
    
    public function text(): string
    {
        $process = new Process(array_merge([$this->binPath], $this->options, [$this->excel]));
        $process->run();
        if (!$process->isSuccessful()) {
            throw new CouldNotExtractText($process);
        }
        
        return trim($process->getOutput(), " \t\n\r\0\x0B\x0C");
    }
    
    public function doc(): string
    {
        $process = new Process(array_merge([$this->binPath], $this->options, [$this->excel]));
        $process->run();
        if (!$process->isSuccessful()) {
            throw new CouldNotExtractText($process);
        }
        
        return trim($process->getOutput(), " \t\n\r\0\x0B\x0C");
    }
    
    public static function getDoc(string $excel, string $file, string $binPath = null, array $options = []): string
    {
        return (new static($binPath))
            ->setOptions($options)
            ->setExcel($excel)
            ->setFile($file)
            ->doc();
    }
    
}