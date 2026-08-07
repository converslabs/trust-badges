#!/bin/bash
#
# Publish the staged release to the WordPress.org plugin SVN repository.
#
# Run `npm run release` first: this script publishes what is in
# release/trust-badges/ and never builds anything itself.
#
#   bash scripts/svn.sh
#
set -euo pipefail

SVN_URL="https://plugins.svn.wordpress.org/trust-badges"

# Resolve everything from the script location so the script works from any cwd.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
SVN_DIR="$PLUGIN_DIR/svn"
RELEASE_DIR="$PLUGIN_DIR/release/trust-badges"

if [ ! -d "$RELEASE_DIR" ]; then
    echo "Release directory not found at $RELEASE_DIR" >&2
    echo "Run 'npm run release' first." >&2
    exit 1
fi

# The version is read from the staged plugin file, never typed by hand. Typing
# it is how v1.1.0 once shipped inside a file named v1.0.0.
VERSION="$(sed -n 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*\(.*\)$/\1/p' "$RELEASE_DIR/trust-badges.php" | head -1 | tr -d '[:space:]')"

if [ -z "$VERSION" ]; then
    echo "Could not read the version from $RELEASE_DIR/trust-badges.php" >&2
    exit 1
fi

README_VERSION="$(sed -n 's/^Stable tag:[[:space:]]*\(.*\)$/\1/p' "$RELEASE_DIR/readme.txt" | head -1 | tr -d '[:space:]')"

if [ "$VERSION" != "$README_VERSION" ]; then
    echo "Version mismatch in the staged build:" >&2
    echo "  trust-badges.php: $VERSION" >&2
    echo "  readme.txt stable tag: $README_VERSION" >&2
    exit 1
fi

if [ ! -f "$RELEASE_DIR/vendor/autoload.php" ]; then
    echo "Staged build has no vendor/autoload.php - it would fatal on activation." >&2
    echo "Run 'composer install' and rebuild." >&2
    exit 1
fi

echo "Publishing version $VERSION from $RELEASE_DIR"

if [ ! -d "$SVN_DIR" ]; then
    echo "SVN folder not found. Checking out from $SVN_URL..."
    svn checkout "$SVN_URL" "$SVN_DIR"
fi

cd "$SVN_DIR" || exit 1
svn update

echo "What type of change do you want to push? (assets/tag)"
read -r CHANGE_TYPE

if [ "$CHANGE_TYPE" = "assets" ]; then
    echo "Pushing readme.txt and .wordpress-org assets..."
    cp "$RELEASE_DIR/readme.txt" trunk/readme.txt

    # Only the filenames WordPress.org actually recognises. A blanket copy is
    # how stale, differently-named screenshots accumulate in assets/ forever.
    if [ -d "$PLUGIN_DIR/.wordpress-org" ]; then
        mkdir -p assets
        for ASSET in "$PLUGIN_DIR"/.wordpress-org/banner-*.png \
                     "$PLUGIN_DIR"/.wordpress-org/banner-*.jpg \
                     "$PLUGIN_DIR"/.wordpress-org/icon-*.png \
                     "$PLUGIN_DIR"/.wordpress-org/icon-*.svg \
                     "$PLUGIN_DIR"/.wordpress-org/screenshot-*.png \
                     "$PLUGIN_DIR"/.wordpress-org/screenshot-*.jpg; do
            [ -e "$ASSET" ] && cp "$ASSET" assets/
        done

        if [ -d "$PLUGIN_DIR/.wordpress-org/blueprints" ]; then
            mkdir -p assets/blueprints
            cp "$PLUGIN_DIR"/.wordpress-org/blueprints/*.json assets/blueprints/
        fi
    fi

    svn add --force assets trunk/readme.txt

    echo
    echo "About to commit the following:"
    svn status assets trunk/readme.txt
    echo
    echo "Press Enter to commit, or Ctrl-C to abort."
    read -r _
    svn commit -m "Update assets and readme.txt"
    exit 0
fi

if [ "$CHANGE_TYPE" = "tag" ]; then
    TAG_DIR="tags/$VERSION"

    # Re-tagging a published version is not something to do silently: the old
    # code ran `svn rm` then `svn add` on the same path, which errors and then
    # commits the deletion instead of the new tag.
    if [ -e "$TAG_DIR" ]; then
        echo "Tag $TAG_DIR already exists." >&2
        echo "Bump the version, or remove the tag deliberately with 'svn rm $TAG_DIR && svn commit'." >&2
        exit 1
    fi

    # Every item is required; skipping a missing one silently is how an
    # incomplete plugin gets published.
    FILES=(assets includes vendor composer.json readme.txt trust-badges.php)
    for ITEM in "${FILES[@]}"; do
        if [ ! -e "$RELEASE_DIR/$ITEM" ]; then
            echo "Missing from staged build: $ITEM" >&2
            exit 1
        fi
    done

    # Empty trunk (except .svn) so anything that is not part of the staged
    # build cannot survive into the release. Copying the staged items over the
    # top of whatever was already there is how a stray .wordpress-org/ or an
    # old source map ends up published.
    find trunk -mindepth 1 -maxdepth 1 ! -name '.svn' -exec rm -rf {} +

    for ITEM in "${FILES[@]}"; do
        cp -R "$RELEASE_DIR/$ITEM" trunk/
    done

    # Belt and braces: the staging step already filters these, but never let
    # them reach WordPress.org.
    find trunk \( -name '.DS_Store' -o -name 'Thumbs.db' -o -name '*.map' \
        -o -name '.git*' -o -name 'node_modules' -o -name '.wordpress-org' \) \
        -not -path '*/.svn/*' -prune -exec rm -rf {} + 2>/dev/null || true

    # Stage deletions first, then additions, so removed files are dropped.
    svn status trunk | awk '/^!/ {print $2}' | while read -r MISSING; do
        svn rm --force "$MISSING"
    done
    svn add --force trunk

    echo
    echo "About to commit the following to trunk:"
    svn status trunk | head -40
    echo
    echo "Press Enter to commit, or Ctrl-C to abort."
    read -r _

    svn commit -m "Release version $VERSION"

    # Tag from the committed trunk rather than a second local copy, so the tag
    # is guaranteed to match what was just published.
    svn copy "$SVN_URL/trunk" "$SVN_URL/$TAG_DIR" -m "Tag version $VERSION"
    svn update

    echo "Published $VERSION to trunk and tagged at $TAG_DIR"
    exit 0
fi

echo "Unknown change type. Exiting." >&2
exit 1
