#!/bin/bash

# Task Creation via cURL mit dem erstellten Token
# Verantwortlich: User ID 1
# Zugeordnet: User ID 57

curl -X POST http://127.0.0.1:8000/api/app/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer Vsew8fUkghFV6FxY0ADX8rpTCk6k5JCMCEwjEWp1" \
  -d '{
    "title": "Neue Aufgabe über API",
    "description": "Diese Aufgabe wurde über die API erstellt mit korrekten Zuordnungen",
    "task_type": "Installation", 
    "task_type_id": 1,
    "priority": "medium",
    "status": "open",
    "assigned_to": 57,
    "owner_id": 1,
    "due_date": "2025-08-15",
    "due_time": "14:30",
    "estimated_minutes": 120
  }'
