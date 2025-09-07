#!/bin/bash

echo "Testing webhook with invalid token..."

# Test with invalid token
curl -X POST \
  -k \
  -v \
  -H "Content-Type: application/json" \
  -d '{
    "VoltMaster": {
      "connstate": "Connected",
      "rssi": -78,
      "operator": "Telekom_Deutschland_GER",
      "conntype": "5G-NSA",
      "ip": {
        "100.114.107.1": null
      }
    }
  }' \
  https://sunnybill-test.test/api/router-webhook/INVALID_TOKEN_TEST_123456789

echo ""
echo "Test completed!"