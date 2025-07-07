# Temporäres Passwort Fix - Zusammenfassung

## Problem
Das temporäre Passwort wurde gehashed in der Datenbank gespeichert, obwohl es im Klartext bleiben sollte, damit neue Benutzer es bei der ersten Anmeldung verwenden können.

**Ursprünglicher Datensatz:**
```sql
INSERT INTO `sunnybill`.`users` (..., `password`, `temporary_password`, ...) 
VALUES (..., '$2y$12$f70Q.K6Kl6hSU3oWv.88q.u7X2d2TuYTlzjAyn2u8BrnL2gxLjc1S', '$2y$12$f70Q.K6Kl6hSU3oWv.88q.u7X2d2TuYTlzjAyn2u8BrnL2gxLjc1S', ...);
```

**Problem:** Beide Felder (`password` und `temporary_password`) hatten den gleichen gehashten Wert.

## Ursache
Laravel's automatisches Hashing von Feldern mit "password" im Namen, kombiniert mit der Art, wie die Daten in der Filament CreateUser Page verarbeitet wurden.

## Lösung

### 1. User Model (app/Models/User.php)
- **Mutator hinzugefügt:** `setTemporaryPasswordAttribute()` - verhindert automatisches Hashing
- **Accessor hinzugefügt:** `getTemporaryPasswordAttribute()` - stellt sicher, dass der Wert als Klartext zurückgegeben wird
- **Casts unverändert:** Nur `password` wird gehashed, `temporary_password` nicht

```php
/**
 * Mutator for temporary_password - prevents automatic hashing
 */
protected function setTemporaryPasswordAttribute($value): void
{
    // Store temporary password as plain text (no hashing)
    $this->attributes['temporary_password'] = $value;
}

/**
 * Accessor for temporary_password - returns plain text
 */
protected function getTemporaryPasswordAttribute($value): ?string
{
    // Return temporary password as plain text (no decryption needed)
    return $value;
}
```

### 2. CreateUser Page (app/Filament/Resources/UserResource/Pages/CreateUser.php)
- **Entfernung von `temporary_password` aus Form-Daten:** Verhindert automatisches Hashing während der User-Erstellung
- **Setzen des temporären Passworts in `afterCreate()`:** Stellt sicher, dass der Mutator verwendet wird

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    // ... andere Logik ...
    
    // Entferne temporary_password aus den Daten, um automatisches Hashing zu vermeiden
    unset($data['temporary_password']);
    
    return $data;
}

protected function afterCreate(): void
{
    $user = $this->record;
    
    if ($user && $user->email && $this->temporaryPassword) {
        // Setze das temporäre Passwort direkt (unverschlüsselt)
        $user->temporary_password = $this->temporaryPassword;
        $user->save();
        
        // ... E-Mail-Versand ...
    }
}
```

## Ergebnis

**Nach dem Fix:**
- `password`: Gehashed (z.B. `$2y$12$gbCVKNR9yvmkZ...`)
- `temporary_password`: Klartext (z.B. `MyTestPassword123`)

## Tests
Zwei Testskripte wurden erstellt, um die Funktionalität zu validieren:

1. **test_temporary_password_fix.php** - Testet die grundlegende Funktionalität
2. **test_filament_user_creation_fix.php** - Simuliert die Filament User-Erstellung

Beide Tests bestätigen:
- ✅ Temporäres Passwort wird im Klartext gespeichert
- ✅ Normales Passwort wird korrekt gehashed
- ✅ Mutator/Accessor funktionieren korrekt
- ✅ Helper-Methoden funktionieren wie erwartet

## Auswirkungen
- Neue Benutzer erhalten jetzt E-Mails mit dem korrekten temporären Passwort im Klartext
- Das System kann das temporäre Passwort für die erste Anmeldung verwenden
- Die Sicherheit bleibt gewährleistet, da das normale Passwort weiterhin gehashed wird
- Bestehende Funktionalität bleibt unverändert

## Datum
7. Juli 2025, 08:50 Uhr
