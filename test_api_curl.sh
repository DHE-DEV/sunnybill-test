#!/bin/bash

echo "=== Testing API Endpoint: /api/app/customers ==="
echo ""

curl -X GET "https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/customers" \
  -H "Authorization: Bearer sb_rDRd7B5ESrHitQrXCR29gVVy9lov72Y58vDFxICT5i9mcac5ElwZgoVdC08JNg9p" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  --silent \
  --show-error \
  | python -m json.tool

echo ""
echo "=== Test completed ==="
