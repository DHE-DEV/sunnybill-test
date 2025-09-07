#!/bin/bash

echo "Testing VoltMaster Webhook with simple array format..."

curl -X POST \
  -k \
  -H "Content-Type: application/json" \
  -d '{"VoltMaster":{"ip":["10.0.0.1"],"rssi":-75,"operator":"Vodafone_DE","conntype":"4G"}}' \
  https://sunnybill-test.test/api/router-webhook/RHURTV6D5h3YSKpJ4LD7eX85FkB1g4nM

echo ""
echo "Test completed!"