#!/usr/bin/env bash
set -euo pipefail

: "${ADP_BASE_URL:?ADP_BASE_URL fehlt}"
: "${ADP_USER:?ADP_USER fehlt}"
: "${ADP_PASSWORD:?ADP_PASSWORD fehlt}"

workdir="$(mktemp -d)"
page="$workdir/page.html"
cookies="$workdir/cookies.txt"
state="$workdir/state.json"
csrf_error="$workdir/csrf-error.json"
access_error="$workdir/access-error.json"
trap 'rm -rf "$workdir"' EXIT

curl --fail --silent --show-error --insecure --user "$ADP_USER:$ADP_PASSWORD" \
    --cookie-jar "$cookies" "$ADP_BASE_URL/index.php/apps/adplaner/" --output "$page"
for contract in 'id="adplaner-app"' 'id="team-select"' 'id="month-input"' 'id="adp-panel"'; do
    if ! grep -q "$contract" "$page"; then
        echo "App-DOM-Vertrag fehlt: $contract" >&2
        exit 1
    fi
done

token="$(sed -n 's/.*data-requesttoken="\([^"]*\)".*/\1/p' "$page" | head -n 1)"
if [[ -z "$token" ]]; then
    echo 'Request-Token fehlt.' >&2
    exit 1
fi

curl --fail --silent --show-error --insecure --user "$ADP_USER:$ADP_PASSWORD" \
    --cookie "$cookies" --cookie-jar "$cookies" -H "requesttoken: $token" \
    "$ADP_BASE_URL/index.php/apps/adplaner/api/state" --output "$state"
ADP_STATE="$state" ADP_USER="$ADP_USER" php -r '
$state = json_decode(file_get_contents(getenv("ADP_STATE")), true, flags: JSON_THROW_ON_ERROR);
if (($state["currentUser"]["uid"] ?? "") !== getenv("ADP_USER")) throw new RuntimeException("Aktuelles Konto fehlt im API-Zustand.");
if (!is_array($state["teams"] ?? null)) throw new RuntimeException("Teamliste fehlt im API-Zustand.");
if (!is_array($state["organization"] ?? null)) throw new RuntimeException("Organisationsvertrag fehlt im API-Zustand.");
'

endpoint="$ADP_BASE_URL/index.php/apps/adplaner/api/teams/SmokeMissing/settings"
status="$(curl --silent --show-error --insecure --user "$ADP_USER:$ADP_PASSWORD" \
    --cookie "$cookies" --cookie-jar "$cookies" -H 'Content-Type: application/json' \
    -X POST --data '{}' --write-out '%{http_code}' --output "$csrf_error" "$endpoint")"
if [[ "$status" != '412' ]]; then
    echo "Schreibzugriff ohne CSRF-Token ergab HTTP $status statt 412." >&2
    exit 1
fi

status="$(curl --silent --show-error --insecure --user "$ADP_USER:$ADP_PASSWORD" \
    --cookie "$cookies" --cookie-jar "$cookies" -H "requesttoken: $token" -H 'Content-Type: application/json' \
    -X POST --data '{}' --write-out '%{http_code}' --output "$access_error" "$endpoint")"
if [[ "$status" != '403' ]] || ! php -r '$data=json_decode(file_get_contents($argv[1]),true); exit(($data["ok"] ?? true) === false ? 0 : 1);' "$access_error"; then
    echo "Unzulässiger Teamzugriff ergab keinen verständlichen HTTP-403-Fehler." >&2
    exit 1
fi

echo "AdPlaner HTTP smoke: OK ($ADP_USER)"
