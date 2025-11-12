#!/bin/bash
# Script to test screenshot generation locally
# Usage: ./test-screenshot-locally.sh [blueprint-directory]

set -e

echo "==================================================================="
echo "WordPress Blueprint Screenshot Generation - Local Test"
echo "==================================================================="
echo ""

# Check if Playwright is installed
if ! command -v npx &> /dev/null; then
    echo "Error: npm/npx is not installed!"
    echo "Please install Node.js from https://nodejs.org/"
    exit 1
fi

echo "Checking Playwright installation..."
if ! npx playwright --version &> /dev/null; then
    echo "Playwright is not installed. Installing now..."
    npm install -g playwright
    npx playwright install chromium
else
    echo "✓ Playwright is installed: $(npx playwright --version)"
fi

echo ""

# Check if a specific blueprint directory was provided
if [ $# -eq 0 ]; then
    echo "No blueprint directory specified."
    echo "Usage: $0 <blueprint-directory>"
    echo ""
    echo "Example:"
    echo "  $0 blueprints/latest-gutenberg"
    echo ""
    echo "Or to test all blueprints:"
    echo "  python generate_screenshots.py"
    exit 1
fi

BLUEPRINT_DIR="$1"

# Check if the directory exists
if [ ! -d "$BLUEPRINT_DIR" ]; then
    echo "Error: Directory '$BLUEPRINT_DIR' does not exist!"
    exit 1
fi

# Check if blueprint.json exists
if [ ! -f "$BLUEPRINT_DIR/blueprint.json" ]; then
    echo "Error: '$BLUEPRINT_DIR/blueprint.json' does not exist!"
    exit 1
fi

echo "Testing screenshot generation for: $BLUEPRINT_DIR"
echo ""

# Run the Python script
python generate_screenshots.py "$BLUEPRINT_DIR"

# Check the results
echo ""
echo "==================================================================="
echo "Results"
echo "==================================================================="

if [ -d "$BLUEPRINT_DIR/screenshots" ]; then
    echo "✓ Screenshots directory created"
    echo ""
    echo "Generated screenshots:"
    ls -lh "$BLUEPRINT_DIR/screenshots/"
    echo ""
    
    if [ -f "$BLUEPRINT_DIR/screenshots/preview.png" ]; then
        echo "✓ preview.png created"
        echo "  Size: $(du -h "$BLUEPRINT_DIR/screenshots/preview.png" | cut -f1)"
    else
        echo "✗ preview.png not found"
    fi
    
    if [ -f "$BLUEPRINT_DIR/screenshots/wordpress.png" ]; then
        echo "✓ wordpress.png created"
        echo "  Size: $(du -h "$BLUEPRINT_DIR/screenshots/wordpress.png" | cut -f1)"
    else
        echo "✗ wordpress.png not found"
    fi
else
    echo "✗ Screenshots directory not created"
fi

echo ""
echo "==================================================================="
echo "Test complete!"
echo "==================================================================="
