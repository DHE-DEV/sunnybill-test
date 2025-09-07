#!/bin/bash

echo "Testing VoltMaster Webhook with Array IP Format..."

# Test with the new array format for IP
curl -X POST \
  -k \
  -H "Content-Type: application/json" \
  -d '{
    "VoltMaster": {
      "ip": ["100.114.107.1"],
      "ecio": 0,
      "imei": "860302050594539",
      "imsi": "901405114762101",
      "ipv6": [],
      "rscp": 0,
      "rsrp": -94,
      "rsrq": -8,
      "rssi": -68,
      "sinr": 7,
      "temp": 400,
      "iccid": "89882280666147621019",
      "manuf": "Quectel",
      "model": "RG501Q-EU",
      "modem": "2-1",
      "cellid": "34604294",
      "serial": "MPY23GM0M002670",
      "network": "5G-NSA,26201",
      "opernum": 26201,
      "psstate": "attached",
      "serving": "3,LTE,FDD,262,01",
      "conntype": "5G-NSA",
      "netstate": "Roaming",
      "operator": "Telekom Deutschland GER",
      "pincount": 3,
      "pinstate": "OK",
      "revision": "RG501QEUAAR12A11M4G_04.202.04.202",
      "simstate": "inserted",
      "connstate": "Connected",
      "modemtime": "25/09/07,15:00:40"
    }
  }' \
  https://sunnybill-test.test/api/router-webhook/RHURTV6D5h3YSKpJ4LD7eX85FkB1g4nM

echo ""
echo "Test completed!"