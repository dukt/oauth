# Create git tag
set -e

if GIT_DIR=./.git git show-ref --tags | egrep -q "refs/tags/${PLUGIN_VERSION}"

then
    echo "Found tag ${PLUGIN_VERSION}, don't create it"
else
    echo "Tag ${PLUGIN_VERSION} not found, create it"
    git tag ${PLUGIN_VERSION}
    git push --tags
fi
