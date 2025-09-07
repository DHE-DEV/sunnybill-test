#!/bin/bash

echo "Testing VoltMaster Webhook Data..."

# Test with the exact data from production error log
curl -X POST \
  -k \
  -v \
  -H "Content-Type: application/json" \
  -d '{
    "VoltMaster": {
      "connstate": "Connected",
      "psstate": "attached",
      "netstate": "Roaming",
      "imei": "860302050594539",
      "iccid": "89882280666147621019",
      "model": "RG501Q-EU",
      "manuf": "Quectel",
      "serial": "MPY23GM0M002670",
      "revision": "RG501QEUAAR12A11M4G_04_202_04_202",
      "imsi": "901405114762101",
      "simstate": "inserted",
      "pinstate": "OK",
      "modemtime": "25/09/07,13:38:46",
      "rssi": -78,
      "rscp": 0,
      "ecio": 0,
      "rsrp": -115,
      "sinr": -1,
      "rsrq": -16,
      "cellid": "34604290",
      "operator": "Telekom_Deutschland_GER",
      "opernum": 26201,
      "conntype": "5G-NSA",
      "temp": 340,
      "pincount": 3,
      "network": "5G-NSA,26201",
      "serving": "3,LTE,FDD,262,01",
      "modem": "2-1",
      "ip": {
        "100.114.107.1": null
      }
    }
  }' \
  https://sunnybill-test.test/api/router-webhook/RHURTV6D5h3YSKpJ4LD7eX85FkB1g4nM

echo ""
echo "Test completed!"
