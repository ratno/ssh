# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

A minimal PHP Composer library that exposes two global helper functions for SSH command execution and HTTP POST requests. It wraps `phpseclib/phpseclib` (v3) for SSH and `guzzlehttp/guzzle` (v7) for HTTP. Requires PHP ^8.1.

## Commands

```bash
composer install       # Install dependencies
composer dump-autoload # Regenerate autoloader after changes
composer test          # Run test suite (alias: ./vendor/bin/phpunit)
```

## Architecture

All logic lives in `src/helpers.php`, registered via Composer's `files` autoload so the functions are globally available without explicit imports.

### `ssh_run($rsa_key, $username, $host, $command, $base_dir = "")`

- Authenticates via RSA key (raw key string, not a file path)
- `$command` is an array; each element is either a string or a sub-array (joined with ` && `)
- If `$base_dir` is set, every command is prefixed with `cd $base_dir && `
- Returns an array of output strings (one per command), or a login-failure string

### `request_post($url, $params)`

- Thin wrapper around Guzzle POST with `form_params`
- Returns the raw response body as a string

## Namespace

`Ratno\SSH` (PSR-4 from `src/`), though the current helpers are plain functions, not classes.
