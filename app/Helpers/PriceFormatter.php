<?php

namespace App\Helpers;

use App\Models\CompanySetting;

class PriceFormatter
{
    /**
     * Formatiert einen Artikelpreis basierend auf den Firmeneinstellungen
     */
    public static function formatArticlePrice(float $price, bool $withCurrency = true): string
    {
        $settings = CompanySetting::current();
        $decimalPlaces = $settings->article_price_decimal_places ?? 2;
        
        $formatted = number_format($price, $decimalPlaces, ',', '.');
        
        return $withCurrency ? $formatted . ' €' : $formatted;
    }
    
    /**
     * Formatiert einen Artikelpreis mit spezifischen Nachkommastellen
     */
    public static function formatArticlePriceWithDecimals(float $price, int $decimalPlaces, bool $withCurrency = true): string
    {
        $formatted = number_format($price, $decimalPlaces, ',', '.');
        
        return $withCurrency ? $formatted . ' €' : $formatted;
    }
    
    /**
     * Formatiert einen Gesamtpreis basierend auf den Firmeneinstellungen
     */
    public static function formatTotalPrice(float $price, bool $withCurrency = true): string
    {
        $settings = CompanySetting::current();
        $decimalPlaces = $settings->total_price_decimal_places ?? 2;
        
        $formatted = number_format($price, $decimalPlaces, ',', '.');
        
        return $withCurrency ? $formatted . ' €' : $formatted;
    }
    
    /**
     * Gibt die konfigurierten Nachkommastellen für Artikelpreise zurück
     */
    public static function getArticlePriceDecimalPlaces(): int
    {
        $settings = CompanySetting::current();
        return $settings->article_price_decimal_places ?? 2;
    }
    
    /**
     * Gibt die konfigurierten Nachkommastellen für Gesamtpreise zurück
     */
    public static function getTotalPriceDecimalPlaces(): int
    {
        $settings = CompanySetting::current();
        return $settings->total_price_decimal_places ?? 2;
    }
    
    /**
     * Formatiert einen Preis mit dynamischen Nachkommastellen (entfernt trailing zeros)
     */
    public static function formatPriceDynamic(float $price, int $maxDecimalPlaces = 6, bool $withCurrency = true): string
    {
        $formatted = rtrim(rtrim(number_format($price, $maxDecimalPlaces, ',', '.'), '0'), ',');
        
        return $withCurrency ? $formatted . ' €' : $formatted;
    }
    
    /**
     * Rundet einen Preis auf die konfigurierten Nachkommastellen für Artikelpreise
     */
    public static function roundArticlePrice(float $price): float
    {
        $decimalPlaces = self::getArticlePriceDecimalPlaces();
        return round($price, $decimalPlaces);
    }
    
    /**
     * Rundet einen Preis auf die konfigurierten Nachkommastellen für Gesamtpreise
     */
    public static function roundTotalPrice(float $price): float
    {
        $decimalPlaces = self::getTotalPriceDecimalPlaces();
        return round($price, $decimalPlaces);
    }
}