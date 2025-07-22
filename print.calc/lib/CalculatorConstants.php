<?php

class CalculatorConstants {
    // Типы бумаги
    const PAPER_TYPE_80 = 80;
    const PAPER_TYPE_300 = 300;
    
    // Типы печати
    const PRINT_TYPE_SINGLE = 'single';
    const PRINT_TYPE_DOUBLE = 'double';
    const PRINT_TYPE_DIGITAL = 'digital';
    const PRINT_TYPE_OFFSET = 'offset';
    
    // Типы производства
    const PRINTING_TYPE_OFFSET = 'Офсетная';
    const PRINTING_TYPE_DIGITAL = 'Цифровая';
    
    // Базовые значения
    const DEFAULT_PAPER_PRICE = 1.5;
    const ADJUSTMENT_BASE = 100;
    const ADJUSTMENT_STEP = 50;
    
    // Форматы
    const SIZE_A3 = 'A3';
    const SIZE_A4 = 'A4';
    const SIZE_A5 = 'A5';
    const SIZE_A6 = 'A6';
    const SIZE_A7 = 'A7';
    const SIZE_EURO = 'Евро';
    
    // Типы переплета
    const BINDING_STAPLE = 'staple';
    const BINDING_SPRING = 'spring';
    const BINDING_GLUE = 'glue';
    
    // Дополнительные услуги
    const SERVICE_BIGOVKA = 'bigovka';
    const SERVICE_PERFORATION = 'perforation';
    const SERVICE_DRILL = 'drill';
    const SERVICE_NUMBERING = 'numbering';
    const SERVICE_CORNER_RADIUS = 'cornerRadius';
    
    // Пороговые значения
    const MIN_CATALOG_PAGES = 8;
    const PAGES_PER_SHEET = 4;
    const THICK_CATALOG_PAGES = 20;
}
