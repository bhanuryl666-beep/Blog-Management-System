#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
FRONTEND_DIR="$ROOT_DIR/frontend"

backend_files=(
  "admin.php"
  "analytics.php"
  "auth.php"
  "create.php"
  "db.php"
  "delete.php"
  "edit.php"
  "hash.php"
  "login.php"
  "logout.php"
  "post_media.php"
  "register.php"
  "resend_verification.php"
  "test.php"
  "users.php"
  "verify.php"
)

frontend_files=(
  "index.php"
  "style.css"
  "favicon.svg"
  "image.png"
  "image-1.png"
  "image-2.png"
  "image-3.png"
  "image-4.png"
  "image-5.png"
)

move_file() {
  local source_path="$1"
  local target_dir="$2"
  local filename

  filename="$(basename "$source_path")"

  if [[ -e "$source_path" ]]; then
    mv "$source_path" "$target_dir/$filename"
    printf 'Moved %s -> %s/%s\n' "$filename" "$(basename "$target_dir")" "$filename"
  elif [[ -e "$target_dir/$filename" ]]; then
    printf 'Skipped %s (already in %s)\n' "$filename" "$(basename "$target_dir")"
  else
    printf 'Skipped %s (not found)\n' "$filename"
  fi
}

mkdir -p "$BACKEND_DIR" "$FRONTEND_DIR"

for file in "${backend_files[@]}"; do
  move_file "$ROOT_DIR/$file" "$BACKEND_DIR"
done

for file in "${frontend_files[@]}"; do
  move_file "$ROOT_DIR/$file" "$FRONTEND_DIR"
done

printf '\nFinal structure:\n'
find "$ROOT_DIR" -maxdepth 2 | sed "s#^$ROOT_DIR#.#" | sort
