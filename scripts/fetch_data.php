<?php

$dataUrl = 'https://raw.githubusercontent.com/matteocontrini/comuni-json/master/comuni.json';

// Make sure we're starting from a clean slate.
if (is_dir(__DIR__ . '/../data')) {
  die('The data/ directory already exists.');
}

// Prepare the filesystem.
mkdir(__DIR__ . '/../data');

// Fetch comuni.json data ().
echo "Download the \"comuni\" list.\n";

if (file_put_contents(__DIR__ . '/../data/comuni.json', file_get_contents($dataUrl))) {
  echo "File downloaded successfully.\n";
}
else {
  echo "File downloading failed.\n";
}