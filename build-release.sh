#!/usr/bin/env bash
#
# Creates itella-shipping.zip with the itella-shipping folder, excluding
# files matched by the root .gitignore. Does not require git.
#
# Usage: ./build-release.sh
#

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="itella-shipping"
ZIP_NAME="itella-shipping.zip"
GITIGNORE="$ROOT_DIR/.gitignore"

if ! command -v zip > /dev/null 2>&1; then
    echo "'zip' command is required." >&2
    exit 1
fi

if [[ ! -d "$ROOT_DIR/$PLUGIN_DIR" ]]; then
    echo "Folder '$PLUGIN_DIR' not found." >&2
    exit 1
fi

RULE_REGEX=()
RULE_NEGATE=()
RULE_DIR_ONLY=()

# Converts gitignore glob pattern to extended regular expression
pattern_to_regex() {
    local pattern="$1"
    local anchored="$2"
    local regex=""
    local length=${#pattern}
    local i=0 char next class rest

    while (( i < length )); do
        char="${pattern:i:1}"

        if [[ "$char" == "*" ]]; then
            next="${pattern:i+1:1}"
            if [[ "$next" == "*" ]] && { (( i == 0 )) || [[ "${pattern:i-1:1}" == "/" ]]; }; then
                if [[ "${pattern:i+2:1}" == "/" ]]; then
                    # "**/" matches zero or more directories
                    regex+="(.*/)?"
                    (( i += 3 ))
                    continue
                fi
                if (( i + 2 == length )); then
                    # "/**" matches everything inside
                    regex+=".*"
                    (( i += 2 ))
                    continue
                fi
            fi
            regex+="[^/]*"
        elif [[ "$char" == "?" ]]; then
            regex+="[^/]"
        elif [[ "$char" == "[" ]]; then
            rest="${pattern:i+1}"
            class="${rest%%]*}"
            if [[ "$rest" == "$class" || -z "$class" ]]; then
                regex+="\\["
            else
                [[ "${class:0:1}" == "!" ]] && class="^${class:1}"
                regex+="[$class]"
                (( i += ${#class} + 1 ))
            fi
        elif [[ "$char" == "\\" && $(( i + 1 )) -lt $length ]]; then
            (( i += 1 ))
            regex+="\\${pattern:i:1}"
        elif [[ "$char" =~ [].[{}()+^\$\|] ]]; then
            regex+="\\$char"
        else
            regex+="$char"
        fi

        (( i += 1 ))
    done

    if [[ "$anchored" == "1" ]]; then
        printf '^%s$' "$regex"
    else
        printf '^(.*/)?%s$' "$regex"
    fi
}

# Parses .gitignore file into rule arrays
load_gitignore_rules() {
    local line negate dir_only anchored

    [[ -f "$GITIGNORE" ]] || return 0

    while IFS= read -r line || [[ -n "$line" ]]; do
        line="${line%$'\r'}"

        # Trailing spaces are ignored unless escaped with backslash
        while [[ "$line" == *" " && "$line" != *"\\ " ]]; do
            line="${line% }"
        done

        [[ -z "$line" || "${line:0:1}" == "#" ]] && continue

        negate=0
        if [[ "${line:0:1}" == "!" ]]; then
            negate=1
            line="${line:1}"
        elif [[ "${line:0:2}" == "\\!" || "${line:0:2}" == "\\#" ]]; then
            line="${line:1}"
        fi

        dir_only=0
        if [[ "$line" == */ ]]; then
            dir_only=1
            while [[ "$line" == */ ]]; do
                line="${line%/}"
            done
        fi

        [[ -z "$line" ]] && continue

        # Pattern with slash at the beginning or middle is relative to .gitignore location
        anchored=0
        [[ "$line" == */* ]] && anchored=1
        while [[ "$line" == /* ]]; do
            line="${line#/}"
        done

        RULE_REGEX+=("$(pattern_to_regex "$line" "$anchored")")
        RULE_NEGATE+=("$negate")
        RULE_DIR_ONLY+=("$dir_only")
    done < "$GITIGNORE"
}

# Checks if path is ignored. Last matching rule wins.
is_ignored() {
    local rel_path="$1"
    local is_dir="$2"
    local ignored=1
    local i

    for i in "${!RULE_REGEX[@]}"; do
        if [[ "${RULE_DIR_ONLY[i]}" == "1" && "$is_dir" == "0" ]]; then
            continue
        fi
        if [[ "$rel_path" =~ ${RULE_REGEX[i]} ]]; then
            if [[ "${RULE_NEGATE[i]}" == "1" ]]; then
                ignored=1
            else
                ignored=0
            fi
        fi
    done

    return $ignored
}

FILES_LIST=()
FILES_COUNT=0

# Recursively collects paths to add to zip, skipping ignored ones
collect_files() {
    local rel_dir="$1"
    local abs_path rel_path name is_dir

    for abs_path in "$ROOT_DIR/$rel_dir"/* "$ROOT_DIR/$rel_dir"/.*; do
        name="${abs_path##*/}"
        [[ "$name" == "." || "$name" == ".." || "$name" == ".git" ]] && continue
        [[ -e "$abs_path" || -L "$abs_path" ]] || continue

        rel_path="$rel_dir/$name"
        is_dir=0
        [[ -d "$abs_path" ]] && is_dir=1

        if is_ignored "$rel_path" "$is_dir"; then
            continue
        fi

        if [[ "$is_dir" == "1" ]]; then
            FILES_LIST+=("$rel_path/")
            collect_files "$rel_path"
        else
            FILES_LIST+=("$rel_path")
            (( FILES_COUNT += 1 ))
        fi
    done
}

load_gitignore_rules

FILES_LIST+=("$PLUGIN_DIR/")
collect_files "$PLUGIN_DIR"

rm -f "$ROOT_DIR/$ZIP_NAME"

cd "$ROOT_DIR"
printf '%s\n' "${FILES_LIST[@]}" | zip -q -X "$ZIP_NAME" -@

echo "Created $ZIP_NAME ($FILES_COUNT files)"
