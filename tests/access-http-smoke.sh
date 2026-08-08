#!/usr/bin/env bash
set -euo pipefail

: "${ADP_BASE_URL:?ADP_BASE_URL fehlt}"
: "${ADP_USER:?ADP_USER fehlt}"
: "${ADP_PASSWORD:?ADP_PASSWORD fehlt}"
: "${ADP_TEAM_CODE:?ADP_TEAM_CODE fehlt}"
: "${ADP_FOREIGN_UID:?ADP_FOREIGN_UID fehlt}"

workdir="$(mktemp -d)"
page="$workdir/page.html"
cookies="$workdir/cookies.txt"
plan="$workdir/plan.json"
error="$workdir/error.json"
plan_after="$workdir/plan-after.json"
trap 'rm -rf "$workdir"' EXIT

curl --fail --silent --show-error --insecure --user "$ADP_USER:$ADP_PASSWORD" \
    --cookie-jar "$cookies" "$ADP_BASE_URL/index.php/apps/adplaner/" --output "$page"

token="$(sed -n 's/.*data-requesttoken="\([^"]*\)".*/\1/p' "$page" | head -n 1)"
if [[ -z "$token" ]]; then
    echo 'Request-Token fehlt.' >&2
    exit 1
fi

month="$(date -u +%Y-%m)"
plan_endpoint="$ADP_BASE_URL/index.php/apps/adplaner/api/teams/$ADP_TEAM_CODE/months/$month"
curl --fail --silent --show-error --insecure --user "$ADP_USER:$ADP_PASSWORD" \
    --cookie "$cookies" --cookie-jar "$cookies" -H "requesttoken: $token" \
    "$plan_endpoint" --output "$plan"

slot_id="$(php -r '
$data = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
foreach (($data["days"] ?? []) as $day) {
    foreach (($day["slots"] ?? []) as $slot) {
        if (isset($slot["id"])) {
            echo (int)$slot["id"];
            exit;
        }
    }
}
exit(1);
' "$plan")"

candidate_endpoint="$plan_endpoint/slots/$slot_id/candidates"
status="$(curl --silent --show-error --insecure --user "$ADP_USER:$ADP_PASSWORD" \
    --cookie "$cookies" --cookie-jar "$cookies" -H "requesttoken: $token" -H 'Content-Type: application/json' \
    -X POST --data "{\"targetUid\":\"$ADP_FOREIGN_UID\"}" --write-out '%{http_code}' \
    --output "$error" "$candidate_endpoint")"
if [[ "$status" != '403' ]] || ! php -r '
$data = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
exit(($data["ok"] ?? true) === false ? 0 : 1);
' "$error"; then
    echo "Fremdänderung durch normales Teammitglied ergab HTTP $status statt eines verständlichen 403-Fehlers." >&2
    exit 1
fi

curl --fail --silent --show-error --insecure --user "$ADP_USER:$ADP_PASSWORD" \
    --cookie "$cookies" --cookie-jar "$cookies" -H "requesttoken: $token" \
    "$plan_endpoint" --output "$plan_after"
php -r '
$data = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$foreignUid = $argv[2];
foreach (($data["days"] ?? []) as $day) {
    foreach (($day["slots"] ?? []) as $slot) {
        foreach (($slot["candidates"] ?? []) as $candidate) {
            if (($candidate["assistantUid"] ?? "") === $foreignUid) {
                throw new RuntimeException("Die abgewiesene Fremdänderung wurde dennoch gespeichert.");
            }
        }
    }
}
' "$plan_after" "$ADP_FOREIGN_UID"

echo "AdPlaner C3 API access smoke: OK ($ADP_USER)"
