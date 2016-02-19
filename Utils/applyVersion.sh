#!/bin/bash

cd ./Source/oauth/

# Create Info.php with plugin version constant

cat > Info.php << EOF
<?php
namespace Craft;

define('OAUTH_VERSION', '${PLUGIN_VERSION}');

EOF