#!/usr/bin/env bash
# PreToolUse hook (Bash matcher): auto-allows `rm` calls (including ones
# wrapped in `timeout`) whose targets stay inside the project directory,
# auto-denies ones that reach outside it. Any other command is left alone
# (no decision emitted -> normal rules apply).
set -euo pipefail

cmd=$(jq -r '.tool_input.command // empty')
[ -z "$cmd" ] && exit 0

root=$(cd "${CLAUDE_PROJECT_DIR:-.}" && pwd)

mapfile -t subcmds < <(printf '%s' "$cmd" | sed -E 's/(&&|\|\||;|\|&|\||&)/\n/g')

found_rm=0
outside=""

for sub in "${subcmds[@]}"; do
  read -ra tok <<< "$sub"
  [ "${#tok[@]}" -eq 0 ] && continue

  start=0
  if [ "${tok[0]}" = "timeout" ]; then
    start=1
    # skip timeout's own flags (-k/--kill-after, -s/--signal take a value)
    while [ "$start" -lt "${#tok[@]}" ] && [[ "${tok[$start]}" == -* ]]; do
      case "${tok[$start]}" in
        -k|--kill-after|-s|--signal) start=$((start + 2)) ;;
        *) start=$((start + 1)) ;;
      esac
    done
    start=$((start + 1)) # mandatory DURATION argument
  fi
  [ "$start" -ge "${#tok[@]}" ] && continue
  [ "${tok[$start]}" != "rm" ] && continue
  found_rm=1
  for t in "${tok[@]:$((start + 1))}"; do
    case "$t" in
      -*) continue ;;
    esac
    target=$(realpath -m -- "$t" 2>/dev/null || printf '%s' "$t")
    case "$target" in
      "$root"|"$root"/*) ;;
      *) outside="$t" ;;
    esac
  done
done

[ "$found_rm" -eq 0 ] && exit 0

if [ -n "$outside" ]; then
  printf '{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"deny","permissionDecisionReason":"rm target \\"%s\\" resolves outside the project directory (%s)"}}' "$outside" "$root"
else
  printf '{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"allow","permissionDecisionReason":"all rm targets are inside the project directory"}}'
fi
