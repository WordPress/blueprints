#!/usr/bin/env python3
"""
Script to generate screenshots for WordPress Blueprints.

This script captures screenshots of WordPress Playground instances running
each blueprint. Screenshots are saved in a 'screenshots' subdirectory within
each blueprint's directory.

Usage:
    python generate_screenshots.py [blueprint_dir1] [blueprint_dir2] ...
    
If no directories are provided, all blueprints will be processed.
"""

import os
import sys
import json
import subprocess
import time
from urllib.parse import quote
from pathlib import Path


def get_blueprint_directories(specific_dirs=None):
    """
    Get list of blueprint directories to process.
    
    Args:
        specific_dirs: List of specific directories to process, or None for all
        
    Returns:
        List of blueprint directory paths
    """
    if specific_dirs:
        return [d for d in specific_dirs if os.path.isdir(d)]
    
    blueprint_dirs = []
    blueprints_root = 'blueprints'
    
    if not os.path.exists(blueprints_root):
        return []
    
    for item in os.listdir(blueprints_root):
        item_path = os.path.join(blueprints_root, item)
        blueprint_json = os.path.join(item_path, 'blueprint.json')
        
        if os.path.isdir(item_path) and os.path.exists(blueprint_json):
            blueprint_dirs.append(item_path)
    
    return sorted(blueprint_dirs)


def get_current_branch():
    """Get the current git branch name."""
    try:
        branch = os.environ.get('GITHUB_HEAD_REF') or \
                 os.environ.get('GITHUB_REF_NAME') or \
                 subprocess.check_output(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], 
                                       stderr=subprocess.DEVNULL).decode().strip()
        return branch
    except:
        return 'trunk'


def generate_screenshot_for_blueprint(blueprint_dir):
    """
    Generate screenshot for a single blueprint.
    
    Args:
        blueprint_dir: Path to the blueprint directory
        
    Returns:
        True if successful, False otherwise
    """
    blueprint_path = os.path.join(blueprint_dir, 'blueprint.json')
    
    if not os.path.exists(blueprint_path):
        print(f"Error: {blueprint_path} does not exist")
        return False
    
    # Read blueprint metadata
    try:
        with open(blueprint_path, 'r') as f:
            blueprint_data = json.load(f)
            meta = blueprint_data.get('meta', {})
            title = meta.get('title', os.path.basename(blueprint_dir))
    except Exception as e:
        print(f"Error reading {blueprint_path}: {e}")
        return False
    
    print(f"\n{'='*60}")
    print(f"Processing: {title}")
    print(f"Directory: {blueprint_dir}")
    print(f"{'='*60}")
    
    # Construct the Playground URL
    branch = get_current_branch()
    raw_url = f"https://raw.githubusercontent.com/wordpress/blueprints/{branch}/{blueprint_path}"
    playground_url = f"https://playground.wordpress.net/?blueprint-url={quote(raw_url)}"
    
    print(f"Blueprint URL: {raw_url}")
    print(f"Playground URL: {playground_url}")
    
    # Create screenshots directory
    screenshots_dir = os.path.join(blueprint_dir, 'screenshots')
    os.makedirs(screenshots_dir, exist_ok=True)
    
    # Screenshot paths
    preview_screenshot = os.path.join(screenshots_dir, 'preview.png')
    wordpress_screenshot = os.path.join(screenshots_dir, 'wordpress.png')
    
    # Create a Playwright script to capture the screenshot
    playwright_script = f"""
const {{ chromium }} = require('playwright');

(async () => {{
    const browser = await chromium.launch({{
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    }});
    
    const context = await browser.newContext({{
        viewport: {{ width: 1280, height: 720 }}
    }});
    
    const page = await context.newPage();
    
    try {{
        console.log('Loading Playground...');
        await page.goto('{playground_url}', {{ 
            waitUntil: 'networkidle',
            timeout: 120000
        }});
        
        // Wait for the WordPress Playground iframe
        console.log('Waiting for WordPress Playground...');
        await page.waitForSelector('iframe[title="WordPress Playground"]', {{ timeout: 60000 }});
        
        // Additional wait for content to load
        await page.waitForTimeout(5000);
        
        // Take screenshot of the entire page
        console.log('Capturing preview screenshot...');
        await page.screenshot({{ 
            path: '{preview_screenshot}',
            fullPage: false
        }});
        
        console.log('Preview screenshot saved to {preview_screenshot}');
        
        // Try to capture the WordPress content inside the iframe
        try {{
            const frame = page.frameLocator('iframe[title="WordPress Playground"]');
            console.log('Capturing WordPress screenshot...');
            await frame.locator('body').screenshot({{ 
                path: '{wordpress_screenshot}',
                timeout: 10000
            }});
            console.log('WordPress screenshot saved to {wordpress_screenshot}');
        }} catch (iframeError) {{
            console.log('Could not capture iframe content:', iframeError.message);
        }}
        
        console.log('Screenshots captured successfully!');
        process.exit(0);
        
    }} catch (error) {{
        console.error('Error:', error.message);
        process.exit(1);
    }} finally {{
        await browser.close();
    }}
}})();
"""
    
    # Write the Playwright script to a temporary file
    script_path = os.path.join(screenshots_dir, '_capture_temp.js')
    with open(script_path, 'w') as f:
        f.write(playwright_script)
    
    # Run the Playwright script
    print("\nCapturing screenshots...")
    try:
        result = subprocess.run(
            ['node', script_path],
            capture_output=True,
            text=True,
            timeout=180  # 3 minutes timeout
        )
        
        print(result.stdout)
        
        if result.returncode != 0:
            print(f"Error: {result.stderr}")
            return False
        
        # Clean up the temporary script
        os.remove(script_path)
        
        # Check if screenshots were created
        if os.path.exists(preview_screenshot):
            print(f"✓ Screenshot successfully saved to {preview_screenshot}")
            return True
        else:
            print("✗ Screenshot was not created")
            return False
            
    except subprocess.TimeoutExpired:
        print("✗ Screenshot capture timed out")
        return False
    except Exception as e:
        print(f"✗ Error: {e}")
        return False


def main():
    """Main function to generate screenshots for blueprints."""
    print("WordPress Blueprint Screenshot Generator")
    print("=" * 60)
    
    # Check if Playwright is installed
    try:
        result = subprocess.run(['npx', 'playwright', '--version'], 
                              capture_output=True, 
                              text=True)
        print(f"Playwright version: {result.stdout.strip()}")
    except:
        print("Error: Playwright is not installed!")
        print("Please install it with: npm install -g playwright && npx playwright install chromium")
        return 1
    
    # Get blueprint directories to process
    specific_dirs = sys.argv[1:] if len(sys.argv) > 1 else None
    blueprint_dirs = get_blueprint_directories(specific_dirs)
    
    if not blueprint_dirs:
        print("No blueprint directories found to process.")
        return 0
    
    print(f"\nFound {len(blueprint_dirs)} blueprint(s) to process:")
    for dir in blueprint_dirs:
        print(f"  - {dir}")
    
    # Process each blueprint
    successful = 0
    failed = 0
    
    for blueprint_dir in blueprint_dirs:
        if generate_screenshot_for_blueprint(blueprint_dir):
            successful += 1
        else:
            failed += 1
    
    # Summary
    print("\n" + "=" * 60)
    print("SUMMARY")
    print("=" * 60)
    print(f"Total blueprints processed: {len(blueprint_dirs)}")
    print(f"Successful: {successful}")
    print(f"Failed: {failed}")
    
    return 0 if failed == 0 else 1


if __name__ == '__main__':
    sys.exit(main())
