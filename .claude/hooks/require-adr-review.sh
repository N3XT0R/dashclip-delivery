#!/usr/bin/env bash
#
# Claude Code PreToolUse hook for file edits (Edit, Write, NotebookEdit). The ADRs in docs/adr/ are
# binding project guidance (AGENTS.md), so a project file may only be changed once the session has
# read at least one ADR. Until then the edit is denied and the reason lists the ADRs to choose from.
# Files outside the project (memory, scratchpad) are not affected.

set -uo pipefail

input=$(cat)
project_dir="${CLAUDE_PROJECT_DIR:-$(pwd)}"
target=$(jq -r '.tool_input.file_path // .tool_input.notebook_path // empty' <<<"$input")
transcript=$(jq -r '.transcript_path // empty' <<<"$input")

if [[ -z "$target" ]]; then
    exit 0
fi
if [[ "$target" != /* ]]; then
    target="$project_dir/$target"
fi
if [[ "$target" != "$project_dir"/* ]]; then
    exit 0
fi

# An ADR counts as read when an earlier tool call of this session (Read, or a shell command such as
# cat or sed) named one of the ADR files.
# The tool inputs are collected first: grep -q in a pipe would end jq with SIGPIPE, which pipefail
# turns into a failed match.
if [[ -n "$transcript" && -f "$transcript" ]]; then
    tool_inputs=$(jq -r 'select(.type == "assistant") | .message.content[]? | select(.type == "tool_use") | .input | tostring' "$transcript" 2>/dev/null)
    if grep -qE 'docs/adr/[^"[:space:]]+\.md' <<<"$tool_inputs"; then
        exit 0
    fi
fi

adr_list=$(for adr in "$project_dir"/docs/adr/*.md; do
    [[ -f "$adr" ]] || continue
    title=$(grep -m 1 -E '^# ' "$adr" | sed 's/^# //')
    printf -- '- docs/adr/%s: %s\n' "$(basename "$adr")" "$title"
done)

reason="Edit blocked: no ADR has been read in this session yet. The ADRs in docs/adr/ are binding project guidance (AGENTS.md). Read every ADR that covers this change before editing ${target#"$project_dir"/}, then apply it:
$adr_list"

jq -n --arg reason "$reason" '{
    hookSpecificOutput: {
        hookEventName: "PreToolUse",
        permissionDecision: "deny",
        permissionDecisionReason: $reason
    }
}'
exit 0
