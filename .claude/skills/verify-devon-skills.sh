#!/usr/bin/env bash
set -euo pipefail

# Verifies paths in `.agents/skills.lock.yaml`: each listed SKILL.md exists and
# matches the recorded sha256. Other skills in `.agents/skills/` are ignored so
# this repo can ship Laravel/Devon skills side by side.

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
lock_file="$repo_root/.agents/skills.lock.yaml"

test -f "$lock_file"

paths="$(grep -E '^\s+path:\s' "$lock_file" | sed -E 's/^[[:space:]]*path:[[:space:]]+//' | tr -d '\r')"
missing=0

while IFS= read -r skill_path; do
  [ -z "$skill_path" ] && continue
  file="$repo_root/${skill_path}"
  if [ ! -f "$file" ]; then
    echo "missing: $skill_path" >&2
    missing=1
    continue
  fi
  expected="$(awk -v p="$skill_path" '
    $0 ~ /^  [a-z0-9-]+:/ { key=$0; sub(/^  /, "", key); sub(/:$/, "", key) }
    $0 ~ /^    path: / { sub(/^    path: /, ""); if ($0 == p) found=1; else found=0 }
    found && $0 ~ /^    sha256: / { sub(/^    sha256: /, ""); print; exit }
  ' "$lock_file")"
  if [ -z "$expected" ]; then
    echo "lock parse failed for path: $skill_path" >&2
    missing=1
    continue
  fi
  actual="$(shasum -a 256 "$file" | awk '{print $1}')"
  if [ "$actual" != "$expected" ]; then
    echo "sha256 mismatch: $skill_path" >&2
    echo "  expected: $expected" >&2
    echo "  actual:   $actual" >&2
    missing=1
  fi
done <<< "$paths"

if [ "$missing" -ne 0 ]; then
  exit 1
fi

echo "Devon lock-listed skills are present and sha256 checks pass."
