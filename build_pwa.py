import os
import json
import shutil

# Define the base directory where blueprints are located
BLUEPRINTS_DIR = 'blueprints'
TEMPLATE_DIR = 'pwa-template'

def read_template(file_path):
    with open(file_path, 'r') as file:
        return file.read()

def build_pwa_for_folder(folder_path):
    # Read blueprint.json
    blueprint_path = os.path.join(folder_path, 'blueprint.json')
    if not os.path.exists(blueprint_path):
        print(f"Skipping {folder_path}, no blueprint.json found.")
        return

    with open(blueprint_path, 'r') as f:
        blueprint = json.load(f)

    # Extract necessary fields
    title = blueprint['meta']['title']
    description = blueprint['meta']['description']
    start_url = blueprint.get('landingPage', '/')

    # Read and format index.html template
    index_html_template = read_template(os.path.join(TEMPLATE_DIR, 'index.html'))
    index_html_content = index_html_template.replace('{{PWA_NAME}}', title)\
                                            .replace('{{BLUEPRINT}}', json.dumps(blueprint, indent=2))

    with open(os.path.join(folder_path, 'index.html'), 'w') as f:
        f.write(index_html_content)

    # Read and format manifest.json template
    manifest_json_template = read_template(os.path.join(TEMPLATE_DIR, 'manifest.json'))
    manifest_json_content = manifest_json_template.replace('{{PWA_NAME}}', title)\
                                                  .replace('{{PWA_SHORT_NAME}}', title.split()[0])\
                                                  .replace('{{PWA_DESCRIPTION}}', description)\
                                                  .replace('{{PWA_START_URL}}', start_url)

    with open(os.path.join(folder_path, 'manifest.json'), 'w') as f:
        f.write(manifest_json_content)

    # Ensure icons directory exists
    icons_dir = os.path.join(folder_path, 'icons')
    if not os.path.exists(icons_dir):
        os.makedirs(icons_dir)
        # Copy default icons
        default_icons_dir = os.path.join(TEMPLATE_DIR, 'icons')
        for icon in os.listdir(default_icons_dir):
            shutil.copy(os.path.join(default_icons_dir, icon), icons_dir)

def main():
    # Traverse each folder in blueprints directory
    for folder_name in os.listdir(BLUEPRINTS_DIR):
        folder_path = os.path.join(BLUEPRINTS_DIR, folder_name)
        if os.path.isdir(folder_path):
            build_pwa_for_folder(folder_path)

if __name__ == "__main__":
    main()
