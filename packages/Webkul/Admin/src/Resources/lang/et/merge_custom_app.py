#!/usr/bin/env python3
"""
Merge all custom_app_*.php part files from the 'parts' directory
back into a single custom_app.php file in the parent directory.

Each part file has the structure:
    <?php
    return [
        'key' => [
            ...
        ],
    ];

This script extracts the inner content (the top-level key and its array)
from each file and combines them into one PHP file.
"""

import os
import re
import glob

PARTS_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'parts')
OUTPUT_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'custom_app.php')


def extract_inner_block(filepath):
    """
    Read a part file and extract the top-level key block.
    Returns the lines between 'return [' and the final '];',
    i.e. the "    'key' => [ ... ]," block.
    """
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    lines = content.split('\n')

    # Find the line after 'return ['
    start = None
    end = None
    for i, line in enumerate(lines):
        if re.match(r'^return\s*\[', line.strip()):
            start = i + 1
            break

    if start is None:
        print(f"  WARNING: Could not find 'return [' in {filepath}")
        return None

    # Find the final '];'
    for i in range(len(lines) - 1, start - 1, -1):
        if lines[i].strip() == '];':
            end = i
            break

    if end is None:
        print(f"  WARNING: Could not find closing '];' in {filepath}")
        return None

    # Extract the inner lines
    inner_lines = lines[start:end]

    # Remove trailing empty lines
    while inner_lines and inner_lines[-1].strip() == '':
        inner_lines.pop()

    return '\n'.join(inner_lines)


def main():
    # Find all part files
    pattern = os.path.join(PARTS_DIR, 'custom_app_*.php')
    part_files = sorted(glob.glob(pattern))

    if not part_files:
        print(f"No part files found in {PARTS_DIR}")
        return

    print(f"Found {len(part_files)} part files:")

    blocks = []
    for filepath in part_files:
        filename = os.path.basename(filepath)
        block = extract_inner_block(filepath)
        if block:
            blocks.append(block)
            print(f"  + {filename}")
        else:
            print(f"  ! {filename} (skipped)")

    # Build the merged file
    output = "<?php\n\nreturn [\n"
    output += '\n\n'.join(blocks)
    output += "\n];\n"

    with open(OUTPUT_FILE, 'w', encoding='utf-8') as f:
        f.write(output)

    print(f"\nDone! Merged {len(blocks)} sections into {OUTPUT_FILE}")


if __name__ == '__main__':
    main()
