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

def get_dot_template_files():
    dot_template_files = []
    for root, dirs, files in os.walk('.'):
        for file in files:
            if file.endswith('.template'):
                path = os.path.join(root, file)
                dot_template_files.append(path)
    return dot_template_files


def build_markdown_table():
    with open('index.json', 'r') as f:
        index = json.load(f)
    # blueprints_list = []
    # for path, meta in index.items():
    #     blueprints_list.append('* {0} – [Preview]({1}) | [Source]({2})\n'.format(
    #         meta.get('title', ''),
    #         'https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/adamziel/blueprints/trunk/' + path,
    #         'https://github.com/adamziel/blueprints/blob/trunk/' + path
    #     ))
    blueprints_rows = [
        ['Title', 'Preview', 'Source']
    ]
    for path, meta in index.items():
        blueprints_rows.append([
            meta.get('title', ''),
            '[Preview](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/adamziel/blueprints/trunk/{0})'.format(path),
            '[Source](https://github.com/adamziel/blueprints/blob/trunk/{0})'.format(path)
        ])

    widths = [max(map(len, col)) for col in zip(*blueprints_rows)]

    def format_row(row):
        formatted_row = ' | '.join((val.ljust(width) for val, width in zip(row, widths)))
        return '| ' + formatted_row + ' |'

    formatted_rows = [
        format_row(blueprints_rows[0]),
        format_row(['-' * len(cell) for cell in blueprints_rows[0]])
    ]
    for row in blueprints_rows[1:]:
        formatted_rows.append(format_row(row))
    formatted_table = '\n'.join(formatted_rows)

    # Replace "{BLUEPRINTS_TABLE}" in all the *.template files
    DOT_TEMPLATE_FILES = get_dot_template_files()
    for file in DOT_TEMPLATE_FILES:
        with open(file, 'r') as f:
            template = f.read()
            with open(file.replace('.template', ''), 'w') as f:
                f.write(re.sub(r'{BLUEPRINTS_TABLE}', ''.join(formatted_table), template))

build_markdown_table()
