import json
import os
import re
import sys

highlighted_blueprints = [
    'Stylish Press',
    'Feed Reader with the Friends Plugin'
]

def build_json_index():
    index = {}
    for root, dirs, files in os.walk('blueprints'):
        for file in files:
            if file == 'blueprint.json':
                path = os.path.join(root, file)
                with open(path, 'r') as f:
                    data = json.load(f)
                    meta = data.get('meta', {})
                    index[path] = meta
    # Sort index alphabetically by title
    index = dict(sorted(index.items(), key=lambda item: (
        item[1].get('title', '') not in highlighted_blueprints, 
        item[1].get('title', '')
    )))
    with open('index.json', 'w') as f:
        json.dump(index, f, indent=2)


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
    blueprints_rows = [
        ['Title', 'Description', 'Author', 'Actions', ]
    ]
    for path, meta in index.items():
        title = meta.get('title', '')
        if title in highlighted_blueprints:
            title = f"**{title}**"
        blueprints_rows.append([
            title,
            meta.get('description', ''),
            '[@{0}](https://github.com/{0})'.format(meta.get('author', '')) if meta.get('author', '') else '',
            '• [Open in Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/wordpress/blueprints/trunk/{0})'.format(path) +
            '<br>• [View source](https://github.com/wordpress/blueprints/blob/trunk/{0})'.format(path) +
            '<br>• [Edit](https://playground.wordpress.net/builder/builder.html?blueprint-url=https://raw.githubusercontent.com/wordpress/blueprints/trunk/{0})'.format(path),
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


def rewrite_branch_urls_to_trunk():
    with open('index.json', 'r') as f:
        index = json.load(f)

    for path, meta in index.items():
        with open(path, 'r') as f:
            blueprint = f.read()
            json_blueprint = json.loads(blueprint)
            map_url_resources(json_blueprint, branch_url_mapper)
            with open(path, 'w') as f:
                f.write(json.dumps(json_blueprint, indent="\t"))


def map_url_resources(blueprint_fragment, mapper):
    """
    Recursively map URL resources in a blueprint using a mapper function.
    A URL resource is a dictionary with a "resource": "url" entry, and a "url" key.
    """
    if isinstance(blueprint_fragment, dict):
        if 'resource' in blueprint_fragment and blueprint_fragment['resource'] == 'url' and 'url' in blueprint_fragment:
            blueprint_fragment['url'] = mapper(blueprint_fragment['url'])
        else:
            for key, value in blueprint_fragment.items():
                map_url_resources(value, mapper)
    elif isinstance(blueprint_fragment, list):
        for item in blueprint_fragment:
            map_url_resources(item, mapper)

def branch_url_mapper(url):
    """
    Rewrite a raw.githubusercontent.com URL to point to the trunk branch.

    >>> branch_url_mapper('https://raw.githubusercontent.com/wordpress/blueprints/my-branch/blueprint.json')
    'https://raw.githubusercontent.com/wordpress/blueprints/trunk/blueprint.json'
    >>> branch_url_mapper('https://raw.githubusercontent.com/wordpress/blueprints/trunk/blueprint.json')
    'https://raw.githubusercontent.com/wordpress/blueprints/trunk/blueprint.json'
    """
    if not url.startswith("https://raw.githubusercontent.com"):
        return url
    return re.sub(r'https://raw.githubusercontent.com/wordpress/blueprints/([^/]+)', r'https://raw.githubusercontent.com/wordpress/blueprints/trunk', url)

if '--test' in sys.argv:
    print("Running doctests")
    import doctest
    doctest.testmod()
else:
    print("Reindexing")
    build_json_index()
    build_markdown_table()
    rewrite_branch_urls_to_trunk()
