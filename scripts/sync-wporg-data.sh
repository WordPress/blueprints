#!/bin/bash
# Syncs WordPress.org plugin blueprint data from wp-public-data-analyzer

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
SOURCE_FILE="$ROOT_DIR/../wp-public-data-analyzer/plugins-with-blueprints.json"
TARGET_FILE="$ROOT_DIR/plugins-data/wporg-official.json"

if [ ! -f "$SOURCE_FILE" ]; then
    echo "Error: Source file not found: $SOURCE_FILE"
    echo "Run 'composer update-data && composer analyze:plugins-with-blueprints' in wp-public-data-analyzer first."
    exit 1
fi

cp "$SOURCE_FILE" "$TARGET_FILE"
echo "Synced $(grep -c '"name":' "$TARGET_FILE") plugins to $TARGET_FILE"
