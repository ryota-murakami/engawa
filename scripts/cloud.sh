#!/bin/bash
# cloud.sh — read-only view of this project on Laravel Cloud (applications, environments, deployments).
#
# Why it exists: deployment status used to be read off the Cloud dashboard by polling the browser,
# which blocked the session and was easy to misread. This script sends GET requests only.
# API reference: https://laravel.com/cloud/docs/api/introduction
#   (JSON:API responses; OpenAPI spec at https://cloud.laravel.com/api-docs/api.json)
#
# Token: $LARAVEL_CLOUD_API_TOKEN, or an op:// reference in $LARAVEL_CLOUD_API_TOKEN_REF (also read
# from .env) resolved with `op read`; the token itself is never printed. Create it in Cloud under
# organization settings → API tokens, with read permissions and scoped to this application.
#
# Usage:
#   scripts/cloud.sh apps                               applications: id, name, region
#   scripts/cloud.sh envs <application-id>              its environments: id, name, status
#   scripts/cloud.sh deployments <environment-id> [N]   newest N deployments (default 5)
#   scripts/cloud.sh wait <deployment-id> [SECONDS]     poll until it ends: exit 0 succeeded, 1 failed, 2 timeout
#   scripts/cloud.sh get </path>                        any GET path as raw JSON, e.g. /meta/regions
set -euo pipefail

API="https://cloud.laravel.com/api"
cd "$(dirname "$0")/.."

die() { echo "cloud.sh: $*" >&2; exit 1; }

# The API token, from the environment or 1Password; never echoed
token() {
  if [ -n "${LARAVEL_CLOUD_API_TOKEN:-}" ]; then
    printf '%s' "$LARAVEL_CLOUD_API_TOKEN"
    return
  fi
  local ref="${LARAVEL_CLOUD_API_TOKEN_REF:-}"
  # Fall back to the reference kept in the git-ignored .env
  if [ -z "$ref" ] && [ -f .env ]; then
    ref=$(sed -n 's/^LARAVEL_CLOUD_API_TOKEN_REF=//p' .env | tr -d '"' | tail -1)
  fi
  [ -n "$ref" ] || die "no token: set LARAVEL_CLOUD_API_TOKEN_REF=op://<vault>/<item>/<field> in .env (see the header of this file)"
  op read "$ref" || die "op read failed for the token reference (is 1Password unlocked?)"
}

TOKEN=""

# GET one API path; the header comes from a file descriptor so the token stays out of the process list
get() {
  local path="$1" body status
  # Resolved once per run: `wait` polls, and 1Password should be asked only once
  if [ -z "$TOKEN" ]; then
    TOKEN=$(token)
  fi
  body=$(curl -sS --max-time 30 -w '\n%{http_code}' -H 'Accept: application/json' \
    -H @<(printf 'Authorization: Bearer %s\n' "$TOKEN") "$API$path") || die "request failed: GET $path"
  status=${body##*$'\n'}
  body=${body%$'\n'*}
  case "$status" in
    2??) printf '%s\n' "$body" ;;
    401) die "401: the token was refused (expired, revoked or mistyped)" ;;
    403) die "403: the token's permissions or resource scope do not cover GET $path" ;;
    *) die "HTTP $status for GET $path: $(printf '%s' "$body" | head -c 300)" ;;
  esac
}

# One line per deployment: id, status, branch@commit, start → finish, and the failure reason if any
deployment_line='"\(.id)\t\(.attributes.status)\t\(.attributes.branch_name)@\(.attributes.commit_hash[0:7])\t\(.attributes.started_at // "-") → \(.attributes.finished_at // "-")\(if .attributes.failure_reason then "\t" + .attributes.failure_reason else "" end)"'

case "${1:-}" in
  apps)
    get /applications | jq -r '.data[] | "\(.id)\t\(.attributes.name)\t\(.attributes.region // "-")"' ;;
  envs)
    [ -n "${2:-}" ] || die "usage: cloud.sh envs <application-id>"
    get "/applications/$2/environments" | jq -r '.data[] | "\(.id)\t\(.attributes.name)\t\(.attributes.status // "-")"' ;;
  deployments)
    [ -n "${2:-}" ] || die "usage: cloud.sh deployments <environment-id> [N]"
    get "/environments/$2/deployments" | jq -r --argjson n "${3:-5}" \
      "[.data[]] | sort_by(.attributes.started_at // \"\") | reverse | .[0:\$n][] | $deployment_line" ;;
  wait)
    [ -n "${2:-}" ] || die "usage: cloud.sh wait <deployment-id> [SECONDS]"
    deadline=$(( $(date +%s) + ${3:-900} ))
    while :; do
      status=$(get "/deployments/$2" | jq -r '.data.attributes.status')
      echo "$(date +%H:%M:%S) $status"
      # Finished states end the wait; everything else is still in progress
      case "$status" in
        deployment.succeeded) exit 0 ;;
        failed | cancelled | build.failed | deployment.failed)
          get "/deployments/$2" | jq -r '.data.attributes.failure_reason // empty'
          exit 1 ;;
      esac
      [ "$(date +%s)" -lt "$deadline" ] || { echo "still $status after ${3:-900}s"; exit 2; }
      sleep 10
    done ;;
  get)
    [[ "${2:-}" == /* ]] || die "usage: cloud.sh get </path>"
    get "$2" ;;
  *)
    awk 'NR > 1 && /^#/ { sub(/^# ?/, ""); print; next } NR > 1 { exit }' "$0"
    exit 2 ;;
esac
