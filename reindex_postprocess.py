import json
import os
import re
import subprocess
import sys
from functools import lru_cache

highlighted_blueprints = {
    'Feed Reader with the Friends Plugin',
    'Gaming News',
    'Skincare Blog',
    'Non-profit Organization',
    'Personal Resume',
    'Coffee Shop',
    'Personal Blog',
    'University Website',
    'Photography Portfolio',
    'Art Gallery',
}


@lru_cache(maxsize=512)
def get_last_commit_for_file(path):
    """
    Get the commit hash where this specific file was last changed.
    This ensures URLs are stable and only change when the file itself changes.
    """
    try:
        result = subprocess.check_output(
            ['git', 'log', '-1', '--format=%H', '--', path],
            text=True
        ).strip()
        return result if result else 'trunk'
    except Exception:
        return 'trunk'


def build_raw_repo_url(path):
    rel = path.lstrip('./').replace('\\', '/')
    commit_hash = get_last_commit_for_file(path)
    return 'https://raw.githubusercontent.com/wordpress/blueprints/{rev}/{path}'.format(
        rev=commit_hash,
        path=rel
    )


def build_raw_blueprint_url(path):
    return build_raw_repo_url(path)


def build_preview_url(path):
    return 'https://playground.wordpress.net/?blueprint-url={0}'.format(build_raw_blueprint_url(path))


def build_edit_url(path):
    return 'https://playground.wordpress.net/builder/builder.html?blueprint-url={0}'.format(build_raw_blueprint_url(path))


def resolve_screenshot_path(meta, blueprint_path):
    screenshot = meta.get('screenshot')
    if screenshot:
        normalized = screenshot.replace('\\', '/').lstrip('./')
        if normalized.startswith('http://') or normalized.startswith('https://'):
            return normalized
        if screenshot.startswith('/'):
            return normalized
        blueprint_dir = os.path.dirname(blueprint_path).replace('\\', '/')
        return f"{blueprint_dir}/{normalized}".replace('//', '/')

    blueprint_dir = os.path.dirname(blueprint_path)
    return os.path.join(blueprint_dir, 'screenshot.jpg').replace('\\', '/')


def screenshot_source_exists(path):
    if not path:
        return False
    if re.match(r'^[a-z]+://', path):
        return True
    absolute = os.path.abspath(path)
    return os.path.exists(absolute)


def build_screenshot_html(preview, screenshot_path, title):
    label = title or 'Blueprint'
    if screenshot_source_exists(screenshot_path):
        return '<p align="left"><img src="{src}" alt="{alt} screenshot" width="400"></p>'.format(
            preview=preview,
            src=screenshot_path,
            alt=label
        )
    return '<p align="left"><em>No screenshot yet for {name}. Open it in Playground.</em></p>'.format(
        name=label,
        preview=preview
    )

def build_json_index():
    index = {}
    for root, dirs, files in os.walk('blueprints'):
        for file in files:
            if file == 'blueprint.json':
                path = os.path.join(root, file)
                with open(path, 'r') as f:
                    data = json.load(f)
                    meta = data.get('meta', {})
                    meta_with_media = dict(meta)
                    screenshot_path = resolve_screenshot_path(meta, path)
                    if screenshot_source_exists(screenshot_path):
                        if screenshot_path.startswith('http://') or screenshot_path.startswith('https://'):
                            screenshot_url = screenshot_path
                        else:
                            screenshot_url = build_raw_repo_url(screenshot_path)
                        meta_with_media['screenshot_url'] = screenshot_url
                    # Add featured flag based on whether the title is in highlighted_blueprints
                    meta_with_media['featured'] = meta.get('title', '') in highlighted_blueprints
                    index[path] = meta_with_media
    # Sort index alphabetically by title
    index = dict(sorted(index.items(), key=lambda item: (
        item[1].get('title', '') not in highlighted_blueprints, 
        item[1].get('title', '')
    )))
    with open('index.json', 'w') as f:
        json.dump(index, f, indent=2)
    return index


def get_dot_template_files():
    dot_template_files = []
    for root, dirs, files in os.walk('.'):
        for file in files:
            if file.endswith('.template'):
                path = os.path.join(root, file)
                dot_template_files.append(path)
    return dot_template_files


def build_gallery_html(index_data=None):
    """
    Generate gallery.html from gallery.html.template and embed the blueprint index JSON
    so the front-end can render without additional network requests.
    """
    template_file = 'gallery.html.template'
    output_file = 'gallery.html'

    if os.path.exists(template_file):
        with open(template_file, 'r') as f:
            content = f.read()

        placeholder = '{BLUEPRINT_INDEX_JSON}'
        if placeholder in content:
            if index_data is None:
                if os.path.exists('index.json'):
                    with open('index.json', 'r') as index_file:
                        index_data = json.load(index_file)
                else:
                    index_data = {}

            serialized_index = json.dumps(index_data or {}, ensure_ascii=False, separators=(',', ':'))
            serialized_index = serialized_index.replace('</script', '<\\/script')
            content = content.replace(placeholder, serialized_index)

        with open(output_file, 'w') as f:
            f.write(content)


def build_markdown_table():
    with open('index.json', 'r') as f:
        index = json.load(f)
    entries = []
    for path, meta in index.items():
        title = meta.get('title', '')
        display_title = title or 'Untitled Blueprint'
        if display_title in highlighted_blueprints:
            display_title = f"<strong>{display_title}</strong>"

        preview = build_preview_url(path)
        screenshot_path = resolve_screenshot_path(meta, path)
        screenshot_html = build_screenshot_html(preview, screenshot_path, title)

        description = meta.get('description', '')
        description_html = f'<p>{description}</p>' if description else ''

        preview_button = f'<p><a href="{preview}"><img src="playground-preview-button.svg" alt="Try it in Playground" width="220"></a></p>'
        edit_url = build_edit_url(path)
        author = meta.get('author', '').strip()
        if author:
            author_link = f'<a href="https://github.com/{author}">@{author}</a>'
            meta_line = (
                '<p><small>'
                f'By {author_link} • <a href="https://github.com/wordpress/blueprints/blob/trunk/{path}">View source</a> '
                f'• <a href="{edit_url}">Edit</a>'
                '</small></p>'
            )
        else:
            meta_line = (
                '<p><small>'
                f'<a href="https://github.com/wordpress/blueprints/blob/trunk/{path}">View source</a> '
                f'• <a href="{edit_url}">Edit</a>'
                '</small></p>'
            )

        entry = (
            f'<h2>{display_title}</h2>\n'
            f'{description_html}\n'
            f'{meta_line}\n'
            f'{preview_button}\n'
            f'{screenshot_html}\n'
            '<br clear="all">\n'
        )

        entries.append(entry)

    formatted_table = '\n\n'.join(entries)

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
            original_blueprint = f.read()
            json_blueprint = json.loads(original_blueprint)
            map_url_resources(json_blueprint, branch_url_mapper)
            new_blueprint = json.dumps(json_blueprint, indent="\t")
            # Only write if content changed to avoid unnecessary modifications
            if original_blueprint != new_blueprint:
                with open(path, 'w') as f:
                    f.write(new_blueprint)


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
    index_data = build_json_index()
    build_markdown_table()
    build_gallery_html(index_data)
    rewrite_branch_urls_to_trunk()
