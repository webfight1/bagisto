#!/usr/bin/env python3
"""
Split custom_app.php into smaller files by top-level array keys.
Each top-level key (e.g. 'users', 'sales', 'marketing', etc.)
becomes its own PHP file in the same directory.
"""

import re
import os

SOURCE = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'app.php')
OUTPUT_DIR = os.path.dirname(os.path.abspath(__file__))

def find_top_level_keys(lines):
    """
    Find all top-level array keys and their line ranges.
    Top-level keys are at exactly 4-space indent: "    'key' => ["
    """
    keys = []
    pattern = re.compile(r"^    '([a-z][a-z0-9_-]*)'\s*=>\s*\[")

    for i, line in enumerate(lines):
        m = pattern.match(line)
        if m:
            keys.append((m.group(1), i))

    return keys


def find_closing_bracket(lines, start_line):
    """
    From the opening line of a top-level key, find its matching closing bracket.
    The closing pattern is "    ]," or "    ]" at exactly 4-space indent.
    We track bracket depth starting from the opening '['.
    """
    depth = 0
    for i in range(start_line, len(lines)):
        line = lines[i]
        # Count brackets (ignore those inside strings for simplicity,
        # but for language files this is generally safe)
        in_string = False
        escape = False
        for ch in line:
            if escape:
                escape = False
                continue
            if ch == '\\':
                escape = True
                continue
            if ch == "'":
                in_string = not in_string
                continue
            if not in_string:
                if ch == '[':
                    depth += 1
                elif ch == ']':
                    depth -= 1
                    if depth == 0:
                        return i
    return len(lines) - 1


def main():
    with open(SOURCE, 'r', encoding='utf-8') as f:
        content = f.read()

    lines = content.split('\n')

    top_keys = find_top_level_keys(lines)
    print(f"Found {len(top_keys)} top-level keys: {[k[0] for k in top_keys]}")

    for idx, (key, start) in enumerate(top_keys):
        end = find_closing_bracket(lines, start)

        # Extract the block (just the key's content)
        block_lines = lines[start:end + 1]

        # Build a standalone PHP file
        php_content = "<?php\n\nreturn [\n"
        php_content += '\n'.join(block_lines)
        php_content += "\n];\n"

        filename = f"custom_app_{key}.php"
        filepath = os.path.join(OUTPUT_DIR, filename)

        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(php_content)

        line_count = end - start + 1
        print(f"  {filename} ({line_count} lines, lines {start+1}-{end+1})")

    print(f"\nDone! {len(top_keys)} files created in {OUTPUT_DIR}")


if __name__ == '__main__':
    main()
