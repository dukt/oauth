#!/bin/bash

for VERSION in "$@"

do

# Create Info.php with plugin version constant

cat > Source/oauth/Info.php << EOF
<?php

namespace Craft;

define('OAUTH_VERSION', '$VERSION');

EOF

done
