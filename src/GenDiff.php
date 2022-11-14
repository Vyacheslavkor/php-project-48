<?php

function getInfo()
{
    $doc = <<<DOC
Generate diff
    
Usage:
    gendiff (-h|--help)
    gendiff (-v|--version)
    
Options:
    -h --help                     Show this screen
    -v --version                  Show version

DOC;

    $args = Docopt::handle($doc);
    foreach ($args as $k => $v) {
        echo $k . ': ' . json_encode($v) . PHP_EOL;
    }
}
