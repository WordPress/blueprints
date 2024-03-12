import json
import os
import re

def build_json_index():
    index = {}
    for root, dirs, files in os.walk('v1-examples'):
        for file in files:
            if file == 'blueprint.json':
                path = os.path.join(root, file)
                with open(path, 'r') as f:
                    data = json.load(f)
                    meta = data.get('meta', {})
                    index[path] = meta
    with open('index.json', 'w') as f:
        json.dump(index, f, indent=2)

build_json_index()

def build_markdown_index():
    with open('index.json', 'r') as f:
        index = json.load(f)
    blueprints_list = []
    for path, meta in index.items():
        blueprints_list.append('* {0} – [Preview]({1}) | [Source]({2})\n'.format(
            meta.get('title', ''),
            'https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/adamziel/blueprints/trunk/' + path,
            'https://github.com/adamziel/blueprints/blob/trunk/' + path
        ))
    # Replace "{BLUEPRINTS_LIST}" in README.md.template and save to README.md
    with open('README.md.template', 'r') as f:
        template = f.read()
        with open('README.md', 'w') as f:
            f.write(re.sub(r'{BLUEPRINTS_LIST}', ''.join(blueprints_list), template))

build_markdown_index()
