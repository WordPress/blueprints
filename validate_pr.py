import os
import re
import json
import sys

def validate_blueprints():
    # Get the list of directories touched in the current branch
    touched_dirs = get_touched_directories()

    for dir in touched_dirs:
        if not re.match(r'^blueprints/[^/]+$', dir):
            continue
        blueprint_json_path = os.path.join(dir, 'blueprint.json')

        # Check if blueprint.json file exists
        if not os.path.exists(blueprint_json_path):
            print(f"Error: {dir}/{blueprint_json_path} file does not exist in this PR.")
            sys.exit(1)

        # Read and validate the JSON file
        try:
            with open(blueprint_json_path, 'r') as f:
                blueprint_json = json.load(f)
        except json.JSONDecodeError as e:
            print(f"Error: Invalid JSON in {blueprint_json_path}: {str(e)}")
            sys.exit(1)


def get_touched_directories():
    # Run git diff command to get the list of directories touched in the current branch
    diff_output = os.popen('git diff --name-only origin/trunk').read()
    touched_dirs = set()

    # Extract the directory paths from the diff output
    for line in diff_output.splitlines():
        dir_path = os.path.dirname(line)
        touched_dirs.add(dir_path)

    return touched_dirs
# Call the function to validate blueprints
validate_blueprints()