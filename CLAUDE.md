# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`aleksanm/excel2txt` is a small PHP library (not a Laravel app) that extracts text from `.xlsx` files by shelling out to the system `ssconvert` binary (part of Gnumeric), via `symfony/process`. The entire library is one class: `src/Excel.php`.

## Commands

- Install dependencies: `composer install`
- There is no test suite, linter, or build step configured in this repo.
- The library depends on `ssconvert` being present on the host (default path `/usr/bin/ssconvert`, part of the `gnumeric` package). It is not bundled or installed via Composer.

## Architecture

- `Aleksanm\Excel2txt\Excel` (`src/Excel.php`) is a fluent builder:
  - Construct with an optional custom binary path (defaults to `/usr/bin/ssconvert`).
  - `setExcel()` validates the input file is readable and throws `ExcelNotFound` otherwise.
  - `setOptions()` takes human-readable option strings (e.g. `"O=..."`) and runs them through `parseOptions()`, which normalizes each into a `-flag value` pair for `ssconvert`'s CLI.
  - `text()` / `doc()` both build and run a `Process` (`[binPath, ...options, excelPath]`), trimming the output; on failure they throw `CouldNotExtractText` (a `ProcessFailedException`). Note `text()` and `doc()` are currently identical implementations.
  - The static `getDoc()` is a one-shot convenience that chains `setOptions`/`setExcel`/`setFile`/`doc()`.
- Exceptions live in `src/Exceptions/`: `ExcelNotFound` (plain `Exception`) and `CouldNotExtractText` (extends Symfony's `ProcessFailedException`).
- Note: `Excel.php` imports `Aleksanm\Docx2txt\Exceptions\WordNotFound`, but that package is not in `composer.json` and the import is unused — likely leftover from copying a sibling library (`docx2txt`).
