<?php
echo "Loading autoloader...\n";
require_once __DIR__ . '/vendor/autoload.php';

echo "Checking class existence...\n";
var_dump(class_exists('EDAC\\Inc\\Simplified_Summary_Integrations'));

echo "Done.\n";
