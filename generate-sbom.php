<?php

$command = 'composer CycloneDX:make-sbom --output-format=json';
file_put_contents('sbom.json', shell_exec($command));
