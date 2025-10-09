#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
    echo "Usage: $0 <version>" >&2
    exit 1
fi

version="$1"
zip_file="esperluettes-test-${version}.zip"

if [[ ! -f "$zip_file" ]]; then
    echo "❌ ${zip_file} not found, aborting" >&2
    exit 1
fi

echo "🔄 Deploying from ${zip_file}..."

rm -rf app bootstrap config database public resources routes vendor

unzip -o "$zip_file"

echo "✅ Deployment complete."
