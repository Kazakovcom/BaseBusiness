#!/usr/bin/env bash
set -euo pipefail

# Manual race check helper for the "take in work" action.
# By default it logs in as seeded master #2.
# If REQUEST_ID is not provided, the script loads /master and finds
# the first available "take" action automatically.

BASE_URL="${BASE_URL:-http://localhost:8000}"
MASTER_USER_ID="${MASTER_USER_ID:-2}"
REQUEST_ID="${REQUEST_ID:-}"

workdir="$(mktemp -d)"
cookie_jar="$workdir/cookies.txt"
login_page="$workdir/login.html"
login_response="$workdir/login-response.html"
master_page="$workdir/master.html"
body1="$workdir/response1.json"
body2="$workdir/response2.json"
code1="$workdir/code1.txt"
code2="$workdir/code2.txt"
meta="$workdir/meta.txt"

cleanup() {
  rm -rf "$workdir"
}
trap cleanup EXIT

fail() {
  echo "ERROR: $1" >&2
  exit 1
}

extract_csrf() {
  grep -o 'name="_token"[[:space:]]\+value="[^"]*"' "$1" \
    | sed 's/.*value="//; s/"$//' \
    | head -n 1
}

extract_take_request_id() {
  grep -o '/master/requests/[0-9][0-9]*/take' "$1" \
    | sed 's#.*/\([0-9][0-9]*\)/take#\1#' \
    | head -n 1
}

read_meta_field() {
  sed -n "${1}p" "$meta"
}

perform_request() {
  local output_file="$1"
  local code_file="$2"

  curl -sS \
    -b "$cookie_jar" \
    -H "Accept: application/json" \
    -H "X-Requested-With: XMLHttpRequest" \
    -X POST \
    --data-urlencode "_token=$csrf_token" \
    "$BASE_URL/master/requests/$REQUEST_ID/take" \
    -o "$output_file" \
    -w "%{http_code}" > "$code_file"
}

echo "1/4 Fetching login page: $BASE_URL/login"
curl -sS -L -c "$cookie_jar" "$BASE_URL/login" -o "$login_page"

csrf_token="$(extract_csrf "$login_page")"
if [ -z "$csrf_token" ]; then
  fail "Could not extract CSRF token from /login. Check BASE_URL=$BASE_URL and the login page markup."
fi

echo "2/4 Logging in as MASTER_USER_ID=$MASTER_USER_ID"
curl -sS -L \
  -b "$cookie_jar" \
  -c "$cookie_jar" \
  --data-urlencode "_token=$csrf_token" \
  --data-urlencode "user_id=$MASTER_USER_ID" \
  "$BASE_URL/login" \
  -o "$login_response" \
  -w "%{http_code}\n%{url_effective}" > "$meta"

login_http_code="$(read_meta_field 1)"
login_effective_url="$(read_meta_field 2)"

if [ "$login_http_code" -lt 200 ] || [ "$login_http_code" -ge 400 ]; then
  fail "Login request failed with HTTP $login_http_code."
fi

if [ -n "$REQUEST_ID" ]; then
  echo "3/4 Using explicit REQUEST_ID=$REQUEST_ID"
else
  echo "3/4 Loading master dashboard to find an assigned request"
  curl -sS -L \
    -b "$cookie_jar" \
    "$BASE_URL/master" \
    -o "$master_page" \
    -w "%{http_code}\n%{url_effective}" > "$meta"

  master_http_code="$(read_meta_field 1)"
  master_effective_url="$(read_meta_field 2)"

  if [ "$master_http_code" -lt 200 ] || [ "$master_http_code" -ge 400 ]; then
    fail "Failed to load /master after login: HTTP $master_http_code."
  fi

  if printf '%s' "$master_effective_url" | grep -Eq '/login/?$'; then
    fail "Login did not create an authenticated session for /master. Check BASE_URL and MASTER_USER_ID=$MASTER_USER_ID."
  fi

  if printf '%s' "$master_effective_url" | grep -Evq '/master/?$'; then
    fail "Expected to end on /master, but got redirected to $master_effective_url. Check that MASTER_USER_ID=$MASTER_USER_ID belongs to a master user."
  fi

  REQUEST_ID="$(extract_take_request_id "$master_page")"

  if [ -z "$REQUEST_ID" ]; then
    fail "No assigned request with an available take action was found on /master for MASTER_USER_ID=$MASTER_USER_ID. Pass REQUEST_ID explicitly or re-seed the database."
  fi

  echo "Found REQUEST_ID=$REQUEST_ID on /master"
fi

echo "4/4 Sending two parallel take requests"

perform_request "$body1" "$code1" &
pid1=$!
perform_request "$body2" "$code2" &
pid2=$!

wait "$pid1"
wait "$pid2"

http_code_1="$(cat "$code1")"
http_code_2="$(cat "$code2")"

echo
echo "Response #1: HTTP $http_code_1"
cat "$body1"
echo
echo
echo "Response #2: HTTP $http_code_2"
cat "$body2"
echo
echo

success_count=0
conflict_count=0

if [ "$http_code_1" = "200" ]; then
  success_count=$((success_count + 1))
elif [ "$http_code_1" = "409" ]; then
  conflict_count=$((conflict_count + 1))
fi

if [ "$http_code_2" = "200" ]; then
  success_count=$((success_count + 1))
elif [ "$http_code_2" = "409" ]; then
  conflict_count=$((conflict_count + 1))
fi

if [ "$success_count" -eq 1 ] && [ "$conflict_count" -eq 1 ]; then
  echo "Race check passed: exactly one request succeeded and the second one was rejected with a controlled conflict."
  exit 0
fi

fail "Race check failed: expected exactly one HTTP 200 and one HTTP 409, got HTTP $http_code_1 and HTTP $http_code_2."
