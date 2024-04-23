import os
import re
from jsonschema import Draft7Validator, validate, ValidationError
import json
import sys
import requests

def validate_blueprints():
    errors = []

    # Get the list of directories touched in the current branch
    touched_dirs = get_touched_directories()

    for dir in touched_dirs:
        if not re.match(r'^blueprints/[^/]+$', dir):
            continue
        blueprint_json_path = os.path.join(dir, 'blueprint.json')

        # Check if blueprint.json file exists
        if not os.path.exists(blueprint_json_path):
            errors.append(f"Error: {dir}/{blueprint_json_path} file does not exist in this PR.")
            continue

        # Read and validate the JSON file
        try:
            with open(blueprint_json_path, 'r') as f:
                blueprint_json = json.load(f)
        except json.JSONDecodeError as e:
            errors.append(f"Error: Invalid JSON in {blueprint_json_path}: {str(e)}")
            continue 

        # Validate the Blueprint against the JSON schema
        schema_url = 'https://playground.wordpress.net/blueprint-schema.json'
        schema = json.loads(requests.get(schema_url).text)
        
        try:
            validate(instance=blueprint_json, schema=schema, cls=Draft7Validator)
        except ValidationError as e:
            error_path = " > ".join(e.absolute_path)
            at_error = f"at {error_path}" if error_path else "at root" 
            errors.append(
                f"Error: {dir}/{blueprint_json_path} does not match the JSON schema.\n"
                f"{str(e.message)} {at_error}.\n"
            )
            continue

        # Recursively find all urls in the blueprint.json file
        urls = find_urls(blueprint_json)

        # Check if the URLs all point to raw.githubusercontent.com/wordpress/blueprints/{CURRENT BRANCH}
        urls_valid = True
        current_branch = os.environ.get('GITHUB_BRANCH') or os.popen('git rev-parse --abbrev-ref HEAD').read().strip()
        for url in urls:
            if not url.startswith('https://') and not url.startswith('http://'):
                continue
            if not url.startswith(f'https://raw.githubusercontent.com/wordpress/blueprints/{current_branch}/'):
                urls_valid = False
                errors.append(
                    f"Error: {dir}/{blueprint_json_path} contains a URL that is not allowed: \n* {url}\n"
                    f"Since the current branch is {current_branch}, the URL should start with \n* https://raw.githubusercontent.com/wordpress/blueprints/{current_branch}/\n"
                    "In general, all URLs in the blueprint.json file must start with https://raw.githubusercontent.com/wordpress/blueprints/{CURRENT BRANCH}/"
                )

        if not urls_valid:
            continue

    if len(errors):
        for error in errors:
            print(error)
        sys.exit(1)
    

def find_urls(obj):
    urls = []
    if isinstance(obj, dict):
        for key, value in obj.items():
            if key == 'url':
                urls.append(value)
            urls.extend(find_urls(value))
    elif isinstance(obj, list):
        for item in obj:
            urls.extend(find_urls(item))
    return urls

        

def get_touched_directories():
    # Run git diff command to get the list of directories touched in the current branch as compared to 
    # the point where it was forked from the trunk branch
    diff_output = os.popen('git diff --name-only $(git merge-base trunk HEAD)').read()
    touched_dirs = set()

    # Extract the directory paths from the diff output
    for line in diff_output.splitlines():
        dir_path = os.path.dirname(line)
        touched_dirs.add(dir_path)

    return touched_dirs

# Call the function to validate blueprints
validate_blueprints()