#!/usr/bin/env bash
set -euo pipefail

# Manual race check helper for the "take in work" action.
# By default it logs in as seeded master #2 and tries to find
# the first available assigned request on /master automatically.

BASE_URL="${BASE_URL:-http://localhost:8000}"
MASTER_USER_ID="${MASTER_USER_ID:-2}"
REQUEST_ID="${REQUEST_ID:-}"

workdir="$(mktemp -d)"
cookie_jar="$workdir/cookies.txt"
login_page="$workdir/login.html"
master_page="$workdir/master.html"
body1="$workdir/response1.json"
body2="$workdir/response2.json"
code1="$workdir/code1.txt"
code2="$workdir/code2.txt"

cleanup() {
  rm -rf "$workdir"
}
trap cleanup EXIT

extract_csrf() {
  sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' "$1" | head -n 1
}

extract_take_request_id() {
  sed -n 's#.*action="[^"]*/master/requests/\([0-9][0-9]*\)/take".*#\1#p' "$1" | head -n 1
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
curl -sS -c "$cookie_jar" "$BASE_URL/login" -o "$login_page"

csrf_token="$(extract_csrf "$login_page")"
if [ -z "$csrf_token" ]; then
  echo "Could not extract CSRF token from /login" >&2
  exit 1
fi

echo "2/4 Logging in as MASTER_USER_ID=$MASTER_USER_ID"
curl -sS -L \
  -b "$cookie_jar" \
  -c "$cookie_jar" \
  -X POST \
  --data-urlencode "_token=$csrf_token" \
  --data-urlencode "user_id=$MASTER_USER_ID" \
  "$BASE_URL/login" \
  -o /dev/null

echo "3/4 Loading master dashboard"
curl -sS -b "$cookie_jar" "$BASE_URL/master" -o "$master_page"

csrf_token="$(extract_csrf "$master_page")"
if [ -z "$csrf_token" ]; then
  echo "Could not extract CSRF token from /master" >&2
  exit 1
fi

if [ -z "$REQUEST_ID" ]; then
  REQUEST_ID="$(extract_take_request_id "$master_page")"
fi

if [ -z "$REQUEST_ID" ]; then
  echo "No assigned request with available take action was found for master $MASTER_USER_ID." >&2
  echo "Re-seed the database or pass REQUEST_ID explicitly." >&2
  exit 1
fi

echo "Using REQUEST_ID=$REQUEST_ID"
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

echo "Race check failed: expected exactly one HTTP 200 and one HTTP 409." >&2
exit 1
