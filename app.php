#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use Obukhov\GetYourGuideChallenge\ProductFinderCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new ProductFinderCommand());

$application->run();
