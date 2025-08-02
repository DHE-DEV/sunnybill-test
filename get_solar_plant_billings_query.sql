-- SQL Query für die Datensätze auf admin/solar-plant-billings

SELECT 
    spb.id,
    spb.solar_plant_id,
    spb.customer_id,
    spb.billing_year,
    spb.billing_month,
    spb.invoice_number,
    spb.participation_percentage,
    spb.produced_energy_kwh,
    spb.total_costs,
    spb.total_costs_net,
    spb.total_credits,
    spb.total_credits_net,
    spb.total_vat_amount,
    spb.net_amount,
    spb.status,
    spb.notes,
    spb.show_hints,
    spb.cost_breakdown,
    spb.credit_breakdown,
    spb.finalized_at,
    spb.sent_at,
    spb.paid_at,
    spb.created_at,
    spb.updated_at,
    spb.deleted_at,
    
    -- Solaranlagen-Daten
    sp.plant_number,
    sp.name as solar_plant_name,
    sp.location as solar_plant_location,
    sp.total_capacity_kw as solar_plant_total_capacity_kw,
    
    -- Kunden-Daten
    c.name as customer_name,
    c.company_name as customer_company_name,
    c.customer_type,
    
    -- Aktuelle Beteiligung aus participations Tabelle
    pp.percentage as current_participation_percentage,
    pp.participation_kwp as current_participation_kwp

FROM solar_plant_billings spb

-- JOIN mit Solaranlagen
LEFT JOIN solar_plants sp ON spb.solar_plant_id = sp.id

-- JOIN mit Kunden
LEFT JOIN customers c ON spb.customer_id = c.id

-- JOIN mit aktueller Beteiligung
LEFT JOIN plant_participations pp ON (
    spb.solar_plant_id = pp.solar_plant_id 
    AND spb.customer_id = pp.customer_id
)

-- Nur nicht gelöschte Datensätze (Soft Deletes)
WHERE spb.deleted_at IS NULL

-- Sortierung wie in der Filament-Tabelle
ORDER BY spb.created_at DESC;


-- ============================================================
-- VEREINFACHTE VERSION (nur die wichtigsten Spalten)
-- ============================================================

SELECT 
    spb.id,
    sp.plant_number,
    sp.name as anlagenname,
    CASE 
        WHEN c.customer_type = 'business' AND c.company_name IS NOT NULL 
        THEN c.company_name 
        ELSE c.name 
    END as kunde,
    CONCAT(
        CASE spb.billing_month
            WHEN 1 THEN 'Januar'
            WHEN 2 THEN 'Februar' 
            WHEN 3 THEN 'März'
            WHEN 4 THEN 'April'
            WHEN 5 THEN 'Mai'
            WHEN 6 THEN 'Juni'
            WHEN 7 THEN 'Juli'
            WHEN 8 THEN 'August'
            WHEN 9 THEN 'September'
            WHEN 10 THEN 'Oktober'
            WHEN 11 THEN 'November'
            WHEN 12 THEN 'Dezember'
        END,
        ' ',
        spb.billing_year
    ) as abrechnungsmonat,
    spb.invoice_number as rechnungsnummer,
    COALESCE(pp.percentage, spb.participation_percentage) as beteiligung_prozent,
    pp.participation_kwp as beteiligung_kwp,
    sp.total_capacity_kw as anlagen_kapazitaet_kw,
    CONCAT(FORMAT(spb.total_costs, 2, 'de_DE'), ' €') as kosten,
    CONCAT(FORMAT(spb.total_credits, 2, 'de_DE'), ' €') as gutschriften,
    CONCAT(FORMAT(spb.net_amount, 2, 'de_DE'), ' €') as gesamtbetrag,
    CASE spb.status
        WHEN 'draft' THEN 'Entwurf'
        WHEN 'finalized' THEN 'Finalisiert'
        WHEN 'sent' THEN 'Versendet'
        WHEN 'paid' THEN 'Bezahlt'
        ELSE spb.status
    END as status,
    spb.created_at as erstellt_am

FROM solar_plant_billings spb
LEFT JOIN solar_plants sp ON spb.solar_plant_id = sp.id
LEFT JOIN customers c ON spb.customer_id = c.id
LEFT JOIN plant_participations pp ON (
    spb.solar_plant_id = pp.solar_plant_id 
    AND spb.customer_id = pp.customer_id
)

WHERE spb.deleted_at IS NULL

ORDER BY spb.created_at DESC;


-- ============================================================
-- NUR ANZAHL DER DATENSÄTZE
-- ============================================================

SELECT COUNT(*) as anzahl_abrechnungen
FROM solar_plant_billings 
WHERE deleted_at IS NULL;


-- ============================================================
-- MIT FILTERUNG (Beispiele)
-- ============================================================

-- Nach bestimmter Solaranlage filtern:
-- WHERE spb.deleted_at IS NULL AND sp.plant_number = 'PVA-001'

-- Nach bestimmtem Jahr filtern:
-- WHERE spb.deleted_at IS NULL AND spb.billing_year = 2024

-- Nach bestimmtem Status filtern:
-- WHERE spb.deleted_at IS NULL AND spb.status = 'finalized'

-- Nach bestimmtem Kunden filtern:
-- WHERE spb.deleted_at IS NULL AND (c.name LIKE '%Mustermann%' OR c.company_name LIKE '%Mustermann%')
